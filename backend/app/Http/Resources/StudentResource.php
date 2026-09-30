<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'class_name'     => $this->class_name,
            'roll_no'        => $this->roll_no,
            'fee_status'     => $this->fee_status,
            'attendance_pct' => $this->attendance_pct,
            'guardian_name'  => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
        ];
    }
}
