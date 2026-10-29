<x-layouts::app title="Upravit zakázku">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading size="xl">Upravit zakázku</flux:heading>
            <flux:button size="sm" :href="route('works.show', $work)">
                Zpět na zakázku
            </flux:button>
        </div>
        <livewire:works.work-edit :work="$work" />
    </div>
</x-layouts::app>
