<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDoctorScheduleRequest;
use App\Models\DoctorSchedule;
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
            'data'   => $query->get()
        ]);
    }

    public function store(StoreDoctorScheduleRequest $request)
    {
        $validated = $request->validated();
        $doctorId = $validated['doctor_id'];
        $savedSchedules = [];

        DB::transaction(function () use ($validated, $doctorId, &$savedSchedules) {
            foreach ($validated['schedules'] as $item) {
                $savedSchedules[] = DoctorSchedule::updateOrCreate(
                    [
                        'doctor_id'   => $doctorId,
                        'day_of_week' => $item['day_of_week'],
                    ],
                    [
                        'start_time'     => $item['start_time'],
                        'end_time'       => $item['end_time'],
                        'is_working_day' => $item['is_working_day'] ?? true,
                    ]
                );
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حفظ جدول أوقات العمل بنجاح',
            'data'    => $savedSchedules
        ], 201);
    }
}
