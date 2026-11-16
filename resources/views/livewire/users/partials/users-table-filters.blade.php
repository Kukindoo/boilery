<flux:input wire:model.live.debounce.300ms="search" label="Hledat uživatele" placeholder="Jméno, e-mail, telefon nebo adresa" clearable />
<flux:pillbox wire:model.live="selectedRoles" multiple label="Role" placeholder="Vyberte role..." >
    @foreach ($this->roles as $role)
        <flux:pillbox.option :value="(string) $role->id">
            {{ $role->name }}
        </flux:pillbox.option>
    @endforeach
    <flux:pillbox.option value="none">Bez role</flux:pillbox.option>
</flux:pillbox>
