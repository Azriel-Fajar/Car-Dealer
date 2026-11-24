<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreUserRequest;
use App\Http\Requests\Dashboard\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private const LAST_ADMIN_MESSAGE = 'Minimal harus ada satu admin aktif.';

    public function index(Request $request): Response
    {
        $q = $request->string('q')->trim()->toString();
        $role = Role::tryFrom($request->string('role')->toString());
        $active = in_array($request->query('active'), ['0', '1'], true) ? (string) $request->query('active') : null;

        $items = User::query()
            ->when($q !== '', fn ($query) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
            ))
            ->when($role, fn ($query) => $query->where('role', $role->value))
            ->when($active !== null, fn ($query) => $query->where('is_active', $active === '1'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'is_active' => $user->is_active,
                'created_at' => $user->created_at?->format('d M Y'),
                'is_self' => $user->is($request->user()),
            ]);

        return Inertia::render('dashboard/users/index', [
            'items' => $items,
            'filters' => [
                'q' => $q === '' ? null : $q,
                'role' => $role?->value,
                'active' => $active,
            ],
            'roles' => array_map(
                fn (Role $role) => ['value' => $role->value, 'label' => $role->label()],
                Role::cases(),
            ),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);
        $user->forceFill([
            'role' => $data['role'],
            'is_active' => $data['is_active'],
            'email_verified_at' => now(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna ditambahkan.']);

        return $this->backToIndex();
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $newRole = Role::from($data['role']);
        $staysActiveAdmin = $newRole === Role::Admin && $data['is_active'];

        if ($user->role === Role::Admin && $user->is_active && ! $staysActiveAdmin && $this->isLastActiveAdmin($user)) {
            return $this->refuse(self::LAST_ADMIN_MESSAGE);
        }

        if ($user->is($request->user())) {
            if ($newRole !== $user->role) {
                return $this->refuse('Tidak bisa mengubah peran akun sendiri.');
            }

            if (! $data['is_active']) {
                return $this->refuse('Tidak bisa menonaktifkan akun sendiri.');
            }
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $user->forceFill(['role' => $newRole, 'is_active' => $data['is_active']])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna diperbarui.']);

        return $this->backToIndex();
    }

    /**
     * No table restricts deletion of users (passkeys cascade, sessions carry no foreign key).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return $this->refuse('Tidak bisa menghapus akun sendiri.');
        }

        if ($user->role === Role::Admin && $user->is_active && $this->isLastActiveAdmin($user)) {
            return $this->refuse(self::LAST_ADMIN_MESSAGE);
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pengguna dihapus.']);

        return $this->backToIndex();
    }

    private function isLastActiveAdmin(User $user): bool
    {
        return ! User::query()
            ->where('role', Role::Admin->value)
            ->where('is_active', true)
            ->whereKeyNot($user->getKey())
            ->exists();
    }

    private function refuse(string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return $this->backToIndex();
    }

    /**
     * Return to the list the admin came from, keeping its search, filters and page.
     */
    private function backToIndex(): RedirectResponse
    {
        return back(fallback: route('dashboard.users.index'));
    }
}
