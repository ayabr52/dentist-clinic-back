<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDoctorLeaveRequest;
use App\Models\DoctorLeave;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DoctorLeaveController extends Controller
{
    use AuthorizesRequests;

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

        return response()->success($query->get(), 'تم جلب قائمة الإجازات بنجاح');
    }

    public function store(StoreDoctorLeaveRequest $request)
    {
        // التحقق من صلاحية الإنشاء عبر الـ Policy
        $this->authorize('create', DoctorLeave::class);

        $validated = $request->validated();
        $user = auth()->user();

        // إذا كان المستخدم طبيباً، نلزم أن تكون الإجازة له حصراً بغض النظر عن المرسل
        if (! $user->hasRole('admin', 'api')) {
            $validated['doctor_id'] = $user->id;
        }

        $leave = DoctorLeave::create($validated);

        return response()->success(
            $leave->load('doctor:id,first_name,last_name,email'),
            'تم تسجيل الإجازة بنجاح',
            201
        );
    }

    public function destroy(DoctorLeave $leave)
    {
        // Authorize using DoctorLeavePolicy
        $this->authorize('delete', $leave);

        $leave->delete();

        return response()->success(null, 'Doctor leave deleted successfully.');
    }
}
