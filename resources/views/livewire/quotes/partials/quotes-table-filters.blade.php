<flux:pillbox wire:model.live="selectedStatuses" multiple placeholder="Choose tags...">
    @foreach(\App\Enums\QuoteStatus::cases() as $status)
        <flux:pillbox.option value="{{ $status->value }}">
            {{ $status->label() }}
        </flux:pillbox.option>
    @endforeach
</flux:pillbox>