<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Requests\Api\V1\UpdatePaymentStatusRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PaymentController extends Controller
{
    use AuthorizesRequests; // تم إزالة ApiResponse للاعتماد على الـ Macros العالمية

    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Display a listing of the payments.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::with(['invoice.appointments', 'invoice.patient', 'collector']);

        // إذا كان المستخدم مريضاً، جلب دفعاته عبر علاقة الفاتورة حصراً
        if ($user->hasRole('patient')) {
            $query->whereHas('invoice', function ($q) use ($user) {
                $q->where('patient_id', $user->id);
            });
        } else {
            // للأدمن أو الطبيب: الفلترة حسب الـ patient_id عبر علاقة الفاتورة إن وجد
            if ($request->filled('patient_id')) {
                $query->whereHas('invoice', function ($q) use ($request) {
                    $q->where('patient_id', $request->patient_id);
                });
            }
        }

        // الفلترة حسب الحالة تتم عبر علاقة الفاتورة لأنها موجودة في invoices وليس payments
        if ($request->filled('status')) {
            $query->whereHas('invoice', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $payments = $query->latest()->paginate(10);

        return PaymentResource::collection($payments);
    }

    /**
     * Store a newly created payment in storage.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->createPayment($request->validated());

        return response()->success(
            new PaymentResource($payment),
            'تم إنشاء عملية الدفع بنجاح',
            201
        );
    }

    /**
     * Display the specified payment.
     *
     * @return PaymentResource
     */
    public function show(Payment $payment)
    {
        // تفعيل التحقق من الصلاحية أمنياً
        $this->authorize('view', $payment);

        return new PaymentResource(
            $payment->load(['invoice.appointments', 'invoice.patient', 'collector'])
        );
    }

    /**
     * Update the specified payment status.
     */
    public function updateStatus(UpdatePaymentStatusRequest $request, Payment $payment): JsonResponse
    {
        $updatedPayment = $this->paymentService->updateStatus(
            $payment,
            $request->validated()['status']
        );

        return response()->success(
            new PaymentResource($updatedPayment),
            'تم تحديث حالة الدفع بنجاح'
        );
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete(); // Soft Delete

        return response()->success(null, 'تم حذف السجل المالي بنجاح');
    }
}
