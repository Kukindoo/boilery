@php
    use App\Enums\Permissions;
    use Spatie\Permission\Models\Permission;
@endphp
<div class="space-y-6">
    <flux:heading size="xl">
        {{ __('Oprávnění') }}
    </flux:heading>
    @include('livewire.permissions.partials.permissions-table-filters')

    <x-stellaris.container.show-card :title="__('Oprávnění')">
        <x-slot name="action">
            @include('livewire.permissions.partials.permissions-table-actions')
        </x-slot>

        <flux:text class="col-span-3">
            Číslo udává počet rolí, které mají dané oprávnění přiřazené.
        </flux:text>

        <flux:separator class="col-span-3"/>

        @forelse ($permissionGroups as $modelType => $permissions)
            @php
                $permissionScopes = $permissions->groupBy(fn (Permission $permission): string => Permissions::fromLabel($permission->name)?->scope() ?? 'other');
            @endphp
            @if (! $loop->first)
                <flux:separator class="col-span-3"/>
            @endif
            <x-stellaris.container.show-row :title="$modelType === 'other' ? 'Ostatní' : ucfirst($modelType)">
                <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2"
                     wire:key="permission-group-{{ $modelType }}">
                    @foreach (['all', 'own', 'other'] as $scope)
                        @if ($scope !== 'other' || isset($permissionScopes[$scope]))
                            <div @class(['grid grid-cols-1 gap-2', 'md:col-span-2' => $scope === 'other'])>
                                @forelse ($permissionScopes[$scope] ?? [] as $permission)
                                    <a href="{{ route('permissions.show', $permission) }}"
                                       wire:key="permission-{{ $permission->id }}">
                                        <flux:badge size="lg" class="w-full justify-between gap-2"
                                                    :title="$permission->name">
                                            <span class="underline underline-offset-2">
                                                {{ Permissions::fromLabel($permission->name)?->badgeLabel() ?? $permission->name }}
                                            </span>
                                            <span title="{{ __('Počet rolí') }}"
                                                  aria-label="{{ __('Počet rolí') }}: {{ $permission->roles_count }}">
                                                {{ $permission->roles_count }}
                                            </span>
                                        </flux:badge>
                                    </a>
                                @empty
                                @endforelse
                            </div>
                        @endif
                    @endforeach
                </div>
            </x-stellaris.container.show-row>
        @empty
            <flux:text class="col-span-3">
                Žádné záznamy.
            </flux:text>
        @endforelse
    </x-stellaris.container.show-card>
</div>
