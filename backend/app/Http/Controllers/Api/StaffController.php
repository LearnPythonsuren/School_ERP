<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = StaffMember::query();
        if ($search = $request->query('search')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('role', 'like', "%{$search}%"));
        }
        if ($dept = $request->query('department')) {
            $query->where('department', $dept);
        }
        return $query->orderBy('name')->paginate($this->perPage($request));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules('required'));
        return response()->json(StaffMember::create($data), 201);
    }

    public function show(StaffMember $staff)
    {
        return response()->json($staff);
    }

    public function update(Request $request, StaffMember $staff)
    {
        $data = $request->validate($this->rules('sometimes'));
        $staff->update($data);
        return response()->json($staff);
    }

    public function destroy(StaffMember $staff)
    {
        $staff->delete();
        return response()->noContent();
    }

    private function rules(string $presence): array
    {
        return [
            'name'       => [$presence, 'string', 'max:120'],
            'role'       => [$presence, 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:60'],
            'status'     => ['sometimes', 'in:present,absent,leave'],
            'email'      => ['nullable', 'email'],
            'phone'      => ['nullable', 'string', 'max:20'],
        ];
    }
}
