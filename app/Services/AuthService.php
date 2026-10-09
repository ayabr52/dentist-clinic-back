<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        // ضمان وجود دور patient في قاعدة البيانات ثم إسناده آلياً
        $role = Role::findOrCreate('patient', 'api');
        $user->assignRole($role);

        $token = $user->createToken('ClinicApiToken')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        $token = $user->createToken('ClinicApiToken')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function getCurrentUser($user)
    {
        // يمكنك هنا جلب المستخدم مع العلاقات إذا لزم الأمر
        return $user->load('roles');
    }

    /**
     * حذف/تعطيل حساب مستخدم (Soft Delete)
     */
    public function deleteUser($user): bool
    {
        // إلغاء كافة التوكنات النسيجية الفعالة للمستخدم فوراً حتى لا يستطيع استخدام API
        $user->tokens()->delete();

        // إجراء Soft Delete للمستخدم
        return $user->delete();
    }
}
