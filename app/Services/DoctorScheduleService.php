<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;
use App\Models\Service;
use Carbon\Carbon;
use Exception;

class DoctorScheduleService
{
    /**
     * حساب الأوقات المتاحة للطبيب بناءً على مدة الخدمة وجدول الدوام والإجازات والحجوزات القائمة
     */
    public function getAvailableSlots(int $doctorId, string $dateString, int $serviceId): array
    {
        // جلب مدة الخدمة المطلوبة
        $service = Service::findOrFail($serviceId);
        $durationMinutes = $service->duration_minutes ?? 30;

        $date = Carbon::parse($dateString);
        $dayOfWeek = $date->dayOfWeek; // 0 (الأحد) إلى 6 (السبت)

        // 1. التحقق هل الطبيب في إجازة في هذا التاريخ؟
        $isLeave = DoctorLeave::where('doctor_id', $doctorId)
            ->whereDate('start_date', '<=', $dateString)
            ->whereDate('end_date', '>=', $dateString)
            ->exists();

        if ($isLeave) {
            throw new Exception('عذراً، الطبيب في إجازة خلال هذا التاريخ.');
        }

        // 2. التحقق من جدول دوام الطبيب في هذا اليوم
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (! $schedule) {
            throw new Exception('ليس لدى الطبيب دوام مسجل في هذا اليوم.');
        }

        $shiftStart = Carbon::parse($schedule->start_time);
        $shiftEnd = Carbon::parse($schedule->end_time);
        $intervalMinutes = 30; // الفاصل الزمني لاقتراح أوقات البداية

        // جلب الحجوزات القائمة للطبيب في هذا اليوم (غير الملغاة)
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $dateString)
            ->where('status', '!=', 'cancelled')
            ->with('service')
            ->get();

        $availableSlots = [];
        $currentSlotStart = $shiftStart->copy();

        // فحص أوقات البداية المقترحة والتداخلات الزمنية
        while ($currentSlotStart->copy()->addMinutes($durationMinutes)->lte($shiftEnd)) {
            $slotStartStr = $currentSlotStart->format('H:i');
            $slotEndTime = $currentSlotStart->copy()->addMinutes($durationMinutes);

            $hasConflict = false;
            foreach ($appointments as $app) {
                $appStart = Carbon::parse($app->start_time);
                $appDuration = $app->service->duration_minutes ?? ($app->duration_minutes ?? 30);
                $appEnd = $appStart->copy()->addMinutes($appDuration);

                // فحص التداخل
                if ($currentSlotStart->lt($appEnd) && $slotEndTime->gt($appStart)) {
                    $hasConflict = true;
                    break;
                }
            }

            if (! $hasConflict) {
                $availableSlots[] = $slotStartStr;
            }

            $currentSlotStart->addMinutes($intervalMinutes);
        }

        return $availableSlots;
    }
}
