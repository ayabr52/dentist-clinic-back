<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentService
{
    public function getUserAppointments($user)
    {
        if ($user->hasRole('patient')) {
            return Appointment::where('patient_id', $user->id)->with(['doctor', 'service'])->get();
        } elseif ($user->hasRole('doctor')) {
            return Appointment::where('doctor_id', $user->id)->with(['patient', 'service'])->get();
        }

        return Appointment::with(['patient', 'doctor', 'service'])->get();
    }

    public function bookAppointment(array $data, int $patientId): Appointment
    {
        $startTime = date('H:i', strtotime($data['start_time']));
        $appointmentDate = $data['appointment_date'];
        $doctorId = $data['doctor_id'];

        // 1. التحقق المباشر من جدول الإجازات (بدون موديل، استعلام خفيف وسريع)
        // التحقق إذا كان تاريخ الموعد يقع ضمن فترة إجازة الطبيب
        $isLeave = DB::table('doctor_leaves')
            ->where('doctor_id', $doctorId)
            ->whereDate('start_date', '<=', $appointmentDate)
            ->whereDate('end_date', '>=', $appointmentDate)
            ->exists();

        if ($isLeave) {
            throw new \InvalidArgumentException('عذراً، الطبيب في إجازة في هذا التاريخ.');
        }

        // 2. معرفة رقم اليوم في الأسبوع (0 = الأحد، 1 = الإثنين، إلخ)
        $dayOfWeek = Carbon::parse($appointmentDate)->dayOfWeek;

        // 3. التحقق من ساعات الدوام لهذا اليوم من جدول DoctorSchedule
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $startTime)
            ->exists();

        if (!$schedule) {
            throw new \InvalidArgumentException('عذراً، هذا الوقت خارج ساعات دوام الطبيب أو أن الطبيب لا يعمل في هذا اليوم.');
        }

        // 4. التحقق هل الموعد محجوز مسبقاً
        $isBooked = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $appointmentDate)
            ->where('start_time', $startTime)
            ->exists();

        if ($isBooked) {
            throw new \InvalidArgumentException('عذراً، هذا الموعد محجوز مسبقاً لهذا الطبيب.');
        }

        return Appointment::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'service_id' => $data['service_id'],
            'appointment_date' => $appointmentDate,
            'start_time' => $startTime,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function updateStatus(Appointment $appointment, string $status): Appointment
    {
        $appointment->update(['status' => $status]);
        return $appointment;
    }
}
