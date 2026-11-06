<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'diagnosis' => $this->diagnosis ?? null,
            'prescription' => $this->prescription ?? null,
            'notes' => $this->notes ?? null,
            'images' => $this->images ?? [],
            'patient' => new UserResource($this->whenLoaded('patient')),
            'doctor' => new UserResource($this->whenLoaded('doctor')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
