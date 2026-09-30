<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * School user management. Only school admins (or the super-admin) may
 * manage accounts and assign roles — this is the "role-wise access" control.
 */
class UserController extends Controller
{
    /** Guard: admins only. Called at the top of every action. */
    private function gate(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'super-admin']),
            403, 'Only a school admin can manage users and roles.'
        );
    }

    public function index(Request $request)
    {
        $this->gate();
        $q = User::query();
        if ($s = $request->query('search')) $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%");
        if ($r = $request->query('role')) $q->role($r);
        return $q->orderBy('name')->paginate($request->integer('per_page', 25))->through(fn ($u) => $this->shape($u));
    }

    public function store(Request $request)
    {
        $this->gate();
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => ['required', Rule::in(Role::pluck('name'))],
        ]);
        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'],
            'password' => Hash::make($data['password']), 'phone' => $data['phone'] ?? null,
        ]);
        $user->assignRole($data['role']);
        return response()->json($this->shape($user), 201);
    }

    public function show(User $user)
    {
        $this->gate();
        return response()->json($this->shape($user));
    }

    public function update(Request $request, User $user)
    {
        $this->gate();
        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:120'],
            'email'    => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'string', 'min:8'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => ['sometimes', Rule::in(Role::pluck('name'))],
        ]);
        if (isset($data['password'])) $data['password'] = Hash::make($data['password']);
        $user->update(collect($data)->except('role')->toArray());
        if (isset($data['role'])) $user->syncRoles([$data['role']]);
        return response()->json($this->shape($user));
    }

    public function destroy(User $user)
    {
        $this->gate();
        $user->tokens()->delete();
        $user->delete();
        return response()->noContent();
    }

    /** The roles a school admin can assign — powers the UI role dropdown. */
    public function roles()
    {
        $this->gate();
        return response()->json(['roles' => Role::pluck('name')]);
    }

    private function shape(User $u): array
    {
        return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'roles' => $u->getRoleNames()];
    }
}
