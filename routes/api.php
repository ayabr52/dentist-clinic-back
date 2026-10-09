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
use App\Http\Controllers\Api\V1\PaymentController;

/**
 * ============================================================================
 * Clinic Management System API Routes (v1)
 * ============================================================================
 *
 * @title        Clinic Management System API Documentation
 * @version      1.0.0
 * @description  واجهة برمجة التطبيقات لإدارة العيادات (المصادقة، الخدمات، الجداول، المواعيد، السجلات الطبية، والمدفوعات)
 * @prefix       api/v1
 */

Route::prefix('v1')->group(function () {

    /**
     * ------------------------------------------------------------------------
     * Public Authentication Routes (المسارات العامة للمصادقة)
     * ------------------------------------------------------------------------
     */

    /**
     * @router   POST api/v1/register
     * @summary  تسجيل حساب مريض جديد في النظام
     * @tags     Authentication
     * @param    \App\Http\Requests\RegisterRequest $request
     * @return   \Illuminate\Http\JsonResponse
     */
    Route::post('/register', [AuthController::class, 'register']);

    /**
     * @router   POST api/v1/login
     * @summary  تسجيل الدخول وإصدار رمز الوصول (Sanctum Token)
     * @tags     Authentication
     * @param    \Illuminate\Http\Request $request
     * @return   \Illuminate\Http\JsonResponse
     */
    Route::post('/login', [AuthController::class, 'login']);


    /**
     * ------------------------------------------------------------------------
     * Public Services & Doctors Filtering Routes (المسارات العامة للخدمات والفلترة)
     * ------------------------------------------------------------------------
     */

    /**
     * @router   GET api/v1/services
     * @summary  عرض قائمة جميع الخدمات الطبية المتاحة في العيادة
     * @tags     Services
     * @return   \Illuminate\Http\JsonResponse
     */
    Route::get('/services', [ServiceController::class, 'index']);

    /**
     * @router   GET api/v1/services/{service_id}/doctors
     * @summary  جلب الأطباء الذين يقدمون خدمة معينة
     * @tags     Doctor Services
     * @param    int $service_id معرف الخدمة
     * @return   \Illuminate\Http\JsonResponse
     */
    Route::get('/services/{service_id}/doctors', [DoctorServiceController::class, 'getDoctorsByService']);

    /**
     * @router   GET api/v1/doctors/{doctor_id}/services
     * @summary  جلب كافة الخدمات التي يقدمها طبيب محدد
     * @tags     Doctor Services
     * @param    int $doctor_id معرف الطبيب
     * @return   \Illuminate\Http\JsonResponse
     */
    Route::get('/doctors/{doctor_id}/services', [DoctorServiceController::class, 'getDoctorServices']);


    /**
     * ------------------------------------------------------------------------
     * Protected Routes (المسارات المحمية - تتطلب Sanctum Bearer Token)
     * ------------------------------------------------------------------------
     */
    Route::middleware('auth:sanctum')->group(function () {

        /**
         * --- Account Management (إدارة الحساب والجلسات) ---
         */

        /**
         * @router   POST api/v1/logout
         * @summary  إلغاء جلسة المستخدم الحالية وحذف التوكن
         * @tags     Authentication
         * @security BearerAuth
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::post('/logout', [AuthController::class, 'logout']);

        /**
         * @router   GET api/v1/user
         * @summary  جلب بيانات المستخدم المسجل حالياً مع الأدوار والصلاحيات
         * @tags     Authentication
         * @security BearerAuth
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::get('/user', [AuthController::class, 'me']);

        /**
         * @router   DELETE api/v1/users/{user}
         * @summary  تعطيل أو حذف حساب مستخدم محدد (Soft Delete)
         * @tags     User Administration
         * @security BearerAuth
         * @param    int $user معرف المستخدم
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::delete('/users/{user}', [AuthController::class, 'destroy']);


        /**
         * --- Doctor Services Assignment (إسناد وربط الخدمات بالأطباء) ---
         */

        /**
         * @router   POST api/v1/doctors/{doctor_id}/services
         * @summary  ربط الطبيب بكتالوج الخدمات مع تخصيص الأسعار (Custom Price)
         * @tags     Doctor Services
         * @security BearerAuth
         * @param    int $doctor_id معرف الطبيب
         * @param    \Illuminate\Http\Request $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::post('/doctors/{doctor_id}/services', [DoctorServiceController::class, 'assignServices']);

        /**
         * @router   DELETE api/v1/doctors/{doctor_id}/services/{service_id}
         * @summary  إزالة ربط خدمة معينة عن طبيب محدد
         * @tags     Doctor Services
         * @security BearerAuth
         * @param    int $doctor_id معرف الطبيب
         * @param    int $service_id معرف الخدمة المراد إزالتها
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::delete('/doctors/{doctor_id}/services/{service_id}', [DoctorServiceController::class, 'detachService']);


        /**
         * --- Appointments Management (إدارة المواعيد) ---
         */

        /**
         * @router   GET api/v1/appointments
         * @summary  عرض قائمة المواعيد المفلترة بحسب المستخدم الحالي
         * @tags     Appointments
         * @security BearerAuth
         * @param    \Illuminate\Http\Request $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::get('/appointments', [AppointmentController::class, 'index']);

        /**
         * @router   POST api/v1/appointments
         * @summary  حجز موعد جديد في العيادة
         * @tags     Appointments
         * @security BearerAuth
         * @param    \App\Http\Requests\StoreAppointmentRequest $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::post('/appointments', [AppointmentController::class, 'store']);

        /**
         * @router   PATCH api/v1/appointments/{appointment}/status
         * @summary  تحديث حالة الموعد (مؤكد، ملغى، مكتمل)
         * @tags     Appointments
         * @security BearerAuth
         * @param    int $appointment معرف الموعد
         * @param    \Illuminate\Http\Request $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);


        /**
         * --- Medical Records (السجلات الطبية) ---
         */

        /**
         * @router   GET api/v1/medical-records
         * @summary  عرض قائمة السجلات والملفات الطبية للمريض
         * @tags     Medical Records
         * @security BearerAuth
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::get('/medical-records', [MedicalRecordController::class, 'index']);

        /**
         * @router   POST api/v1/medical-records
         * @summary  إضافة تشخيص وسجل طبي جديد لمريض بعد الموعد
         * @tags     Medical Records
         * @security BearerAuth
         * @param    \App\Http\Requests\StoreMedicalRecordRequest $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::post('/medical-records', [MedicalRecordController::class, 'store']);


        /**
         * --- Doctor Schedules (جداول أوقات عمل الأطباء) ---
         */

        /**
         * @router   GET api/v1/doctor-schedules
         * @summary  عرض جداول وساعات عمل الأطباء الأسبوعية
         * @tags     Doctor Schedules
         * @security BearerAuth
         * @param    \Illuminate\Http\Request $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::get('/doctor-schedules', [DoctorScheduleController::class, 'index']);

        /**
         * @router   POST api/v1/doctor-schedules
         * @summary  إضافة أو تحديث فترات العمل دفعة واحدة (Batch Scheduling)
         * @tags     Doctor Schedules
         * @security BearerAuth
         * @param    \App\Http\Requests\StoreDoctorScheduleRequest $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::post('/doctor-schedules', [DoctorScheduleController::class, 'store']);

        /**
         * @router   DELETE api/v1/doctor-schedules/{schedule}
         * @summary  حذف فترة عمل محددة للطبيب
         * @tags     Doctor Schedules
         * @security BearerAuth
         * @param    int $schedule معرف الجدول
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::delete('/doctor-schedules/{schedule}', [DoctorScheduleController::class, 'destroy']);


        /**
         * --- Doctor Leaves (إجازات الأطباء) ---
         */

        /**
         * @router   GET api/v1/doctor-leaves
         * @summary  عرض قائمة إجازات الأطباء المسجلة
         * @tags     Doctor Leaves
         * @security BearerAuth
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::get('/doctor-leaves', [DoctorLeaveController::class, 'index']);

        /**
         * @router   POST api/v1/doctor-leaves
         * @summary  تسجيل طلب إجازة جديد لطبيب
         * @tags     Doctor Leaves
         * @security BearerAuth
         * @param    \App\Http\Requests\StoreDoctorLeaveRequest $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::post('/doctor-leaves', [DoctorLeaveController::class, 'store']);

        /**
         * @router   DELETE api/v1/doctor-leaves/{leave}
         * @summary  إلغاء أو حذف إجازة طبيب
         * @tags     Doctor Leaves
         * @security BearerAuth
         * @param    int $leave معرف الإجازة
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::delete('/doctor-leaves/{leave}', [DoctorLeaveController::class, 'destroy']);


        /**
         * --- Services Administration (إدارة كتالوج الخدمات للآدمن) ---
         */

        /**
         * @router   POST api/v1/services
         * @summary  إنشاء خدمة طبية جديدة في العيادة
         * @tags     Services Administration
         * @security BearerAuth
         * @param    \App\Http\Requests\StoreServiceRequest $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::post('/services', [ServiceController::class, 'store']);

        /**
         * @router   PUT api/v1/services/{service}
         * @summary  تعديل بيانات خدمة طبية موجودة
         * @tags     Services Administration
         * @security BearerAuth
         * @param    int $service معرف الخدمة
         * @param    \App\Http\Requests\UpdateServiceRequest $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::put('/services/{service}', [ServiceController::class, 'update']);

        /**
         * @router   DELETE api/v1/services/{service}
         * @summary  حذف خدمة طبية من النظام (Soft Delete)
         * @tags     Services Administration
         * @security BearerAuth
         * @param    int $service معرف الخدمة
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);


        /**
         * --- Payments Management (إدارة المدفوعات والشؤون المالية) ---
         */

        /**
         * @router   Resource api/v1/payments
         * @summary  مسارات CRUD الكاملة لإدارة فواتير ومعاملات الدفع
         * @tags     Payments
         * @security BearerAuth
         */
        Route::apiResource('payments', PaymentController::class);

        /**
         * @router   PATCH api/v1/payments/{payment}/status
         * @summary  تحديث حالة الفاتورة أو عملية الدفع فقط (Paid, Pending, Failed)
         * @tags     Payments
         * @security BearerAuth
         * @param    int $payment معرف الفاتورة
         * @param    \Illuminate\Http\Request $request
         * @return   \Illuminate\Http\JsonResponse
         */
        Route::patch('payments/{payment}/status', [PaymentController::class, 'updateStatus']);
    });
});
