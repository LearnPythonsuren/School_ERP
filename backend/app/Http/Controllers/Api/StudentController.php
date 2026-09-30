<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query();
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($class = $request->query('class')) {
            $query->where('class_name', $class);
        }
        return StudentResource::collection(
            $query->orderBy('name')->paginate($request->integer('per_page', 25))
        );
    }

    public function store(StoreStudentRequest $request)
    {
        $student = Student::create($request->validated());
        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    public function show(Student $student)
    {
        return new StudentResource($student);
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'name'           => ['sometimes', 'string', 'max:120'],
            'class_name'     => ['sometimes', 'string', 'max:60'],
            'roll_no'        => ['nullable', 'integer', 'min:0'],
            'fee_status'     => ['sometimes', 'in:paid,partial,due'],
            'attendance_pct' => ['sometimes', 'integer', 'between:0,100'],
            'guardian_name'  => ['nullable', 'string', 'max:120'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
        ]);
        $student->update($data);
        return new StudentResource($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->noContent();
    }
}
