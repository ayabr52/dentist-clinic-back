<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // لاحقا يمكننا تقييدها بحيث يسمح للأطباء فقط بالإضافة
    }

    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'diagnosis' => 'required|string',
            'prescription' => 'nullable|string',
            'doctor_notes' => 'nullable|string',
            'ai_analysis_result' => 'nullable|string',
        ];
    }
}
