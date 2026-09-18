@props(['quote'])

@php use App\Enums\FileTypes; @endphp
<div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800/50 dark:shadow-none dark:inset-ring dark:inset-ring-white/10">
    <div class="px-4 py-6 sm:px-6">
        <h3 class="text-base/7 font-semibold text-gray-900 dark:text-white">
            Přílohy
        </h3>
    </div>
    <div class="border-t border-gray-100 dark:border-white/5">
        <dl class="divide-y divide-gray-100 dark:divide-white/5">
            @if($quote->files()->count() === 0)
                <div class="px-4 py-6 sm:px-6">
                    Bez Přílohy
                    @else
                        <div class="px-4 py-6 sm:px-6">
                            <ul role="list"
                                class="divide-y space-y-4 divide-gray-100 rounded-md border border-gray-200 dark:divide-white/5 dark:border-white/10">
                                @foreach($quote->files as $file)
                                    @php
                                        $file_type = FileTypes::tryFrom($file->file_type) ?? FileTypes::UNKNOWN;
                                    @endphp
                                    @if ( str_starts_with($file->mime_type, 'image/') )
                                        <flux:heading class="p-6">
                                            {{ $file_type->label()  }}
                                        </flux:heading>
                                        <img
                                                src="{{ route('quotes.files.preview', [$quote, $file]) }}"
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