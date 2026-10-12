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
     * Create a new partial or full payment transaction for an invoice.
     *
     * This method runs within a database transaction to ensure financial data integrity.
     * It validates that the paid amount does not exceed the remaining balance,
     * creates the payment record, and automatically updates the invoice's paid amount and status.
     *
     * @param array{
     *     invoice_id?: int,
     *     appointment_id?: int,
     *     amount: float,
     *     payment_method?: string
     * } $data The validated payment request data.
     *
     * @return \App\Models\Payment The newly created payment instance.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the invoice is not found.
     * @throws \Illuminate\Validation\ValidationException If the paid amount exceeds the remaining balance.
     * @throws \Throwable If any database operation fails.
     */
    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {

            // إذا تم إرسال appointment_id، نستخرج الـ invoice_id المرتبط به تلقائياً
            if (isset($data['appointment_id'])) {
                $appointment = \App\Models\Appointment::with('invoice')->findOrFail($data['appointment_id']);

                if (!$appointment->invoice_id || !$appointment->invoice) {
                    throw ValidationException::withMessages([
                        'appointment_id' => ['هذا الموعد غير مرتبط بفاتورة مالية بعد.']
                    ]);
                }

                $invoiceId = $appointment->invoice_id;
            } else {
                $invoiceId = $data['invoice_id'];
            }

            /** @var Invoice $invoice */
            $invoice = Invoice::findOrFail($invoiceId);

            $amountPaid = (float) $data['amount'];

            // التصحيح هنا: حساب المبلغ المتبقي بشكل صحيح
            $remainingAmount = (float) $invoice->final_amount - (float) $invoice->paid_amount;

            // Validate that the payment amount does not exceed the remaining balance
            if ($amountPaid > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount' => ['المبلغ المدفوع (' . $amountPaid . ') يتجاوز المبلغ المتبقي على الفاتورة. المبلغ المتبقي فقط: ' . $remainingAmount]
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

            // Update the invoice's total paid amount
            $invoice->paid_amount = (float) $invoice->paid_amount + $amountPaid;

            // Automatically determine and update invoice status
            if ($invoice->paid_amount >= $invoice->final_amount) {
                $invoice->status = 'paid';
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = 'partial';
            }

            $invoice->save();

            return $payment;
        });
    }

    /**
     * Retrieve a specific payment record along with its related details.
     *
     * This method fetches a payment instance by its ID and includes related models such as
     * the associated invoice, patient, appointments, and collector.
     *
     * @param int $paymentId The ID of the payment record to retrieve.
     *
     * @return \App\Models\Payment The payment instance with loaded relations.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the payment with the given ID is not found.
     */
    public function getPaymentDetails(int $paymentId): Payment
    {
        // استخدام العلاقة appointments (بالجمع) المطابقة لملف Invoice.php
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
