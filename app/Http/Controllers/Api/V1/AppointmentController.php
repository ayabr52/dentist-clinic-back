<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @class AppointmentController
 */
class AppointmentController extends Controller
{
    use AuthorizesRequests;

    protected AppointmentService $appointmentService;

    /**
     * إنشاء مثيل جديد من الكونترولر وحقن خدمة المواعيد.
     */
    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * جلب قائمة المواعيد مع الترقيم والفلترة بناءً على دور المستخدم الحالي.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $user = $request->user();

        $query = Appointment::with(['patient', 'doctor', 'service', 'invoice']);

        if ($user->hasRole('patient')) {
            $query->where('patient_id', $user->id);
        } elseif ($user->hasRole('doctor')) {
            $query->where('doctor_id', $user->id);
        }

        $appointments = $query->latest()->paginate(15);

        return response()->success(
            AppointmentResource::collection($appointments),
            'تم جلب قائمة المواعيد بنجاح'
        );
    }

    /**
     * حجز موعد طبي جديد للمريض مع التحقق من الأهلية والتداخلات الزمنية.
     *
     * @throws AuthorizationException
     */
    public function store(AppointmentRequest $request): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        try {
            $appointment = $this->appointmentService->bookAppointment(
                $request->validated(),
                $request->user()->id
            );

            return response()->success(
                new AppointmentResource($appointment),
                'تم حجز الموعد بنجاح',
                201
            );
        } catch (\Exception $e) {
            return response()->error($e->getMessage(), 422);
        }
    }

    /**
     * تحديث حالة الموعد (تأكيد، إلغاء، اكتمال) وفقاً لصلاحيات الدور.
     *
     * @throws AuthorizationException
     */
    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        $user = $request->user();

        $allowedStatuses = ['cancelled'];

        if ($user->hasRole('admin') || $user->hasRole('doctor')) {
            $allowedStatuses = ['confirmed', 'cancelled', 'completed'];
        }

        $request->validate([
            'status' => 'required|in:'.implode(',', $allowedStatuses),
        ]);

        $updated = $this->appointmentService->updateStatus($appointment, $request->status);

        return response()->success(
            new AppointmentResource($updated),
            'تم تحديث حالة الموعد بنجاح'
        );
    }
}
