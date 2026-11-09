@php
    use App\Enums\WorkStatus;
@endphp

<flux:pillbox wire:model.live="selectedStatuses" multiple label="Status" placeholder="Vyberte statusy...">
    @foreach (WorkStatus::cases() as $status)
        <flux:pillbox.option :value="$status->value">
            {{ $status->label() }}
        </flux:pillbox.option>
    @endforeach
</flux:pillbox>
<flux:input wire:model.live.debounce.300ms="search" label="Hledat zakázky" clearable/>
