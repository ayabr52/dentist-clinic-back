<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDoctorScheduleRequest;
use App\Models\DoctorSchedule;
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
}
