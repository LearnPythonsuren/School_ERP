<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * School user management — the "role-wise access" control. Routes are
 * admin-only (EnsureRole). Guard rails: you cannot delete yourself, the
 * last admin cannot be removed or demoted, and only a super-admin can
 * grant super-admin.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::with('roles:id,name');
        if ($s = $request->query('search')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($r = $request->query('role')) {
            $q->role($r);
        }
        return $q->orderBy('name')->paginate($this->perPage($request))->through(fn ($u) => $this->shape($u));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => ['required', Rule::in($this->assignableRoles($request))],
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
        return response()->json($this->shape($user));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:120'],
            'email'    => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => ['sometimes', Rule::in($this->assignableRoles($request))],
        ]);

        if (isset($data['role']) && $data['role'] !== 'admin' && $this->isLastAdmin($user)) {
            abort(422, 'This is the only admin. Make someone else an admin first.');
        }
        if ($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin')) {
            abort(403, 'Only a super-admin can change a super-admin account.');
        }

        // A blank password in an edit form means "leave unchanged".
        $fields = collect($data)->except('role');
        if ($fields->get('password')) {
            $fields->put('password', Hash::make($fields->get('password')));
        } else {
            $fields->forget('password');
        }
        $user->update($fields->toArray());
        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }
        if (! empty($data['password'])) {
            $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()?->id)->delete();
        }
        return response()->json($this->shape($user->fresh()));
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot delete your own account.');
        abort_if($this->isLastAdmin($user), 422, 'This is the only admin and cannot be deleted.');
        abort_if($user->hasRole('super-admin') && ! $request->user()->hasRole('super-admin'), 403, 'Only a super-admin can delete a super-admin.');

        $user->tokens()->delete();
        $user->delete();
        return response()->noContent();
    }

    /** The roles this admin can assign — powers the UI role dropdown. */
    public function roles(Request $request)
    {
        return response()->json(['roles' => $this->assignableRoles($request)]);
    }

    private function assignableRoles(Request $request): array
    {
        $roles = Role::orderBy('id')->pluck('name');
        if (! $request->user()->hasRole('super-admin')) {
            $roles = $roles->reject(fn ($r) => $r === 'super-admin');
        }
        return $roles->values()->all();
    }

    private function isLastAdmin(User $user): bool
    {
        return $user->hasRole('admin') && User::role('admin')->count() <= 1;
    }

    private function shape(User $u): array
    {
        return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'roles' => $u->getRoleNames()];
    }
}
