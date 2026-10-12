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
            'appointment_id'     => 'required|exists:appointments,id',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:cash,card,insurance,online',
        ];
    }
}
