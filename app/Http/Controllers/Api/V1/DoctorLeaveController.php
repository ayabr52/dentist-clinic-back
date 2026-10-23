<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDoctorLeaveRequest;
use App\Models\DoctorLeave;
use Illuminate\Http\Request;

class DoctorLeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = DoctorLeave::with(['doctor' => function ($q) {
            // نأخذ بيانات المستخدم مع دور الطبيب
            $q->select('id', 'first_name', 'last_name', 'email', 'specialization');
        }]);

        // تصفية إجازات طبيب محدد إن وجد
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $query->get()
        ]);
    }

    public function store(StoreDoctorLeaveRequest $request)
    {
        // التحقق من صلاحية الإنشاء عبر الـ Policy
        $this->authorize('create', DoctorLeave::class);

        $validated = $request->validated();
        $user = auth()->user();

        // إذا كان المستخدم طبيباً، نلزم أن تكون الإجازة له حصراً بغض النظر عن المرسل
        if (!$user->hasRole('admin', 'api')) {
            $validated['doctor_id'] = $user->id;
        }

        $leave = DoctorLeave::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تسجيل الإجازة بنجاح',
            'data'    => $leave->load('doctor:id,first_name,last_name,email')
        ], 201);
    }

    public function destroy(DoctorLeave $leave)
    {
        // Authorize using DoctorLeavePolicy
        $this->authorize('delete', $leave);

        $leave->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Doctor leave deleted successfully.'
        ], 200);
    }
}
