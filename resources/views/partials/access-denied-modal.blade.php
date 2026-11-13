<div
    x-data="{
        timeout: null,
        show() {
            clearTimeout(this.timeout);
            this.$flux.modal('access-denied').show();
            this.timeout = setTimeout(() => this.$flux.modal('access-denied').close(), 5000);
        },
        destroy() { clearTimeout(this.timeout); }
    }"
    x-on:access-denied.window="show()"
    @if (session('access_denied'))
        x-init="$nextTick(() => show())"
    @endif
>
    <flux:modal name="access-denied" aria-labelledby="access-denied-title" class="h-[40vh] max-h-[90dvh] w-[90vw] max-w-none bg-primary-bg! dark:bg-primary-bg! sm:w-[40vw]">
        <div class="flex h-full flex-col items-center justify-center gap-4 text-center">
            <flux:icon.x-mark class="size-16 text-red-600" aria-hidden="true" />
            <flux:heading id="access-denied-title" size="lg" class="text-zinc-900!">{{ __('Access denied') }}</flux:heading>
            <flux:text class="text-zinc-600!">{{ __('Oops, that’s not where you should go.') }}</flux:text>
            <flux:modal.close>
                <flux:button>{{ __('Dismiss') }}</flux:button>
            </flux:modal.close>
        </div>
    </flux:modal>
</div>
