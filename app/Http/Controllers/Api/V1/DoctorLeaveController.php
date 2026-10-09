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
        $leave = DoctorLeave::create($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'تم تسجيل الإجازة بنجاح',
            'data'    => $leave->load('doctor:id,first_name,last_name,email')
        ], 201);
    }

    public function destroy($id)
    {
        $leave = DoctorLeave::find($id);

        if (!$leave) {
            return response()->json([
                'status'  => 'error',
                'message' => 'الإجازة غير موجودة'
            ], 404);
        }

        $leave->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'تم حذف الإجازة بنجاح'
        ]);
    }
}
