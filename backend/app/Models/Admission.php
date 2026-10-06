<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admission extends Model
{
    protected $fillable = [
        'application_no',
        'applicant_name',
        'class_applied',
        'guardian_name',
        'guardian_phone',
        'status',        // pending | approved | rejected | enrolled
        'applied_on',
        'enrolled_student_id',
    ];

    protected function casts(): array
    {
        return [
            'applied_on' => 'date:Y-m-d',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'enrolled_student_id');
    }
}
