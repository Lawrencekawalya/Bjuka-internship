<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    /**
     * @return array<string>
     */
    private function manageableRoles(): array
    {
        return [
            UserRole::ADMIN->value,
            UserRole::HR->value,
            UserRole::MANAGER->value,
            UserRole::CENTER_DIRECTOR->value,
            UserRole::SUPERVISOR->value,
        ];
    }

    public function index(): Response
    {
        return Inertia::render('users/Index', [
            'users' => User::query()
                ->whereIn('role', $this->manageableRoles())
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'must_change_password', 'created_at', 'updated_at']),
            'roles' => collect($this->manageableRoles())
                ->map(fn (string $role) => [
                    'value' => $role,
                    'label' => str($role)->replace('_', ' ')->title()->toString(),
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($this->manageableRoles())],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'],
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role->value, $this->manageableRoles(), true), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in($this->manageableRoles())],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updates = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $request->user()->is($user) ? UserRole::ADMIN->value : $validated['role'],
        ];

        if (filled($validated['password'] ?? null)) {
            $updates['password'] = $validated['password'];
            $updates['must_change_password'] = true;
        }

        $user->update($updates);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role->value, $this->manageableRoles(), true), 404);

        if ($request->user()->is($user)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
