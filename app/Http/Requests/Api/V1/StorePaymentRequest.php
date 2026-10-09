<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => 'required|exists:appointments,id|unique:payments,appointment_id',
            'patient_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'status' => 'nullable|in:paid,partial,pending',
        ];
    }
}
