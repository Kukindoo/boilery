<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('Page not found')])
    </head>
    <body class="bg-primary-bg font-sans text-neutral-900 antialiased dark:bg-primary-dark-bg dark:text-primary-dark-text">
        <main class="flex min-h-svh items-center justify-center px-6 py-16">
            <div class="flex max-w-lg flex-col items-center gap-8 text-center">
                <h1 class="text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                    {{ __('Oops, we couldn’t find that page.') }}
                </h1>
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-lg bg-neutral-900 px-5 py-3 text-sm font-medium text-white transition hover:bg-neutral-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-neutral-900 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 dark:focus-visible:outline-white">
                    {{ __('Back to home') }}
                </a>
            </div>
        </main>
    </body>
</html>
