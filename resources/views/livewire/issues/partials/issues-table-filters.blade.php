@php
    use App\Enums\IssueStatus;
@endphp

<flux:pillbox wire:model.live="selectedStatuses" multiple label="Status" placeholder="Vyberte statusy...">
    @foreach (IssueStatus::cases() as $status)
        <flux:pillbox.option :value="$status->value">
            {{ $status->label() }}
        </flux:pillbox.option>
    @endforeach
</flux:pillbox>
<flux:input wire:model.live.debounce.300ms="search" label="Hledat případy" type="search" clearable />
