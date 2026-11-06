<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DoctorLeaveController;
use App\Http\Controllers\Api\V1\DoctorScheduleController;
use App\Http\Controllers\Api\V1\DoctorServiceController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\MedicalRecordController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ServiceController;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

/**
 * ============================================================================
 * Clinic Management System API Routes (v1) - Resource Routing Optimized
 * ============================================================================
 */
Route::prefix('v1')->group(function () {

    /**
     * Public Authentication Routes (مع حماية ضد هجمات القوة الغاشمة)
     */
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    /**
     * Public Services & Doctors Filtering Routes (متاحة للزوار والأدوار كافة)
     */
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{service_id}/doctors', [DoctorServiceController::class, 'getDoctorsByService']);
    Route::get('/doctors/{doctor_id}/services', [DoctorServiceController::class, 'getDoctorServices']);
    Route::get('/doctors', [DoctorServiceController::class, 'index']);
    Route::get('/doctors/{doctor}/available-slots', [DoctorScheduleController::class, 'getAvailableSlots']);

    /**
     * Protected Routes (Sanctum)
     */
    Route::middleware('auth:sanctum')->group(function () {

        // Account Management
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'me']);
        Route::delete('/user/account', [AuthController::class, 'deleteMyAccount']);

        // Appointments Management (استخدام apiResource مع مسار مخصص لتحديث الحالة)
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
        Route::apiResource('appointments', AppointmentController::class)->only(['index', 'store', 'show']);

        // Medical Records Management
        Route::apiResource('medical-records', MedicalRecordController::class)->only(['index', 'store', 'show']);

        /**
         * Doctor, Receptionist & Admin Protected Routes
         */
        Route::middleware([RoleMiddleware::using('admin|doctor|receptionist', 'sanctum')])->group(function () {

            // Doctor Schedules Management
            Route::apiResource('doctor-schedules', DoctorScheduleController::class)->only(['index', 'store', 'destroy']);

            // Doctor Leaves Management
            Route::apiResource('doctor-leaves', DoctorLeaveController::class)
                ->parameters(['doctor-leaves' => 'leave'])
                ->only(['index', 'store', 'destroy']);

            // Invoices Management
            Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);

            // Payments Management
            Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);

            // Services Administration (إدارة الخدمات الكاملة للمدير والصلاحيات العليا باستثناء الاستعراض العام)
            Route::apiResource('services', ServiceController::class)->except(['index', 'show']);

            // Doctor Services Assignment (علاقات الـ Pivot المخصصة)
            Route::post('/doctors/{doctor_id}/services', [DoctorServiceController::class, 'assignServices']);
            Route::delete('/doctors/{doctor_id}/services/{service_id}', [DoctorServiceController::class, 'detachService']);

            // User Administration
            Route::delete('/users/{user}', [AuthController::class, 'destroy']);
        });
    });
});
