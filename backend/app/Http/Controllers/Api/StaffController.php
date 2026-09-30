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
            $query->where('name', 'like', "%{$search}%");
        }
        if ($dept = $request->query('department')) {
            $query->where('department', $dept);
        }
        return $query->orderBy('name')->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string'],
            'role'       => ['required', 'string'],
            'department' => ['nullable', 'string'],
            'status'     => ['nullable', 'in:present,absent,leave'],
            'email'      => ['nullable', 'email'],
            'phone'      => ['nullable', 'string'],
        ]);
        return response()->json(StaffMember::create($data), 201);
    }

    public function show(StaffMember $staff)
    {
        return response()->json($staff);
    }

    public function update(Request $request, StaffMember $staff)
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'string'],
            'role'       => ['sometimes', 'string'],
            'department' => ['nullable', 'string'],
            'status'     => ['sometimes', 'in:present,absent,leave'],
            'email'      => ['nullable', 'email'],
            'phone'      => ['nullable', 'string'],
        ]);
        $staff->update($data);
        return response()->json($staff);
    }

    public function destroy(StaffMember $staff)
    {
        $staff->delete();
        return response()->noContent();
    }
}
