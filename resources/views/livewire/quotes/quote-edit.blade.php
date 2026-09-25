<div class="px-6">
    <form id="contact-quote-form" wire:submit="submit">
        <div class="mx-auto max-w-xl lg:mr-0 lg:max-w-lg">
            @include('livewire.forms.partials.quote-form-inputs-without-files')

            <div class="mt-8 flex justify-end">
                <button type="submit"
                        class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-primary-dark-button-bg dark:hover:bg-primary-dark-button-bg/50 dark:focus-visible:outline-primary-dark-button-bg">
                    Upravit
                </button>
            </div>
        </div>
    </form>
</div>