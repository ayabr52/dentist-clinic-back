<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Requests\Api\V1\UpdatePaymentStatusRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
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
     * @param StorePaymentRequest $request
     * @return \Illuminate\Http\JsonResponse
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
     * @param Payment $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Payment $payment)
    {
        // $this->authorize('view', $payment);

        return $this->successResponse(
            $payment->load(['invoice.appointments', 'invoice.patient', 'collector']),
            'تم جلب تفاصيل الدفع بنجاح'
        );
    }

    /**
     * Update the specified payment status.
     *
     * @param UpdatePaymentStatusRequest $request
     * @param Payment $payment
     * @return \Illuminate\Http\JsonResponse
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
     * @param Payment $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Payment $payment)
    {
        // $this->authorize('delete', $payment);

        $payment->delete(); // Soft Delete

        return $this->successResponse(null, 'تم حذف السجل المالي بنجاح');
    }
}
