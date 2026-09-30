<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Admission::query();
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        return $query->latest()->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'application_no' => ['nullable', 'string', 'unique:admissions,application_no'],
            'applicant_name' => ['required', 'string', 'max:120'],
            'class_applied'  => ['required', 'string', 'max:60'],
            'guardian_name'  => ['nullable', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
        ]);
        $data['application_no'] ??= 'APP-'.now()->format('y').str_pad((string) (Admission::count() + 1), 4, '0', STR_PAD_LEFT);
        $data['status'] = 'pending';
        $data['applied_on'] = now()->toDateString();
        return response()->json(Admission::create($data), 201);
    }

    public function show(Admission $admission)
    {
        return response()->json($admission->load('student'));
    }

    public function update(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'applicant_name' => ['sometimes', 'string', 'max:120'],
            'class_applied'  => ['sometimes', 'string', 'max:60'],
            'guardian_name'  => ['nullable', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
        ]);
        $admission->update($data);
        return response()->json($admission);
    }

    public function approve(Admission $admission)
    {
        abort_if($admission->status === 'enrolled', 422, 'Applicant is already enrolled.');
        $admission->update(['status' => 'approved']);
        return response()->json($admission);
    }

    public function reject(Admission $admission)
    {
        abort_if($admission->status === 'enrolled', 422, 'Applicant is already enrolled.');
        $admission->update(['status' => 'rejected']);
        return response()->json($admission);
    }

    public function enroll(Admission $admission)
    {
        abort_if($admission->status === 'enrolled', 422, 'Applicant is already enrolled.');
        $student = DB::transaction(function () use ($admission) {
            $student = Student::create([
                'name'           => $admission->applicant_name,
                'class_name'     => $admission->class_applied,
                'roll_no'        => (Student::where('class_name', $admission->class_applied)->max('roll_no') ?? 0) + 1,
                'fee_status'     => 'due',
                'attendance_pct' => 100,
                'guardian_name'  => $admission->guardian_name,
                'guardian_phone' => $admission->guardian_phone,
            ]);
            $admission->update(['status' => 'enrolled', 'enrolled_student_id' => $student->id]);
            return $student;
        });
        return response()->json(['message' => 'Applicant enrolled as a student.', 'student' => $student], 201);
    }

    public function destroy(Admission $admission)
    {
        $admission->delete();
        return response()->noContent();
    }
}
