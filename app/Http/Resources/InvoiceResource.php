<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'total_amount' => $this->total_amount,
            'final_amount' => $this->final_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->final_amount - $this->paid_amount, // تصحيح الحساب هنا
            'status' => $this->status, // unpaid, partial, paid
            'patient' => new UserResource($this->whenLoaded('patient')),
            'appointments' => AppointmentResource::collection($this->whenLoaded('appointments')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
