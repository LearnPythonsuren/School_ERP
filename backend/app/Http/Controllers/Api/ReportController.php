<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\FeeInvoice;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    /** Ready-made summaries for the Reports screen. */
    public function summary(): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json([
            'fees' => [
                'collected'   => (float) FeeInvoice::where('status', 'paid')->sum('amount'),
                'outstanding' => (float) FeeInvoice::where('status', '!=', 'paid')->sum('amount'),
                'invoices'    => FeeInvoice::count(),
            ],
            'attendance' => [
                'present_today' => Attendance::whereDate('date', $today)->where('status', 'present')->count(),
                'total_students'=> Student::count(),
            ],
            'academics' => [
                'results_recorded' => ExamResult::count(),
                'top_average'      => (int) round(ExamResult::get()->max(fn ($r) => $r->average) ?? 0),
            ],
        ]);
    }
}
