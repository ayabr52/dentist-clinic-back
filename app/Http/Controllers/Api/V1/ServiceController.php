<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceRequest;
use App\Models\Service;
use App\Services\ServiceManagementService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    protected ServiceManagementService $serviceManager;

    public function __construct(ServiceManagementService $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function index()
    {
        $services = $this->serviceManager->getAllActive();

        return response()->json([
            'status' => 'success',
            'data' => $services
        ]);
    }

    public function store(ServiceRequest $request)
    {
        $service = $this->serviceManager->createService($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء الخدمة بنجاح',
            'data' => $service
        ], 201);
    }

    public function update(ServiceRequest $request, Service $service)
    {
        $updatedService = $this->serviceManager->updateService($service, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث الخدمة بنجاح',
            'data' => $updatedService
        ]);
    }

    public function destroy(Service $service)
    {
        $this->serviceManager->deleteService($service);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف الخدمة بنجاح'
        ]);
    }
}
