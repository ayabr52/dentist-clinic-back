<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicalRecordRequest;
use App\Models\MedicalRecord;
use App\Services\MedicalRecordService;
use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    protected MedicalRecordService $medicalRecordService;

    public function __construct(MedicalRecordService $medicalRecordService)
    {
        $this->medicalRecordService = $medicalRecordService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', MedicalRecord::class);
        $records = $this->medicalRecordService->getPatientRecords($request->user());

        return $this->successResponse($records, 'تم جلب الملفات الطبية بنجاح');
    }

    /**
     * Store a newly created medical record.
     *
     * Validates the incoming request, checks policy authorization, and saves the record
     * together with any uploaded images for the authenticated doctor.
     *
     * @param  MedicalRecordRequest  $request  The validated request payload containing medical record data and optional uploaded images.
     * @return JsonResponse JSON response with the created medical record.
     *
     * @throws AuthorizationException If the authenticated user is not authorized to create a record.
     */
    public function store(MedicalRecordRequest $request)
    {
        $this->authorize('create', MedicalRecord::class);

        // جلب الملفات المرفوعة إن وجدت (تكون فارغة إذا لم يرفع الطبيب صوراً)
        $images = $request->file('images', []);

        // استدعاء السيرفس مرة واحدة فقط وتمرير البيانات والملفات
        $record = $this->medicalRecordService->storeRecord(
            $request->validated(),
            auth()->id(),
            $images
        );

        return $this->successResponse($record, 'تم إنشاء السجل الطبي بنجاح', 201);
    }
}
