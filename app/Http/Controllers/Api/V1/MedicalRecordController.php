<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicalRecordRequest;
use App\Models\MedicalRecord;
use App\Services\MedicalRecordService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @class MedicalRecordController
 */
class MedicalRecordController extends Controller
{
    use AuthorizesRequests;

    protected MedicalRecordService $medicalRecordService;

    /**
     * إنشاء مثيل جديد من الكونترولر وحقن خدمة السجلات الطبية.
     */
    public function __construct(MedicalRecordService $medicalRecordService)
    {
        $this->medicalRecordService = $medicalRecordService;
    }

    /**
     * جلب السجلات الطبية الخاصة بالمستخدم الحالي.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MedicalRecord::class);

        $records = $this->medicalRecordService->getPatientRecords($request->user());

        return response()->success($records, 'تم جلب الملفات الطبية بنجاح');
    }

    /**
     * تخزين سجل طبي جديد مع المرفقات وصور التقارير.
     *
     * Validates the incoming request, checks policy authorization, and saves the record
     * together with any uploaded images for the authenticated doctor.
     *
     * @throws AuthorizationException
     */
    public function store(MedicalRecordRequest $request): JsonResponse
    {
        $this->authorize('create', MedicalRecord::class);

        // جلب الملفات المرفوعة إن وجدت (تكون فارغة إذا لم يرفع الطبيب صوراً)
        $images = $request->file('images', []);

        // استدعاء السيرفس وتمرير البيانات والملفات
        $record = $this->medicalRecordService->storeRecord(
            $request->validated(),
            $request->user()->id,
            $images
        );

        return response()->success($record, 'تم إنشاء السجل الطبي بنجاح', 201);
    }
}
