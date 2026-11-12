@props(['quote'])

@can ('view-any', $quote->issue)
    <flux:link :href="route('issues.show', $quote->issue)" wire:navigate>
        Případ #{{ $quote->issue_id }}
    </flux:link>
@else
    Případ #{{ $quote->issue_id }}
@endcan