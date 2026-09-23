<x-layouts::guest :title="config('app.name'). ' - ' . __('Welcome')">
    <x-contacts.contact-details-section />

    <x-contacts.create-quote-section />
</x-layouts::guest>