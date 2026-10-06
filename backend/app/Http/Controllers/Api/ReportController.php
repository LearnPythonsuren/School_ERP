<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\FeeInvoice;
use App\Models\StaffMember;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /** Ready-made summaries for the Reports screen. */
    public function summary(): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json([
            'fees' => [
                'billed'      => (float) FeeInvoice::sum('amount'),
                'collected'   => (float) FeeInvoice::sum('paid_amount'),
                'outstanding' => (float) FeeInvoice::selectRaw('coalesce(sum(amount - paid_amount), 0) as due')->value('due'),
                'invoices'    => FeeInvoice::count(),
                'by_class'    => FeeInvoice::selectRaw('class_name, sum(amount) as billed, sum(paid_amount) as collected')
                                    ->groupBy('class_name')->orderBy('class_name')->get(),
            ],
            'attendance' => [
                'present_today'  => Attendance::whereDate('date', $today)->where('status', 'present')->count(),
                'total_students' => Student::count(),
                'average_pct'    => (int) round(Student::avg('attendance_pct') ?? 0),
                'below_75'       => Student::where('attendance_pct', '<', 75)->count(),
            ],
            'academics' => [
                'results_recorded' => ExamResult::count(),
                'top_average'      => (int) round(ExamResult::selectRaw('max((maths + science + english) / 3.0) as top')->value('top') ?? 0),
                'grades'           => ExamResult::selectRaw('grade, count(*) as total')->groupBy('grade')->orderBy('grade')->pluck('total', 'grade'),
            ],
            'students' => [
                'by_class' => Student::selectRaw('class_name, count(*) as total')->groupBy('class_name')->orderBy('class_name')->pluck('total', 'class_name'),
            ],
            'admissions' => Admission::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /** CSV download: /reports/export/{students|fees|staff|attendance|results}. Opens in Excel. */
    public function export(Request $request, string $type): StreamedResponse
    {
        [$headers, $rows] = match ($type) {
            'students' => [
                ['ID', 'Name', 'Class', 'Roll', 'Fee status', 'Attendance %', 'Guardian', 'Guardian phone'],
                Student::orderBy('class_name')->orderBy('roll_no')->cursor()->map(fn ($s) => [
                    $s->id, $s->name, $s->class_name, $s->roll_no, $s->fee_status, $s->attendance_pct, $s->guardian_name, $s->guardian_phone,
                ]),
            ],
            'fees' => [
                ['Invoice', 'Student', 'Class', 'Description', 'Amount', 'Paid', 'Balance', 'Status', 'Due on', 'Paid on', 'Mode', 'Receipt'],
                FeeInvoice::with('student:id,name')->orderBy('id')->cursor()->map(fn ($f) => [
                    $f->invoice_no, $f->student?->name, $f->class_name, $f->description, $f->amount, $f->paid_amount, $f->balance,
                    $f->status, $f->due_on?->toDateString(), $f->paid_on?->toDateString(), $f->payment_mode, $f->receipt_no,
                ]),
            ],
            'staff' => [
                ['ID', 'Name', 'Role', 'Department', 'Status', 'Email', 'Phone'],
                StaffMember::orderBy('name')->cursor()->map(fn ($s) => [$s->id, $s->name, $s->role, $s->department, $s->status, $s->email, $s->phone]),
            ],
            'attendance' => [
                ['Date', 'Student', 'Class', 'Status'],
                Attendance::with('student:id,name,class_name')
                    ->whereDate('date', '>=', $request->query('from', now()->subDays(30)->toDateString()))
                    ->whereDate('date', '<=', $request->query('to', now()->toDateString()))
                    ->orderBy('date')->cursor()->map(fn ($a) => [substr((string) $a->date, 0, 10), $a->student?->name, $a->student?->class_name, $a->status]),
            ],
            'results' => [
                ['Exam', 'Student', 'Class', 'Maths', 'Science', 'English', 'Average', 'Grade'],
                ExamResult::with('student:id,name')->orderBy('exam_name')->cursor()->map(fn ($r) => [
                    $r->exam_name, $r->student?->name, $r->class_name, $r->maths, $r->science, $r->english, $r->average, $r->grade,
                ]),
            ],
            default => abort(404, 'Unknown export. Use students, fees, staff, attendance or results.'),
        };

        $filename = $type.'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₹ and Indian names correctly
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                // Neutralise spreadsheet formula injection (=, +, -, @ at cell start).
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
