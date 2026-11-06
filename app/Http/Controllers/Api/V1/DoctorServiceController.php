<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorServiceController extends Controller
{
    /**
     * جلب قائمة جميع الأطباء في العيادة مع تخصصاتهم
     */
    public function index(): JsonResponse
    {
        $doctors = User::role('doctor')
            ->select('id', 'first_name', 'last_name', 'email', 'specialization')
            ->get();

        return response()->success(
            UserResource::collection($doctors),
            'تم جلب قائمة الأطباء بنجاح'
        );
    }

    /**
     * ربط طبيب بخدمة واحدة أو عدة خدمات مع سعر مخصص (Custom Price)
     */
    public function assignServices(Request $request, $doctorId): JsonResponse
    {
        $doctor = User::role('doctor')->findOrFail($doctorId);

        $request->validate([
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'exists:services,id'],
            'services.*.custom_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $syncData = [];
        foreach ($request->services as $item) {
            $syncData[$item['service_id']] = [
                'custom_price' => $item['custom_price'] ?? null,
            ];
        }

        // syncWithoutDetaching لمنع مسح الخدمات القديمة وإضافة الجديدة
        $doctor->services()->syncWithoutDetaching($syncData);

        return response()->success(
            new UserResource($doctor->load('services')),
            'تم ربط الخدمات بالطبيب بنجاح'
        );
    }

    /**
     * جلب كافة أطباء خدمة معينة (يفيد المريض عند اختيار خدمة لمعرفة الأطباء المتاحين)
     */
    public function getDoctorsByService($serviceId): JsonResponse
    {
        $service = Service::findOrFail($serviceId);
        $doctors = $service->doctors()->get(['users.id', 'first_name', 'last_name', 'email', 'specialization']);

        return response()->success([
            'service' => $service->name,
            'doctors' => UserResource::collection($doctors),
        ], 'تم جلب أطباء الخدمة بنجاح');
    }

    /**
     * جلب الخدمات التي يقدمها طبيب معين
     */
    public function getDoctorServices($doctorId): JsonResponse
    {
        $doctor = User::role('doctor')->with('services')->findOrFail($doctorId);

        return response()->success(
            new UserResource($doctor),
            'تم جلب خدمات الطبيب بنجاح'
        );
    }

    /**
     * إلغاء ربط خدمة معينة عن الطبيب
     */
    public function detachService($doctorId, $serviceId): JsonResponse
    {
        $doctor = User::role('doctor')->findOrFail($doctorId);
        $doctor->services()->detach($serviceId);

        return response()->success(null, 'تم إزالة الخدمة عن الطبيب بنجاح');
    }
}
