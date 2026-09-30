<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    protected $fillable = [
        'student_id',
        'exam_name',
        'class_name',
        'maths',
        'science',
        'english',
        'grade',
    ];

    protected function casts(): array
    {
        return [
            'maths'   => 'integer',
            'science' => 'integer',
            'english' => 'integer',
        ];
    }

    public function getAverageAttribute(): int
    {
        return (int) round(($this->maths + $this->science + $this->english) / 3);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
