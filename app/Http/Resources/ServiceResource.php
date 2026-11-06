<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? null,
            'description' => $this->description ?? null,
            'price' => $this->price ?? 0.00,
            // إظهار السعر المخصص للطبيب من جدول الـ Pivot إن وجد
            'custom_price' => $this->whenPivotLoaded('doctor_services', function () {
                return $this->pivot->custom_price;
            }),
            'duration_minutes' => $this->duration_minutes ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
