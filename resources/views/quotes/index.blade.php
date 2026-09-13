<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:heading>
            Poptávky
        </flux:heading>
        <livewire:quotes-table />
    </div>
</x-layouts::app>