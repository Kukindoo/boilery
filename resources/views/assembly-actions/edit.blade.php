<x-layouts::app :title="__('Upravit pracovní úkon')">
    <x-stellaris.container.show-card :title="__('Upravit pracovní úkon')">
        <livewire:assembly-actions.assembly-action-edit :assembly-action="$assemblyAction" />
    </x-stellaris.container.show-card>
</x-layouts::app>
