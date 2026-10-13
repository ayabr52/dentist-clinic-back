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

        $user = $request->user();

        // بناء الاستعلام الأساسي مع العلاقات المطلوبة
        $query = Appointment::with(['patient', 'doctor', 'service', 'invoice']);

        // فلترة النتائج حسب دور المستخدم الحقيقي لحماية الخصوصية
        if ($user->hasRole('patient')) {
            $query->where('patient_id', $user->id);
        } elseif ($user->hasRole('doctor')) {
            $query->where('doctor_id', $user->id);
        }
        // الـ admin أو الأدوار الإدارية تستطيع رؤية كافة المواعيد تلقائياً

        $appointments = $query->latest()->paginate(15);

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
        $this->authorize('update', $appointment);

        $user = $request->user();

        // تحديد الحالات المسموح إرسالها بناءً على دور المستخدم
        $allowedStatuses = ['cancelled']; // الافتراضي المسموح للمريض

        if ($user->hasRole('admin') || $user->hasRole('doctor')) {
            $allowedStatuses = ['confirmed', 'cancelled', 'completed'];
        }

        $request->validate([
            'status' => 'required|in:' . implode(',', $allowedStatuses)
        ]);

        $updated = $this->appointmentService->updateStatus($appointment, $request->status);

        return $this->successResponse(new AppointmentResource($updated), 'تم تحديث حالة الموعد بنجاح');
    }
}
