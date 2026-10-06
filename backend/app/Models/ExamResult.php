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

    /** Default grading scale; schools can still send their own grade. */
    public static function gradeFor(int $average): string
    {
        return match (true) {
            $average >= 90 => 'A+',
            $average >= 75 => 'A',
            $average >= 60 => 'B',
            $average >= 45 => 'C',
            $average >= 33 => 'D',
            default        => 'F',
        };
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
