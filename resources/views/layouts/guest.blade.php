<x-layouts::guest.header :title="$title ?? null">
    <flux:main>
        <div class="mb-6">
            <flux:breadcrumbs>
                {{ $breadcrumbs ?? null }}
            </flux:breadcrumbs>
        </div>
        {{ $slot }}
    </flux:main>
    @persist('toast')
    <flux:toast />
    @endpersist
</x-layouts::guest.header>
