<flux:card>
    <div class="flex items-center justify-between gap-4 my-4">

        <flux:heading size="xl">
            {{ $heading }}
        </flux:heading>

        {{ $actions ?? '' }}
    </div>
    <flux:separator />
    <div class="flex-col items-center justify-between gap-4 space-y-4 my-4">
        {{ $filters ?? '' }}
    </div>
    <flux:separator />
    <div class="mt-4">
        {{ $slot }}
    </div>
</flux:card>
