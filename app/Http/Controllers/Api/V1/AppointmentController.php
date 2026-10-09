<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    use ApiResponse;

    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function index(Request $request)
    {
        $appointments = $this->appointmentService->getUserAppointments($request->user());
        return $this->successResponse($appointments, 'تم جلب المواعيد بنجاح');
    }

    public function store(AppointmentRequest $request)
    {
        try {
            $appointment = $this->appointmentService->bookAppointment(
                $request->validated(),
                $request->user()->id
            );

            return $this->successResponse($appointment, 'تم حجز الموعد بنجاح', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate(['status' => 'required|in:confirmed,cancelled,completed']);

        $updated = $this->appointmentService->updateStatus($appointment, $request->status);
        return $this->successResponse($updated, 'تم تحديث حالة الموعد بنجاح');
    }
}
