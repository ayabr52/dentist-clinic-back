<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\ServiceManagementService;
use Illuminate\Http\JsonResponse;

/**
 * @class ServiceController
 */
class ServiceController extends Controller
{
    protected ServiceManagementService $serviceManager;

    /**
     * إنشاء مثيل جديد من الكونترولر وحقن خدمة إدارة الخدمات.
     */
    public function __construct(ServiceManagementService $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * جلب قائمة الخدمات الطبية النشطة.
     */
    public function index(): JsonResponse
    {
        $services = $this->serviceManager->getAllActive();

        // استخدام ServiceResource للـ Collection
        return response()->success(
            ServiceResource::collection($services),
            'تم جلب قائمة الخدمات بنجاح'
        );
    }

    /**
     * إنشاء خدمة طبية جديدة.
     */
    public function store(ServiceRequest $request): JsonResponse
    {
        $service = $this->serviceManager->createService($request->validated());

        // استخدام ServiceResource لعنصر واحد فردي
        return response()->success(
            new ServiceResource($service),
            'تم إنشاء الخدمة بنجاح',
            201
        );
    }

    /**
     * تحديث بيانات خدمة طبية قائمة.
     */
    public function update(ServiceRequest $request, Service $service): JsonResponse
    {
        $updatedService = $this->serviceManager->updateService($service, $request->validated());

        // استخدام ServiceResource بعد التحديث
        return response()->success(
            new ServiceResource($updatedService),
            'تم تحديث الخدمة بنجاح'
        );
    }

    /**
     * حذف خدمة طبية.
     */
    public function destroy(Service $service): JsonResponse
    {
        $this->serviceManager->deleteService($service);

        return response()->success(null, 'تم حذف الخدمة بنجاح');
    }
}
