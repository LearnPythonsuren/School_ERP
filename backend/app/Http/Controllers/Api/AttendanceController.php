<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Attendance for one day, keyed by student id — the shape the UI's
     * Attendance screen expects.
     */
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $marks = Attendance::whereDate('date', $date)
            ->pluck('status', 'student_id');

        return response()->json([
            'date'  => $date,
            'marks' => $marks,
        ]);
    }

    /**
     * Bulk upsert — the "Save attendance" button sends the whole day's
     * marks at once. Idempotent thanks to the unique (student_id, date).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'date'          => ['required', 'date'],
            'marks'         => ['required', 'array'],
            'marks.*'       => ['in:present,absent,leave'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['marks'] as $studentId => $status) {
                Attendance::updateOrCreate(
                    ['student_id' => $studentId, 'date' => $data['date']],
                    ['status' => $status],
                );
            }
        });

        return response()->json(['message' => 'Attendance saved.']);
    }
}
