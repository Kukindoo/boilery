<flux:field class="px-4 py-6 sm:grid sm:grid-cols-3 sm:items-start sm:gap-4 sm:space-y-0 sm:px-6">
    <flux:label class="sm:pt-2">
        {{ $label }}
    </flux:label>
    <div class="min-w-0 space-y-2 sm:col-span-2">
        {{ $slot }}
    </div>
</flux:field>