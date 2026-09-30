<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\FeeInvoice;
use App\Models\StaffMember;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * The four KPI cards + recent activity the dashboard renders.
     * All figures are computed live from this school's own database.
     */
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json([
            'total_students' => Student::count(),
            'present_today'  => Attendance::whereDate('date', $today)
                                    ->where('status', 'present')->count(),
            'fees_collected' => (float) FeeInvoice::where('status', 'paid')->sum('amount'),
            'fees_due'       => (float) FeeInvoice::where('status', '!=', 'paid')->sum('amount'),
            'staff_on_duty'  => StaffMember::where('status', 'present')->count(),
            'staff_total'    => StaffMember::count(),
        ]);
    }
}
