<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Requests\Api\V1\UpdatePaymentStatusRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of the payments.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::with(['invoice.appointments', 'invoice.patient', 'collector']);

        // إذا كان المستخدم مريضاً، جلب فواتيره الخاصة حصراً بناءً على دوره
        if ($user->hasRole('patient')) {
            $query->where('patient_id', $user->id);
        } else {
            // للأدمن أو الطبيب: السماح بالفلترة حسب الـ patient_id إن وجد
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->get();

        return $this->successResponse($payments, 'تم جلب الدفعات المالية بنجاح');
    }

    /**
     * Store a newly created payment in storage.
     *
     * @return JsonResponse
     */
    public function store(StorePaymentRequest $request)
    {
        // التحقق من الصلاحية (مثلاً للأدمن فقط أو الموظف المخول)
        // $this->authorize('create', Payment::class);

        $payment = $this->paymentService->createPayment($request->validated());

        return $this->successResponse($payment, 'تم إنشاء عملية الدفع بنجاح', 201);
    }

  /**
     * Display the specified payment.
     *
     * @return JsonResponse
     */
    public function show(Payment $payment)
    {
        // تفعيل التحقق من الصلاحية أمنياً
        $this->authorize('view', $payment);

        // استخدام Resource لتنظيم هيكل الـ JSON المعادة
        return new PaymentResource(
            $payment->load(['invoice.appointments', 'invoice.patient', 'collector'])
        );
    }

    /**
     * Update the specified payment status.
     *
     * @return JsonResponse
     */
    public function updateStatus(UpdatePaymentStatusRequest $request, Payment $payment)
    {
        // $this->authorize('update', $payment);

        $updatedPayment = $this->paymentService->updateStatus(
            $payment,
            $request->validated()['status'],
            $request->validated()['payment_method'] ?? null
        );

        return $this->successResponse($updatedPayment, 'تم تحديث حالة الدفع بنجاح');
    }

    /**
     * Remove the specified payment from storage.
     *
     * @return JsonResponse
     */
    public function destroy(Payment $payment)
    {
        // $this->authorize('delete', $payment);

        $payment->delete(); // Soft Delete

        return $this->successResponse(null, 'تم حذف السجل المالي بنجاح');
    }
}
