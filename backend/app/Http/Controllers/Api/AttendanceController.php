<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    /**
     * Attendance for one day: `marks` keyed by student id (what the UI has
     * always used) plus the class roster with each student's mark, so a
     * register can be drawn in one call. Optional ?class= filter.
     */
    public function index(Request $request)
    {
        $request->validate(['date' => ['nullable', 'date']]);
        $date = Carbon::parse($request->query('date', 'today'))->toDateString();

        $marks = Attendance::whereDate('date', $date)->pluck('status', 'student_id');

        $students = Student::query()
            ->when($request->query('class'), fn ($q, $class) => $q->where('class_name', $class))
            ->orderBy('class_name')->orderBy('roll_no')->orderBy('name')
            ->get(['id', 'name', 'class_name', 'roll_no'])
            ->map(fn (Student $s) => $s->only(['id', 'name', 'class_name', 'roll_no']) + ['status' => $marks[$s->id] ?? null]);

        return response()->json([
            'date'     => $date,
            'marks'    => $marks,
            'students' => $students,
            'classes'  => Student::distinct()->orderBy('class_name')->pluck('class_name'),
            'summary'  => [
                'present' => $marks->filter(fn ($s) => $s === 'present')->count(),
                'absent'  => $marks->filter(fn ($s) => $s === 'absent')->count(),
                'leave'   => $marks->filter(fn ($s) => $s === 'leave')->count(),
            ],
        ]);
    }

    /**
     * Bulk upsert — the "Save attendance" button sends the whole day's
     * marks at once. Idempotent thanks to the unique (student_id, date).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'    => ['required', 'date', 'before_or_equal:today'],
            'marks'   => ['required', 'array'],
            'marks.*' => ['in:present,absent,leave'],
        ]);

        $ids = array_map('intval', array_keys($data['marks']));
        $known = Student::whereIn('id', $ids)->pluck('id')->all();
        if ($unknown = array_diff($ids, $known)) {
            throw ValidationException::withMessages(['marks' => ['Unknown student id(s): '.implode(', ', $unknown)]]);
        }

        $date = Carbon::parse($data['date'])->toDateString();

        DB::transaction(function () use ($data, $date) {
            foreach ($data['marks'] as $studentId => $status) {
                Attendance::updateOrCreate(
                    ['student_id' => $studentId, 'date' => $date],
                    ['status' => $status],
                );
            }
            Student::whereIn('id', array_keys($data['marks']))->get()->each->refreshAttendancePct();
        });

        return response()->json(['message' => 'Attendance saved.', 'saved' => count($data['marks'])]);
    }
}
