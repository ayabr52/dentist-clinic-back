<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function getUserAppointments($user)
    {
        if ($user->hasRole('patient')) {
            return Appointment::where('patient_id', $user->id)->with(['doctor', 'service', 'invoice'])->get();
        } elseif ($user->hasRole('doctor')) {
            return Appointment::where('doctor_id', $user->id)->with(['patient', 'service', 'invoice'])->get();
        }

        return Appointment::with(['patient', 'doctor', 'service', 'invoice'])->get();
    }

    public function bookAppointment(array $data, int $patientId): Appointment
    {
        $appointmentDate = $data['appointment_date'];
        $startTimeString = $data['start_time']; // مثال: "10:00"
        $doctorId = $data['doctor_id'];
        $serviceId = $data['service_id'];

        // 1. جلب الخدمة لتحديد مدتها وسعرها
        $service = Service::findOrFail($serviceId);
        $durationMinutes = $service->duration_minutes ?? 30; // مدة الجلسة بالدقائق (افتراضي 30 دقيقة إذا لم تكن محددة)
        $servicePrice = $service->price ?? 0.00;

        // حساب وقت البداية والنهاية باستخدام Carbon
        $startTime = Carbon::parse("$appointmentDate $startTimeString");
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        // نبدأ الـ Transaction والقفل لمنع التسابق (Race Conditions)
        return DB::transaction(function () use ($doctorId, $appointmentDate, $startTime, $endTime, $startTimeString, $patientId, $servicePrice, $serviceId, $data) {

            // أ. التحقق من جدول الإجازات (مع قفل الصفوف المرتبطة إن أمكن أو التحقق الآمن)
            $isLeave = DB::table('doctor_leaves')
                ->where('doctor_id', $doctorId)
                ->whereDate('start_date', '<=', $appointmentDate)
                ->whereDate('end_date', '>=', $appointmentDate)
                ->exists();

            if ($isLeave) {
                throw ValidationException::withMessages([
                    'appointment_date' => ['عذراً، الطبيب في إجازة في هذا التاريخ.'],
                ]);
            }

            // ب. التحقق من ساعات الدوام لهذا اليوم
            $dayOfWeek = $startTime->dayOfWeek; // 0 = الأحد، الخ
            $schedule = DoctorSchedule::where('doctor_id', $doctorId)
                ->where('day_of_week', $dayOfWeek)
                ->where('is_working_day', true)
                ->where('start_time', '<=', $startTimeString)
                ->where('end_time', '>=', $endTime->format('H:i')) // التأكد أن وقت النهاية ضمن الدوام أيضاً
                ->exists();

            if (! $schedule) {
                throw ValidationException::withMessages([
                    'start_time' => ['عذراً، هذا الوقت خارج ساعات دوام الطبيب أو أن الجلسة تتجاوز وقت الدوام.'],
                ]);
            }

            // ج. التحقق الحقيقي من التداخل الزمني (Overlap Validation) مع lockForUpdate
            $isConflicting = Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', $appointmentDate)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($startTimeString, $startTime, $endTime) {
                    // التحقق من تطابق وقت البداية تماماً أو تقاطع الفترات
                    $query->where('start_time', $startTimeString)
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<', $endTime->format('H:i'))
                                ->where('end_time', '>', $startTime->format('H:i'));
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($isConflicting) {
                throw ValidationException::withMessages([
                    'start_time' => ['عذراً، هذا الوقت يتداخل مع موعد حجز آخر لهذا الطبيب.'],
                ]);
            }

            // د. إدارة الفواتير
            $invoice = Invoice::where('patient_id', $patientId)
                ->whereIn('status', ['unpaid', 'partial'])
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $invoice) {
                $invoice = Invoice::create([
                    'patient_id' => $patientId,
                    'total_amount' => $servicePrice,
                    'final_amount' => $servicePrice,
                    'paid_amount' => 0.00,
                    'status' => 'unpaid',
                ]);
            } else {
                $invoice->update([
                    'total_amount' => $invoice->total_amount + $servicePrice,
                    'final_amount' => $invoice->final_amount + $servicePrice,
                ]);
            }

            // هـ. إنشاء الموعد مع حفظ وقت النهاية (end_time) لتسهيل الفحص المستقبلي
            return Appointment::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'service_id' => $serviceId,
                'invoice_id' => $invoice->id,
                'appointment_date' => $appointmentDate,
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTime->format('H:i'), // يجب التأكد من وجود هذا العمود في جدول appointments
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    public function updateStatus(Appointment $appointment, string $status): Appointment
    {
        $appointment->update(['status' => $status]);

        return $appointment;
    }
}
