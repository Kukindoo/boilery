@php
    use App\Enums\FileTypes;
@endphp

<x-layouts::app :title="__('Poptávky')">
    <x-stellaris.panel.container.base>
        <x-slot:header>
            <x-stellaris.panel.header.base :heading="__('Poptávka :quote_id', ['quote_id' => $quote->id])">
                <x-slot:subheading>
                    <x-customers.customer-name :customer="$quote->user" />
                </x-slot>
            </x-stellaris.panel.header.base>
        </x-slot:header>

        <x-slot:actionsStrip>
            <x-stellaris.panel.actions-strip.base>
                <x-slot:slotLeft>
                    <flux:button :href="route('quotes.index')" icon="arrow-left" size="sm">
                        {{ __('Zpět na Poptávky') }}
                    </flux:button>
                </x-slot:slotLeft>
                @include('quotes.partials.show-actions')
            </x-stellaris.panel.actions-strip.base>
        </x-slot:actionsStrip>

        <x-quotes.quote-person-info :quote="$quote" />
        <x-quotes.quote-boiler-info :quote="$quote" />
        <x-files.attachment-card :model="$quote" />
        <livewire:activity-logs.activity-logs-table
                :subject-type="\App\Models\Quote::class"
                :subject-id="$quote->id"
        />
    </x-stellaris.panel.container.base>

    <x-pdfs.pdf-modal-preview
            :model="$quote"
            :pdf-content="$pdfContent"
            :file-type="FileTypes::QUOTE_SUMMARY->value"
            :show-trigger="false"
    />
</x-layouts::app>
