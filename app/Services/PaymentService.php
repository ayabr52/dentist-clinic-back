<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function createPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $amount = $data['amount'];
            $discount = $data['discount'] ?? 0.00;
            $finalAmount = max(0, $amount - $discount);

            return Payment::create([
                'appointment_id' => $data['appointment_id'],
                'patient_id' => $data['patient_id'],
                'amount' => $amount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'status' => $data['status'] ?? 'pending',
            ]);
        });
    }

    public function updateStatus(Payment $payment, string $status, ?string $method = null): Payment
    {
        return DB::transaction(function () use ($payment, $status, $method) {
            $updateData = ['status' => $status];

            if ($method) {
                $updateData['payment_method'] = $method;
            }

            $payment->update($updateData);

            return $payment->fresh();
        });
    }
}
