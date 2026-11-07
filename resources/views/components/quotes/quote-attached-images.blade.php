@props(['quote'])

@php use App\Enums\FileTypes; @endphp
<div class="overflow-hidden bg-card-bg shadow-sm sm:rounded-lg dark:bg-card-dark-bg dark:shadow-none dark:inset-ring dark:inset-ring-primary-dark-border/10">
    <div class="px-4 py-6 sm:px-6">
        <h3 class="text-base/7 font-semibold text-heading-text dark:text-primary-dark-text">
            Přílohy
        </h3>
    </div>
    <div class="border-t border-subtle-border dark:border-primary-dark-border/5">
        <dl class="divide-y divide-subtle-border dark:divide-primary-dark-border/5">
            @if($quote->files()->count() === 0)
                <div class="px-4 py-6 sm:px-6">
                    Bez Přílohy
                    @else
                        <div class="px-4 py-6 sm:px-6">
                            <ul role="list"
                                class="divide-y space-y-4 divide-subtle-border rounded-md border border-primary-border dark:divide-primary-dark-border/5 dark:border-primary-dark-border/10">
                                @foreach($quote->files as $file)
                                    @php
                                        $file_type = $file->file_type ?? FileTypes::UNKNOWN;
                                    @endphp
                                    @if ( str_starts_with($file->mime_type, 'image/') )
                                        <flux:heading class="p-6">
                                            {{ $file_type->label()  }}
                                        </flux:heading>
                                        <img
                                                src="{{ route('files.preview', ['quote', $quote, $file]) }}"
                                                alt="{{ $file_type->label() }}"
                                                class="max-h-[75vh] w-full object-contain"
                                        >
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                </div>
            @endif
        </dl>
    </div>
</div>
