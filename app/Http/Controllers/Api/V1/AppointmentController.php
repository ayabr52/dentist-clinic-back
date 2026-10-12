<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AppointmentController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Appointment::class);

        // جلب البيانات مقسمة لصفحات (مثلاً 15 سجل في كل صفحة) مع فلترة حسب دور المستخدم عبر السيرفس إذا رغبتِ
        $appointments = Appointment::with(['patient', 'doctor', 'service', 'invoice'])
            ->latest()
            ->paginate(15);

        return AppointmentResource::collection($appointments);
    }

    public function store(AppointmentRequest $request)
    {
        $this->authorize('create', Appointment::class);

        try {
            $appointment = $this->appointmentService->bookAppointment(
                $request->validated(),
                $request->user()->id
            );

            // استخدام الـ Resource لإرجاع البيانات بشكل نظيف وموحد
            return $this->successResponse(new AppointmentResource($appointment), 'تم حجز الموعد بنجاح', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        // 1. تفعيل حراسة البوليسي للتأكد أن المستخدم مخول بتعديل هذا الموعد حصراً
        $this->authorize('update', $appointment);

        $request->validate(['status' => 'required|in:confirmed,cancelled,completed']);

        $updated = $this->appointmentService->updateStatus($appointment, $request->status);

        return $this->successResponse(new AppointmentResource($updated), 'تم تحديث حالة الموعد بنجاح');
    }
}
