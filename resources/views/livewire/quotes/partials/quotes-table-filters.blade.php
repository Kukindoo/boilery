@php
    use App\Enums\QuoteStatus;
@endphp

<flux:pillbox wire:model.live="selectedStatuses" multiple placeholder="Choose tags...">
    @foreach(QuoteStatus::cases() as $status)
        <flux:pillbox.option value="{{ $status->value }}">
            {{ $status->label() }}
        </flux:pillbox.option>
    @endforeach
</flux:pillbox>