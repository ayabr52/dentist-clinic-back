<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDoctorScheduleRequest;
use App\Models\Appointment;
use App\Models\DoctorLeave;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = DoctorSchedule::with('doctor:id,first_name,last_name');

        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    public function store(StoreDoctorScheduleRequest $request)
    {
        $validated = $request->validated();
        $doctorId = (int) $validated['doctor_id'];
        $user = auth()->user();

        // فحص الملكية: منع الطبيب من إضافة أو تعديل جدول طبيب آخر إلا إذا كان Admin
        if (! $user->hasRole('admin', 'api') && $user->id !== $doctorId) {
            return response()->json([
                'status' => 'error',
                'message' => 'عذراً، غير مصرح لك بتعديل جدول دوام طبيب آخر.',
            ], 403);
        }

        $savedSchedules = [];

        DB::transaction(function () use ($validated, $doctorId, &$savedSchedules) {
            foreach ($validated['schedules'] as $item) {
                $savedSchedules[] = DoctorSchedule::updateOrCreate(
                    [
                        'doctor_id' => $doctorId,
                        'day_of_week' => $item['day_of_week'],
                    ],
                    [
                        'start_time' => $item['start_time'],
                        'end_time' => $item['end_time'],
                        'is_working_day' => $item['is_working_day'] ?? true,
                    ]
                );
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'تم حفظ جدول أوقات العمل بنجاح',
            'data' => $savedSchedules,
        ], 201);
    }

    /**
     * Remove the specified doctor schedule.
     *
     * @param  DoctorSchedule  $schedule  Matches the route parameter {schedule}
     * @return JsonResponse
     */
    public function destroy(DoctorSchedule $schedule)
    {
        $user = auth()->user();

        // Verify ownership or admin role
        if (! $user->hasRole('admin', 'api') && $user->id !== $schedule->doctor_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this schedule.',
            ], 403);
        }

        $schedule->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Schedule deleted successfully.',
        ], 200);
    }

    /**
     * جلب الأوقات المتاحة للطبيب في تاريخ معين
     */
    public function getAvailableSlots(Request $request, $doctorId)
    {
        $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $dateString = $request->date;
        $date = Carbon::parse($dateString);
        $dayOfWeek = $date->dayOfWeek; // 0 (الأحد) إلى 6 (السبت)

        // 1. التحقق هل الطبيب في إجازة في هذا التاريخ؟
        $isLeave = DoctorLeave::where('doctor_id', $doctorId)
            ->whereDate('start_date', '<=', $dateString)
            ->whereDate('end_date', '>=', $dateString)
            ->exists();

        if ($isLeave) {
            return response()->json([
                'success' => true,
                'message' => 'عذراً، الطبيب في إجازة خلال هذا التاريخ.',
                'data' => [],
            ]);
        }

        // 2. التحقق من جدول دوام الطبيب في هذا اليوم
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_working_day', true)
            ->first();

        if (! $schedule) {
            return response()->json([
                'success' => true,
                'message' => 'ليس لدى الطبيب دوام مسجل في هذا اليوم.',
                'data' => [],
            ]);
        }

        // 3. توليد الفترات الزمنية (كل 30 دقيقة) ضمن ساعات الدوام
        $startTime = Carbon::parse($schedule->start_time);
        $endTime = Carbon::parse($schedule->end_time);
        $slotDuration = 30; // بالدقائق

        // جلب الأوقات المحجوزة مسبقاً لهذا الطبيب في هذا اليوم (غير الملغاة)
        $bookedTimes = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $dateString)
            ->where('status', '!=', 'cancelled')
            ->pluck('start_time')
            ->map(function ($time) {
                return Carbon::parse($time)->format('H:i');
            })
            ->toArray();

        $availableSlots = [];

        while ($startTime->copy()->addMinutes($slotDuration)->lte($endTime)) {
            $slotTime = $startTime->format('H:i');

            // إذا لم يكن الوقت محجوزاً مسبقاً، يتم إضافته للأوقات المتاحة
            if (! in_array($slotTime, $bookedTimes)) {
                $availableSlots[] = $slotTime;
            }

            $startTime->addMinutes($slotDuration);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب الأوقات المتاحة بنجاح',
            'data' => $availableSlots,
        ]);
    }
}
