<div class="px-6 pt-20 pb-24 sm:pb-32 lg:px-8 lg:py-48">
    @if(! $submitted)
        <form id="contact-quote-form" wire:submit="submit">
            <div class="mx-auto max-w-xl lg:mr-0 lg:max-w-lg">
                @include('livewire.forms.partials.quote-form-inputs')
                <div class="mt-8 flex justify-end">
                    <button type="submit"
                            class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-primary-dark-button-bg dark:hover:bg-primary-dark-button-bg/50 dark:focus-visible:outline-primary-dark-button-bg">
                        Odeslat
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="rounded-md bg-green-50 p-4 dark:bg-green-500/10 dark:outline dark:outline-green-500/20">
            <div class="flex">
                <div class="shrink-0">
                    <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true"
                         class="size-5 text-green-400">
                        <path d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                              clip-rule="evenodd" fill-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Poptávka přijata</h3>
                    <div class="mt-2 text-sm text-green-700 dark:text-green-200/85">
                        <p>Vaše potávka byla přijata. Měli by jste dostat potvrzující e-mail.</p>
                    </div>
                    <div class="mt-4">
                        <div class="-mx-2 -my-1.5 flex">
                            <p class="rounded-md bg-green-50 px-2 py-1.5 text-sm font-medium text-green-800  dark:bg-transparent dark:text-green-200">
                                Brzy se Vám ozveme
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

