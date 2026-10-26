<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDoctorScheduleRequest;
use App\Models\DoctorSchedule;
use App\Services\DoctorScheduleService;
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

        return response()->success($query->get(), 'تم جلب جداول الدوام بنجاح');
    }

    public function store(StoreDoctorScheduleRequest $request)
    {
        $validated = $request->validated();
        $doctorId = (int) $validated['doctor_id'];
        $user = auth()->user();

        // فحص الملكية: منع الطبيب من إضافة أو تعديل جدول طبيب آخر إلا إذا كان Admin
        if (! $user->hasRole('admin', 'api') && $user->id !== $doctorId) {
            return response()->error('عذراً، غير مصرح لك بتعديل جدول دوام طبيب آخر.', 403);
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

        return response()->success($savedSchedules, 'تم حفظ جدول أوقات العمل بنجاح', 201);
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
            return response()->error('Unauthorized to delete this schedule.', 403);
        }

        $schedule->delete();

        return response()->success(null, 'Schedule deleted successfully.');
    }

    /**
     * جلب الأوقات المتاحة للطبيب في تاريخ معين بناءً على مدة الخدمة
     */
    public function getAvailableSlots(Request $request, $doctorId, DoctorScheduleService $scheduleService)
    {
        $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'service_id' => ['required', 'exists:services,id'],
        ]);

        try {
            $slots = $scheduleService->getAvailableSlots($doctorId, $request->date, $request->service_id);

            return response()->success($slots, 'تم جلب الأوقات المتاحة بنجاح');
        } catch (\Exception $e) {
            return response()->success([], $e->getMessage());
        }
    }
}
