<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @php
        $fileNameDirty = implode('_', [
            $quote->first_name,
            $quote->last_name,
            $quote->id,
            \App\Enums\FileTypes::QUOTE_SUMMARY->snake(),
        ]);

        $fileName = Str::ascii($fileNameDirty)
    @endphp
    <title>{{ $fileName ?? config('app.name') }}</title>

    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="flex items-center justify-between gap-4">
        <flux:heading>
            Poptávka {{ $quote->id }}
            <flux:text>{{ $quote->first_name }} {{ $quote->last_name }}</flux:text>
        </flux:heading>
    </div>
    <x-quotes.quote-person-info :quote="$quote" />
    <x-quotes.quote-boiler-info :quote="$quote" />
    <x-quotes.quote-attached-images :quote="$quote" />
</div>
<script>
    window.print();
</script>
</body>
</html>