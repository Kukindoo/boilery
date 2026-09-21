<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-primary-bg dark:bg-primary-dark-bg">
<flux:header container class="border-b border-zinc-200 bg-primary-bg dark:border-zinc-700 dark:bg-primary-dark-bg">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <a href="{{ route('home') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
        <x-app-logo />
    </a>

    <flux:navbar class="-mb-px max-lg:hidden">
        @auth
            <flux:navbar.item icon="home" :href="route('dashboard')" wire:navigate>
                Přehled
            </flux:navbar.item>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:navbar.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                    {{ __('Log Out') }}
                </flux:navbar.item>
            </form>
        @else
            <flux:navbar.item icon="key" :href="route('login')" :current="request()->routeIs('login')" wire:navigate>
                {{ __('Login') }}
            </flux:navbar.item>
        @endauth
    </flux:navbar>

</flux:header>

<!-- Mobile Menu -->
<flux:sidebar stashable sticky class="lg:hidden border-e border-zinc-200 bg-primary-bg dark:border-zinc-700 dark:bg-primary-dark-bg">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

    <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
        <x-app-logo />
    </a>

    <flux:navlist>
        <flux:navlist.group>
            <flux:navbar.item icon="key" :href="route('login')" :current="request()->routeIs('login')" wire:navigate>
                {{ __('Login') }}
            </flux:navbar.item>
        </flux:navlist.group>
    </flux:navlist>
</flux:sidebar>

{{ $slot }}

@fluxScripts
</body>
</html>
