<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Gate with a role if you like, e.g.:
        // return $this->user()->hasAnyRole(['admin', 'teacher']);
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:120'],
            'class_name'     => ['required', 'string', 'max:60'],
            'roll_no'        => ['nullable', 'integer', 'min:0'],
            'fee_status'     => ['nullable', 'in:paid,partial,due'],
            'attendance_pct' => ['nullable', 'integer', 'between:0,100'],
            'guardian_name'  => ['nullable', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
