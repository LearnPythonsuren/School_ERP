<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query();
        if ($search = $request->query('search')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('guardian_phone', 'like', "%{$search}%"));
        }
        if ($class = $request->query('class')) {
            $query->where('class_name', $class);
        }
        if ($fee = $request->query('fee_status')) {
            $query->where('fee_status', $fee);
        }
        return StudentResource::collection(
            $query->orderBy('class_name')->orderBy('roll_no')->orderBy('name')->paginate($this->perPage($request))
        )->additional(['classes' => Student::distinct()->orderBy('class_name')->pluck('class_name')]);
    }

    public function store(StoreStudentRequest $request)
    {
        $student = Student::create($request->validated());
        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    /**
     * Bulk import (e.g. a school's existing Excel list saved as CSV, parsed in
     * the browser). All-or-nothing: if any row is invalid nothing is saved and
     * every problem is reported with its row number, so the file can be fixed.
     */
    public function import(Request $request)
    {
        $request->validate(['rows' => ['required', 'array', 'min:1', 'max:3000']]);

        $rules = (new StoreStudentRequest())->rules();
        $errors = [];
        foreach ($request->input('rows') as $i => $row) {
            $v = Validator::make(is_array($row) ? $row : [], $rules);
            if ($v->fails()) {
                $errors[] = 'Row '.($i + 2).': '.implode(' ', $v->errors()->all()); // +2: header line + 1-based
            }
        }
        if ($errors) {
            throw ValidationException::withMessages(['rows' => array_slice($errors, 0, 50)]);
        }

        $created = DB::transaction(fn () => collect($request->input('rows'))
            ->map(fn ($row) => Student::create(Arr::only($row, array_keys($rules))))
            ->count());

        return response()->json(['message' => "{$created} students imported.", 'imported' => $created], 201);
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
