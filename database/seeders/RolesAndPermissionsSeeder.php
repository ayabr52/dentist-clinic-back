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

        // 1. إنشاء الصلاحيات الأساسية (شملنا صلاحيات الفواتير والدفعات أيضاً)
        $permissions = [
            'manage-users',
            'manage-services',
            'manage-schedules',
            'view-appointments',
            'create-appointments',
            'manage-medical-records',
            'manage-invoices', // صلاحية إدارة الفواتير
            'manage-payments', // صلاحية تسجيل واستلام الدفعات
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        // 2. إنشاء الأدوار وتعيين الصلاحيات

        // الأدمن (له كافة الصلاحيات)
        $adminRole = Role::findOrCreate('admin', 'api');
        $adminRole->syncPermissions(Permission::all());

        // الطبيب
        $doctorRole = Role::findOrCreate('doctor', 'api');
        $doctorRole->syncPermissions([
            'view-appointments',
            'manage-schedules',
            'manage-medical-records',
        ]);

        // موظف الاستقبال (Receptionist) - مسير المواعيد والصندوق
        $receptionistRole = Role::findOrCreate('receptionist', 'api');
        $receptionistRole->syncPermissions([
            'view-appointments',
            'create-appointments',
            'manage-invoices',
            'manage-payments',
        ]);

        // المريض
        $patientRole = Role::findOrCreate('patient', 'api');
        $patientRole->syncPermissions([
            'view-appointments',
            'create-appointments',
        ]);
    }
}
