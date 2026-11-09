<?php

namespace App\Livewire\Customers;

use App\Actions\Customers\UpdateUserRoles;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Throwable;

class UserRoles extends Component
{
    #[Locked]
    public User $user;

    #[Locked]
    public bool $editing = false;

    /** @var list<int> */
    #[Locked]
    public array $roleIds = [];

    public function mount(User $user): void
    {
        Gate::authorize('view', $user);
        $this->user = $user;
    }

    public function edit(): void
    {
        Gate::authorize('manageRoles', $this->user);
        $this->roleIds = $this->user->roles()->pluck('roles.id')->all();
        $this->resetValidation();
        $this->editing = true;
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->roleIds = [];
        $this->resetValidation();
    }

    public function toggleRole(int $id): void
    {
        Gate::authorize('manageRoles', $this->user);
        abort_unless($this->editing, 403);
        Role::query()->where('guard_name', 'web')->findOrFail($id);
        $this->roleIds = in_array($id, $this->roleIds, true)
            ? array_values(array_diff($this->roleIds, [$id]))
            : [...$this->roleIds, $id];
    }

    /**
     * @throws Throwable
     */
    public function save(): void
    {
        Gate::authorize('manageRoles', $this->user);
        abort_unless($this->editing, 403);
        $this->validate([
            'roleIds' => ['array'],
            'roleIds.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ]);
        app(UpdateUserRoles::class)->handle($this->user, $this->roleIds);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');
        if (auth()->id() === $this->user->id) {
            auth()->user()->unsetRelation('roles')->unsetRelation('permissions');
        }
        $this->cancel();
        if (Gate::denies('view', $this->user)) {
            $this->redirect(route('dashboard'));
            $this->skipRender();
        }
    }

    public function render(): View
    {
        Gate::authorize('view', $this->user);

        $assignedIds = $this->user->roles()->pluck('roles.id')->all();
        $selectedIds = $this->editing ? $this->roleIds : $assignedIds;
        $roles = Role::query()->where('guard_name', 'web')
            ->when(! $this->editing, fn ($query) => $query->whereIn('id', $assignedIds))
            ->orderBy('name')->get()->sortBy(fn (Role $role): int => match (true) {
                in_array($role->id, $selectedIds, true) => 0,
                in_array($role->id, $assignedIds, true) => 1,
                default => 2,
            });

        return view('livewire.customers.user-roles', compact('roles', 'assignedIds'));
    }
}
