<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;

class DoctorServiceController extends Controller
{

    /**
     * جلب قائمة جميع الأطباء في العيادة مع تخصصاتهم
     */
    public function index()
    {
        $doctors = User::role('doctor')
            ->select('id', 'first_name', 'last_name', 'email', 'specialization')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $doctors
        ]);
    }

    /**
     * ربط طبيب بخدمة واحدة أو عدة خدمات مع سعر مخصص (Custom Price)
     */
    public function assignServices(Request $request, $doctorId)
    {
        $doctor = User::role('doctor')->findOrFail($doctorId);

        $request->validate([
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.custom_price' => 'nullable|numeric|min:0',
        ]);

        $syncData = [];
        foreach ($request->services as $item) {
            $syncData[$item['service_id']] = [
                'custom_price' => $item['custom_price'] ?? null
            ];
        }

        // syncWithoutDetaching لمنع مسح الخدمات القديمة وإضافة الجديدة
        $doctor->services()->syncWithoutDetaching($syncData);

        return response()->json([
            'status' => 'success',
            'message' => 'تم ربط الخدمات بالطبيب بنجاح',
            'data' => $doctor->load('services')
        ]);
    }

    /**
     * جلب كافة أطباء خدمة معينة (يفيد المريض عند اختيار خدمة لمعرفة الأطباء المتاحين)
     */
    public function getDoctorsByService($serviceId)
    {
        $service = Service::findOrFail($serviceId);
        $doctors = $service->doctors()->get(['users.id', 'first_name', 'last_name', 'email', 'specialization']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'service' => $service->name,
                'doctors' => $doctors
            ]
        ]);
    }

    /**
     * جلب الخدمات التي يقدمها طبيب معين
     */
    public function getDoctorServices($doctorId)
    {
        $doctor = User::role('doctor')->findOrFail($doctorId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->full_name,
                'specialization' => $doctor->specialization,
                'services' => $doctor->services
            ]
        ]);
    }

    /**
     * إلغاء ربط خدمة معينة عن الطبيب
     */
    public function detachService($doctorId, $serviceId)
    {
        $doctor = User::role('doctor')->findOrFail($doctorId);
        $doctor->services()->detach($serviceId);

        return response()->json([
            'status' => 'success',
            'message' => 'تم إزالة الخدمة عن الطبيب بنجاح'
        ]);
    }
}
