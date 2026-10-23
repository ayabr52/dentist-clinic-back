<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class MedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'exists:appointments,id'],
            'patient_id' => ['required', 'exists:users,id'],
            'diagnosis' => ['required', 'string'],
            'prescription' => ['nullable', 'string'], // أضيفت لمنع ضياع الوصفة الطبية
            'doctor_notes' => ['nullable', 'string'], // أضيفت لملاحظات الطبيب
            'notes' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:5'], // تحديد الحد الأقصى لعدد الملفات
            'images.*' => ['file', 'mimes:jpeg,png,jpg,pdf', 'max:5120'], // منع الملفات النصية مثل notes.txt وتحديد النوع والحجم (بحد أقصى 5 ميجابايت)
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->appointment_id && $this->patient_id) {
                $appointment = Appointment::find($this->appointment_id);

                if ($appointment) {
                    // 1. التحقق من أن المريض الممرر هو نفس مريض الموعد
                    if ((int) $appointment->patient_id !== (int) $this->patient_id) {
                        $validator->errors()->add('patient_id', 'المريض المحدد لا يطابق المريض المسجل في هذا الموعد.');
                    }

                    // 2. التحقق من أن الطبيب الحالي هو نفس طبيب الموعد (إلا إذا كان Admin)
                    $user = auth()->user();
                    if ($user && ! $user->hasRole('admin', 'api') && (int) $appointment->doctor_id !== (int) $user->id) {
                        $validator->errors()->add('appointment_id', 'غير مصرح لك بإضافة سجل طبي لموعد يخص طبيباً آخر.');
                    }
                }
            }
        });
    }
}
