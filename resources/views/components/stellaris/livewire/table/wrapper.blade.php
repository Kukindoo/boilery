<flux:card>
    <div class="flex items-center justify-between gap-4">

        <flux:heading>
            {{ $heading }}
        </flux:heading>

        {{ $actions ?? '' }}
    </div>
    <div class="flex items-center justify-between gap-4 mt-4">
        {{ $filters ?? '' }}
    </div>
    {{ $slot }}
</flux:card>
