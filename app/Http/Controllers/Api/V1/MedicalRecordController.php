<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MedicalRecordRequest;
use App\Services\MedicalRecordService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    use ApiResponse;

    protected MedicalRecordService $medicalRecordService;

    public function __construct(MedicalRecordService $medicalRecordService)
    {
        $this->medicalRecordService = $medicalRecordService;
    }

    public function index(Request $request)
    {
        $records = $this->medicalRecordService->getPatientRecords($request->user());
        return $this->successResponse($records, 'تم جلب الملفات الطبية بنجاح');
    }

    public function store(MedicalRecordRequest $request)
    {
        $record = $this->medicalRecordService->storeRecord($request->validated(), $request->user()->id);
        return $this->successResponse($record, 'تم إنشاء السجل الطبي بنجاح', 201);
    }
}
