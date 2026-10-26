<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

/**
 * @class ServiceManagementService
 */
class ServiceManagementService
{
    /**
     * جلب كافة الخدمات الطبية النشطة في النظام.
     */
    public function getAllActive(): Collection
    {
        return Service::where('is_active', true)->get();
    }

    /**
     * إنشاء خدمة طبية جديدة.
     */
    public function createService(array $data): Service
    {
        return Service::create($data);
    }

    /**
     * تحديث بيانات خدمة طبية قائمة.
     */
    public function updateService(Service $service, array $data): Service
    {
        $service->update($data);

        return $service;
    }

    /**
     * حذف خدمة طبية من النظام.
     */
    public function deleteService(Service $service): void
    {
        $service->delete();
    }
}
