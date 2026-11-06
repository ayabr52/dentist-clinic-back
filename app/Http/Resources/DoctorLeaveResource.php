<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorLeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'doctor' => new UserResource($this->whenLoaded('doctor')),
            'start_date' => $this->start_date ?? null,
            'end_date' => $this->end_date ?? null,
            'reason' => $this->reason ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
