<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // يمكن تقييدها لاحقاً لRoles محددة
    }

    public function rules(): array
    {
        return [
            'doctor_id'                 => ['required', 'exists:users,id'],
            'schedules'                 => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week'    => ['required', 'integer', 'between:0,6'],
            'schedules.*.start_time'     => ['required', 'date_format:H:i'],
            'schedules.*.end_time'       => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.is_working_day' => ['nullable', 'boolean'],
        ];
    }
}
