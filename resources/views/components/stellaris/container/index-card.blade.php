<div class="flex w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center justify-between gap-4">
        <flux:heading>
            {{ $heading }}
            <flux:text>{{ $subheading ?? '' }}</flux:text>
        </flux:heading>
        <div class="flex space-x-2">
            {{ $actions ?? '' }}
        </div>
    </div>
    {{ $slot }}
</div>