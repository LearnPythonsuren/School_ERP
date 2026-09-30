<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    /** Grid for one class: GET /api/timetable?class=Class 10-A */
    public function index(Request $request)
    {
        $class = $request->query('class');
        abort_unless($class, 422, 'A class query parameter is required.');
        $slots = TimetableSlot::where('class_name', $class)->orderBy('period_no')->get();
        $grid = [];
        foreach ($slots as $s) {
            $grid[$s->day][$s->period_no] = [
                'subject' => $s->subject, 'teacher' => $s->teacher,
                'start_time' => $s->start_time, 'end_time' => $s->end_time,
            ];
        }
        return response()->json(['class' => $class, 'grid' => $grid, 'slots' => $slots]);
    }

    /** Create or overwrite one slot (idempotent on class + day + period). */
    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $slot = TimetableSlot::updateOrCreate(
            ['class_name' => $data['class_name'], 'day' => $data['day'], 'period_no' => $data['period_no']],
            $data,
        );
        return response()->json($slot, 201);
    }

    public function show(TimetableSlot $slot)
    {
        return response()->json($slot);
    }

    public function update(Request $request, TimetableSlot $slot)
    {
        $data = $request->validate($this->rules(true));
        $slot->update($data);
        return response()->json($slot);
    }

    public function destroy(TimetableSlot $slot)
    {
        $slot->delete();
        return response()->noContent();
    }

    private function rules(bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        return [
            'class_name' => [$req, 'string'],
            'day'        => [$req, 'in:Mon,Tue,Wed,Thu,Fri,Sat'],
            'period_no'  => [$req, 'integer', 'between:1,10'],
            'subject'    => [$req, 'string'],
            'teacher'    => ['nullable', 'string'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time'   => ['nullable', 'date_format:H:i'],
        ];
    }
}
