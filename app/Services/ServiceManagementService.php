<?php

namespace App\Services;

use App\Models\Service;

class ServiceManagementService
{
    public function getAllActive()
    {
        return Service::where('is_active', true)->get();
    }

    public function createService(array $data): Service
    {
        return Service::create($data);
    }

    public function updateService(Service $service, array $data): Service
    {
        $service->update($data);

        return $service;
    }

    public function deleteService(Service $service): void
    {
        $service->delete();
    }
}
