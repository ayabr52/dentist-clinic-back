<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentService
{
    /**
     * Create a new payment record for an invoice.
     *
     * This method handles the creation of a payment, validates that the amount does not exceed
     * the remaining balance on the invoice (using cent-based integer calculations to prevent float precision bugs),
     * and updates the invoice's paid amount and status.
     * It uses DB::transaction to ensure data consistency and lockForUpdate on the invoice
     * to prevent race conditions when multiple payments are attempted simultaneously.
     *
     * @param array $data The payment data including appointment_id (optional), invoice_id, amount, and payment_method.
     * @return Payment The created Payment record, loaded with invoice, patient, and collector relationships.
     * @throws ValidationException If appointment_id is invalid or amount exceeds remaining balance.
     * @throws \Exception If database transaction fails or invoice not found.
     */
    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {

            // If appointment_id is provided, resolve the corresponding invoice_id automatically
            if (isset($data['appointment_id'])) {
                $appointment = \App\Models\Appointment::with('invoice')->findOrFail($data['appointment_id']);

                if (!$appointment->invoice_id || !$appointment->invoice) {
                    throw ValidationException::withMessages([
                        'appointment_id' => ['This appointment is not linked to any financial invoice yet.']
                    ]);
                }

                $invoiceId = $appointment->invoice_id;
            } else {
                $invoiceId = $data['invoice_id'];
            }

            /**
             * Critical Concurrency Lock:
             * Use lockForUpdate() to prevent Race Conditions when concurrent payment attempts occur.
             */
            $invoice = Invoice::where('id', $invoiceId)->lockForUpdate()->firstOrFail();

            $amountPaid = (float) $data['amount'];

            // Prevent float precision inaccuracies (e.g. 0.30 - 0.10 != 0.20) by scaling values into cents (integers)
            $finalAmountCents   = (int) round(((float) $invoice->final_amount) * 100);
            $paidAmountCents    = (int) round($amountPaid * 100);
            $existingPaidCents  = (int) round(((float) $invoice->paid_amount) * 100);

            $remainingCents     = $finalAmountCents - $existingPaidCents;

            // Validate that the payment amount does not exceed the remaining balance
            if ($paidAmountCents > $remainingCents) {
                $remainingAmountFormatted = number_format($remainingCents / 100, 2);
                throw ValidationException::withMessages([
                    'amount' => ['The paid amount (' . $amountPaid . ') exceeds the remaining balance on the invoice. Remaining balance: ' . $remainingAmountFormatted]
                ]);
            }

            // Create the payment record
            /** @var Payment $payment */
            $payment = Payment::create([
                'invoice_id'     => $invoice->id,
                'patient_id'     => $invoice->patient_id,
                'amount'         => $amountPaid,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'collected_by'   => auth()->id(),
            ]);

            // Update the invoice's total paid amount accurately
            $newPaidCents = $existingPaidCents + $paidAmountCents;
            $invoice->paid_amount = $newPaidCents / 100;

            // Automatically determine and update invoice status
            if ($newPaidCents >= $finalAmountCents) {
                $invoice->status = 'paid';
            } elseif ($newPaidCents > 0) {
                $invoice->status = 'partial';
            }

            $invoice->save();

            return $payment->load(['invoice.patient', 'collector']);
        });
    }

    /**
     * Retrieve a specific payment record along with its related details.
     *
     * This method fetches a payment instance by its ID and includes related models such as
     * the associated invoice, patient, appointments, and collector.
     *
     * @param int $paymentId The ID of the payment record to retrieve.
     * @return \App\Models\Payment The payment instance with loaded relations.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the payment with the given ID is not found.
     */
    public function getPaymentDetails(int $paymentId): Payment
    {
        // Using 'appointments' (plural) to match the exact relationship definition in Invoice model
        return Payment::with(['invoice.patient', 'invoice.appointments', 'collector'])->findOrFail($paymentId);
    }

    /**
     * Retrieve a list of payments associated with a specific invoice.
     *
     * This method queries the Payment model to fetch all payments that are linked
     * to the provided invoice ID. The results are ordered by the creation date in
     * descending order and include the associated collector information.
     *
     * @param int $invoiceId The ID of the invoice for which to retrieve payments.
     * @return \Illuminate\Database\Eloquent\Collection A collection of Payment models.
     */
    public function getInvoicePayments(int $invoiceId)
    {
        return Payment::where('invoice_id', $invoiceId)
            ->with(['collector'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
