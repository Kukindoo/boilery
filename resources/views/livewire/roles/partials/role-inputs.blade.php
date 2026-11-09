@php
    use App\Enums\Roles;
@endphp

<flux:input wire:model="form.name" label="Název" :readonly="$form->role !== null && Roles::tryFrom($form->role->id) !== null" />
@if ($form->role !== null && Roles::tryFrom($form->role->id) !== null)
    <flux:text>Systémový název nelze změnit.</flux:text>
@endif
