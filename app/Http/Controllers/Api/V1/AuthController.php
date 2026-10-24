<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest; // لو أنشأتيه بنفس الطريقة
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'تم إنشاء الحساب بنجاح',
            'data' => $result,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! auth()->attempt($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'بيانات الاعتماد غير صحيحة',
            ], 401);
        }

        $user = auth()->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * جلب بيانات المستخدم المسجل حالياً عبر الـ AuthService وتنسيقها بالـ UserResource
     */
    public function me(Request $request)
    {
        // 1. جلب البيانات عبر الـ Service
        $user = $this->authService->getCurrentUser($request->user());

        // 2. إرجاعها منسقة ونظيفة عبر الـ Resource
        return response()->json([
            'status' => 'success',
            'data' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    /**
     * حذف المستخدم لحسابه الشخصي
     */
    public function deleteMyAccount(Request $request)
    {
        $user = $request->user();

        $this->authService->deleteUser($user);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حذف وتجميد حسابك بنجاح',
        ]);
    }
}
