<?php

namespace App\Services;

use App\Models\MedicalRecord;
use Illuminate\Support\Facades\DB;

class MedicalRecordService
{
    public function storeRecord(array $data, int $doctorId, array $images = []): MedicalRecord
    {
        return DB::transaction(function () use ($data, $doctorId, $images) {

            /** @var MedicalRecord $record */
            $record = MedicalRecord::create([
                'patient_id'         => $data['patient_id'],
                'doctor_id'          => $doctorId,
                'appointment_id'     => $data['appointment_id'] ?? null,
                'diagnosis'          => $data['diagnosis'],
                'prescription'       => $data['prescription'] ?? null,
                'doctor_notes'       => $data['doctor_notes'] ?? null,
                'ai_analysis_result' => $data['ai_analysis_result'] ?? null,
            ]);

            if (!empty($images)) {
                foreach ($images as $imageFile) {
                    $path = $imageFile->store('medical_images', 'public');

                    $record->images()->create([
                        'image_path' => $path,
                        'image_type' => $data['image_type'] ?? 'xray',
                        'title'      => $data['image_title'] ?? null,
                    ]);
                }
            }

            return $record->load(['patient', 'doctor', 'appointment', 'images']);
        });
    }

    public function getPatientRecords($user, ?int $patientId = null)
    {
        $query = MedicalRecord::with(['patient', 'doctor', 'appointment', 'images'])
            ->latest();

        if ($user->hasRole('patient')) {
            return $query->where('patient_id', $user->id)->get();
        }

        if ($user->hasRole('doctor')) {
            $query->where('doctor_id', $user->id);
        }

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        return $query->get();
    }
}
