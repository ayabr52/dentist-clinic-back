<?php

namespace App\Services;

use App\Models\MedicalRecord;

class MedicalRecordService
{

    public function storeRecord(array $data, int $doctorId): MedicalRecord
    {
        return MedicalRecord::create([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $doctorId,
            'appointment_id' => $data['appointment_id'] ?? null,
            'diagnosis' => $data['diagnosis'],
            'prescription' => $data['prescription'] ?? null,
            'doctor_notes' => $data['doctor_notes'] ?? null,
            'ai_analysis_result' => $data['ai_analysis_result'] ?? null,
        ]);
    }
    public function getPatientRecords($user)
    {
        // إذا كان المريض يطلب سجله الطبي، أو الطبيب يطلب سجلات مرضاه، أو الأدمن الكل
        if ($user->hasRole('patient')) {
            return MedicalRecord::where('patient_id', $user->id)->with(['doctor', 'appointment'])->get();
        } elseif ($user->hasRole('doctor')) {
            return MedicalRecord::where('doctor_id', $user->id)->with(['patient', 'appointment'])->get();
        }

        return MedicalRecord::with(['patient', 'doctor', 'appointment'])->get();
    }

 
}
