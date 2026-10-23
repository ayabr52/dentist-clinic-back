<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\MedicalRecordController;
use App\Http\Controllers\Api\V1\DoctorScheduleController;
use App\Http\Controllers\Api\V1\DoctorLeaveController;
use App\Http\Controllers\Api\V1\DoctorServiceController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;
use Spatie\Permission\Middleware\RoleMiddleware;

/**
 * ============================================================================
 * Clinic Management System API Routes (v1)
 * ============================================================================
 */

Route::prefix('v1')->group(function () {

    /**
     * Public Authentication Routes
     */
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    /**
     * Public Services & Doctors Filtering Routes
     */
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{service_id}/doctors', [DoctorServiceController::class, 'getDoctorsByService']);
    Route::get('/doctors/{doctor_id}/services', [DoctorServiceController::class, 'getDoctorServices']);
    Route::get('/doctors', [DoctorServiceController::class, 'index']);

    /**
     * Protected Routes (Sanctum)
     */
    Route::middleware('auth:sanctum')->group(function () {

        // Account Management
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'me']);

        // Appointments Management
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::post('/appointments', [AppointmentController::class, 'store']);
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);

        // Medical Records
        Route::get('/medical-records', [MedicalRecordController::class, 'index']);
        Route::post('/medical-records', [MedicalRecordController::class, 'store']);


        /**
         * Doctor & Admin Protected Routes
         */
        Route::middleware([RoleMiddleware::using('admin|doctor|receptionist', 'sanctum')])->group(function () {
            // Doctor Schedules
            Route::get('/doctor-schedules', [DoctorScheduleController::class, 'index']);
            Route::post('/doctor-schedules', [DoctorScheduleController::class, 'store']);
            Route::delete('/doctor-schedules/{schedule}', [DoctorScheduleController::class, 'destroy']);

            // Doctor Leaves
            Route::get('/doctor-leaves', [DoctorLeaveController::class, 'index']);
            Route::post('/doctor-leaves', [DoctorLeaveController::class, 'store']);
            Route::delete('/doctor-leaves/{leave}', [DoctorLeaveController::class, 'destroy']);
        });


        /**
         * Financial, Reception & Management Routes (Invoices, Payments & Admin)
         */
        Route::middleware([RoleMiddleware::using('admin|doctor|receptionist', 'sanctum')])->group(function () {

            // Invoices Management
            Route::get('/invoices', [InvoiceController::class, 'index']);
            Route::get('/invoices/{id}', [InvoiceController::class, 'show']);

            // Payments Management
            Route::get('/payments', [PaymentController::class, 'index']);
            Route::post('/payments', [PaymentController::class, 'store']);
            Route::get('/payments/{id}', [PaymentController::class, 'show']);
            Route::patch('/payments/{payment}/status', [PaymentController::class, 'updateStatus']);
            // Services Administration (CRUD)
            Route::post('/services', [ServiceController::class, 'store']);
            Route::put('/services/{service}', [ServiceController::class, 'update']);
            Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

            // Doctor Services Assignment
            Route::post('/doctors/{doctor_id}/services', [DoctorServiceController::class, 'assignServices']);
            Route::delete('/doctors/{doctor_id}/services/{service_id}', [DoctorServiceController::class, 'detachService']);

            // User Administration
            Route::delete('/users/{user}', [AuthController::class, 'destroy']);
        });
    });
});
