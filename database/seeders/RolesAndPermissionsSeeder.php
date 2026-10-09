<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // إعادة تفريغ كاش الصلاحيات
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. إنشاء الصلاحيات الأساسية
        $permissions = [
            'manage-users',
            'manage-services',
            'manage-schedules',
            'view-appointments',
            'create-appointments',
            'manage-medical-records',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        // 2. إنشاء الأدوار وتعيين الصلاحيات
        $adminRole = Role::findOrCreate('admin', 'api');
        $adminRole->syncPermissions(Permission::all());

        $doctorRole = Role::findOrCreate('doctor', 'api');
        $doctorRole->syncPermissions([
            'view-appointments',
            'manage-schedules',
            'manage-medical-records',
        ]);

        $patientRole = Role::findOrCreate('patient', 'api');
        $patientRole->syncPermissions([
            'view-appointments',
            'create-appointments',
        ]);
    }
}
