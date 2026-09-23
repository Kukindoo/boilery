<x-layouts::guest :title="config('app.name'). ' - ' . __('Welcome')">
    <x-contacts.contact-details-section />

    <x-contacts.contact-section />
</x-layouts::guest>