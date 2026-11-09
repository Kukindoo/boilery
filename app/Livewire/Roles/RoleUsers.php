<?php

namespace App\Livewire\Roles;

use App\Actions\Customers\RemoveRoleFromUser;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class RoleUsers extends Component
{
    use WithPagination;

    #[Locked]
    public Role $role;

    public string $search = '';

    #[Locked]
    public ?int $removeUserId = null;

    public bool $confirmingRemoval = false;

    public function mount(Role $role): void
    {
        Gate::authorize('view', $role);
        Gate::authorize('viewAny', User::class);
        $this->role = $role;
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        Gate::authorize('view', $this->role);
        Gate::authorize('viewAny', User::class);

        return User::query()->role($this->role)
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('name')->orderBy('id')->paginate(15, pageName: 'role_users_page');
    }

    public function updatedSearch(): void
    {
        $this->resetPage(pageName: 'role_users_page');
    }

    public function confirmRemoveRole(int $id): void
    {
        Gate::authorize('view', $this->role);
        $user = User::query()->role($this->role)->findOrFail($id);
        Gate::authorize('manageRoles', $user);
        $this->resetValidation();
        $this->removeUserId = $id;
        $this->confirmingRemoval = true;
    }

    public function cancelRemoval(): void
    {
        $this->confirmingRemoval = false;
        $this->removeUserId = null;
        $this->resetValidation();
    }

    public function removeRole(): void
    {
        abort_if($this->removeUserId === null, 404);
        $user = User::findOrFail($this->removeUserId);
        app(RemoveRoleFromUser::class)->handle($user, $this->role);
        $this->removeUserId = null;
        $this->confirmingRemoval = false;
        $this->resetPage(pageName: 'role_users_page');
        unset($this->users);
        auth()->user()->unsetRelation('roles')->unsetRelation('permissions');
        if (Gate::denies('view', $this->role) || Gate::denies('viewAny', User::class)) {
            $this->redirect(route('dashboard'));
            $this->skipRender();
        }
    }

    public function render(): View
    {
        return view('livewire.roles.role-users');
    }
}
