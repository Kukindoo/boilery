<x-layouts::app :title="__('Upravit náhradní díl')">
    <x-stellaris.container.index-card :heading="__('Upravit náhradní díl')">
        <livewire:spare-parts.spare-part-edit :spare-part="$sparePart" />
    </x-stellaris.container.index-card>
</x-layouts::app>
