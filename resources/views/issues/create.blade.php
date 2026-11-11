<x-layouts::app :title="__('Nový případ')">
    <x-stellaris.container.show-card :title="__('Nový případ')">
        <livewire:issues.issue-create :customer-id="$customerId" />
    </x-stellaris.container.show-card>
</x-layouts::app>
