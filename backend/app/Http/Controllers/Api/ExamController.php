<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\Student;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamResult::with('student:id,name,class_name');
        if ($exam = $request->query('exam')) {
            $query->where('exam_name', $exam);
        }
        if ($class = $request->query('class')) {
            $query->where('class_name', $class);
        }
        $rows = $query->orderBy('class_name')->orderByDesc('id')->get()->map(fn (ExamResult $r) => $this->shape($r));

        return response()->json([
            'data'  => $rows,
            'exams' => ExamResult::distinct()->orderBy('exam_name')->pluck('exam_name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['class_name'] ??= Student::find($data['student_id'])?->class_name;
        $result = ExamResult::create($data);
        $this->autoGrade($result, $request);
        return response()->json($this->shape($result->load('student')), 201);
    }

    public function show(ExamResult $exam)
    {
        return response()->json($this->shape($exam->load('student')));
    }

    public function update(Request $request, ExamResult $exam)
    {
        $data = $request->validate($this->rules(true));
        $exam->update($data);
        $this->autoGrade($exam, $request);
        return response()->json($this->shape($exam->load('student')));
    }

    public function destroy(ExamResult $exam)
    {
        $exam->delete();
        return response()->noContent();
    }

    /** Fill the grade from the average unless one was sent explicitly. */
    private function autoGrade(ExamResult $r, Request $request): void
    {
        if (! $request->filled('grade')) {
            $r->update(['grade' => ExamResult::gradeFor($r->average)]);
        }
    }

    private function rules(bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        return [
            'student_id' => [$req, 'exists:students,id'],
            'exam_name'  => [$req, 'string', 'max:80'],
            'class_name' => ['nullable', 'string', 'max:60'],
            'maths'      => [$req, 'integer', 'between:0,100'],
            'science'    => [$req, 'integer', 'between:0,100'],
            'english'    => [$req, 'integer', 'between:0,100'],
            'grade'      => ['nullable', 'string', 'max:2'],
        ];
    }

    private function shape(ExamResult $r): array
    {
        return [
            'id' => $r->id, 'student_id' => $r->student_id, 'student' => $r->student?->name,
            'exam_name' => $r->exam_name, 'class_name' => $r->class_name,
            'maths' => $r->maths, 'science' => $r->science, 'english' => $r->english,
            'average' => $r->average, 'grade' => $r->grade,
        ];
    }
}
