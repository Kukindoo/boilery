<x-layouts::app :title="__('Upravit případ')">
    <x-stellaris.container.show-card :title="__('Upravit případ')">
        <livewire:issues.issue-edit :issue="$issue" />
    </x-stellaris.container.show-card>
</x-layouts::app>
