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
            'remaining_amount' => $this->final_amount - $this->paid_amount,
            'status' => $this->status, // unpaid, partial, paid
            'payments' => PaymentResource::collection($this->whenLoaded('payments')), // إن وجدت
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
