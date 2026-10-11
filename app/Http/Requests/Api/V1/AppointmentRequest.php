<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\DoctorSchedule;
use App\Models\DoctorLeave;
use Carbon\Carbon;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id'        => ['required', 'exists:users,id'],
            'service_id'       => ['required', 'exists:services,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time'       => ['required', 'date_format:H:i'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * التحقق المتقدم من تطابق موعد المريض مع جدول دوام الطبيب وإجازاته
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $doctorId  = $this->doctor_id;
            $dateString = $this->appointment_date;
            $startTime = $this->start_time;

            if (!$doctorId || !$dateString || !$startTime) {
                return;
            }

            // تحويل تاريخ الموعد إلى كائن Carbon لمعرفة يوم الأسبوع
            $date = Carbon::parse($dateString);
            $dayOfWeek = $date->dayOfWeek; // يعطي رقماً من 0 (الأحد) إلى 6 (السبت)

            // 1. التحقق هل الطبيب في إجازة في هذا التاريخ؟
            $isLeave = DoctorLeave::where('doctor_id', $doctorId)
                ->whereDate('start_date', '<=', $dateString)
                ->whereDate('end_date', '>=', $dateString)
                ->exists();

            if ($isLeave) {
                $validator->errors()->add('appointment_date', 'عذراً، الطبيب في إجازة خلال هذا التاريخ.');
                return;
            }

            // 2. التحقق هل الطبيب لديه دوام مسجل في هذا اليوم من الأسبوع (طابقنا الرقم مع day_of_week)
            $schedule = DoctorSchedule::where('doctor_id', $doctorId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working_day', true)
                ->first();

            if (!$schedule) {
                $validator->errors()->add('appointment_date', 'عذراً، الطبيب غير مسجل دوامه في هذا اليوم من الأسبوع.');
                return;
            }

            // 3. التحقق هل وقت الموعد يقع ضمن ساعات دوام الطبيب في هذا اليوم؟
            if ($startTime < $schedule->start_time || $startTime > $schedule->end_time) {
                $validator->errors()->add('start_time', "وقت الموعد خارج ساعات الدوام المتاحة للطبيب لهذا اليوم ({$schedule->start_time} - {$schedule->end_time}).");
            }
        });
    }
}
