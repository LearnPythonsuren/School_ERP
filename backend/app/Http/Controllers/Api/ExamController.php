<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamResult::with('student');
        if ($exam = $request->query('exam')) {
            $query->where('exam_name', $exam);
        }
        if ($class = $request->query('class')) {
            $query->where('class_name', $class);
        }
        $rows = $query->get()->map(fn (ExamResult $r) => $this->shape($r));
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        return response()->json(ExamResult::create($data), 201);
    }

    public function show(ExamResult $exam)
    {
        return response()->json($this->shape($exam));
    }

    public function update(Request $request, ExamResult $exam)
    {
        $data = $request->validate($this->rules(true));
        $exam->update($data);
        return response()->json($this->shape($exam));
    }

    public function destroy(ExamResult $exam)
    {
        $exam->delete();
        return response()->noContent();
    }

    private function rules(bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        return [
            'student_id' => [$req, 'exists:students,id'],
            'exam_name'  => [$req, 'string'],
            'class_name' => ['nullable', 'string'],
            'maths'      => [$req, 'integer', 'between:0,100'],
            'science'    => [$req, 'integer', 'between:0,100'],
            'english'    => [$req, 'integer', 'between:0,100'],
            'grade'      => ['nullable', 'string', 'max:2'],
        ];
    }

    private function shape(ExamResult $r): array
    {
        return [
            'id' => $r->id, 'student' => $r->student?->name, 'class_name' => $r->class_name,
            'maths' => $r->maths, 'science' => $r->science, 'english' => $r->english,
            'average' => $r->average, 'grade' => $r->grade,
        ];
    }
}
