<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method, // cash, card, etc.

            // جلب حالة الفاتورة والمبلغ الإ النهائي من علاقة invoice بشكل آمن
            'invoice_id' => $this->invoice_id,
            'invoice_status' => $this->whenLoaded('invoice', fn() => $this->invoice->status),
            'final_amount' => $this->whenLoaded('invoice', fn() => $this->invoice->final_amount),

            'collected_by' => new UserResource($this->whenLoaded('collector')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
