<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\BookIssue;
use App\Models\FeeInvoice;
use App\Models\StaffMember;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * KPI cards + activity for the dashboard. All figures are computed
     * live from this school's own database.
     */
    public function index(): JsonResponse
    {
        $today = now()->toDateString();
        $marked = Attendance::whereDate('date', $today);

        // Present % for each of the last 7 days that has any marks.
        $trend = Attendance::whereDate('date', '>=', now()->subDays(6)->toDateString())
            ->selectRaw("date, sum(case when status = 'present' then 1 else 0 end) as present, count(*) as total")
            ->groupBy('date')->orderBy('date')->get()
            ->map(fn ($r) => ['date' => substr((string) $r->date, 0, 10), 'pct' => $r->total ? (int) round($r->present * 100 / $r->total) : 0]);

        return response()->json([
            'total_students'     => Student::count(),
            'present_today'      => (clone $marked)->where('status', 'present')->count(),
            'marked_today'       => (clone $marked)->count(),
            'fees_collected'     => (float) FeeInvoice::sum('paid_amount'),
            'fees_due'           => (float) FeeInvoice::selectRaw('coalesce(sum(amount - paid_amount), 0) as due')->value('due'),
            'invoices_unpaid'    => FeeInvoice::where('status', '!=', 'paid')->count(),
            'staff_on_duty'      => StaffMember::where('status', 'present')->count(),
            'staff_total'        => StaffMember::count(),
            'pending_admissions' => Admission::where('status', 'pending')->count(),
            'books_on_loan'      => BookIssue::where('status', '!=', 'returned')->count(),
            'attendance_trend'   => $trend,
            'recent_announcements' => Announcement::latest('id')->limit(4)->get(['id', 'title', 'channel', 'audience', 'status', 'sent_at', 'created_at']),
        ]);
    }
}
