<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. تشغيل Seeder الأدوار والصلاحيات أولاً
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. إنشاء حساب Admin
        $admin = User::create([
            'first_name' => 'Aya',
            'last_name'  => 'Barghouth',
            'email'      => 'admin@clinic.com',
            'password'   => Hash::make('password123'),
        ]);
        $admin->assignRole('admin');

        // 3. إنشاء حساب Doctor
        $doctor = User::create([
            'first_name'     => 'Ahmad',
            'last_name'      => 'Ali',
            'email'          => 'doctor@clinic.com',
            'phone'          => '0912345678',
            'specialization' => 'General Dentistry',
            'password'       => Hash::make('password123'),
        ]);
        $doctor->assignRole('doctor');

        // 4. إنشاء حساب Patient
        $patient = User::create([
            'first_name' => 'Sara',
            'last_name'  => 'Khalid',
            'email'      => 'patient@clinic.com',
            'phone'      => '0987654321',
            'password'   => Hash::make('password123'),
        ]);
        $patient->assignRole('patient');

        // 5. إنشاء خدمات طبية
        $service1 = Service::create([
            'name'             => 'كشف عام',
            'description'      => 'معاينة وتشخيص مبدئي للحالة الطبية',
            'price'            => 100.00,
            'duration_minutes' => 30,
            'is_active'        => true,
        ]);

        $service2 = Service::create([
            'name'             => 'تنظيف أسنان',
            'description'      => 'إزالة الجير والعناية باللثة',
            'price'            => 250.00,
            'duration_minutes' => 45,
            'is_active'        => true,
        ]);

        // 6. ربط الطبيب بالخدمات مع سعر مخصص (Custom Price)
        $doctor->services()->attach([
            $service1->id => ['custom_price' => 120.00], // سعر مخصص للطبيب للكشف العام
            $service2->id => ['custom_price' => null],   // يعتمد السعر الافتراضي للخدمة (250.00)
        ]);
    }
}
