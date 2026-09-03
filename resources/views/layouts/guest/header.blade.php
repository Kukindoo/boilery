<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
<flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <a href="{{ route('dashboard') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
        <x-app-logo />
    </a>

    <flux:navbar class="-mb-px max-lg:hidden">
        <flux:navbar.item icon="home" :href="route('login')" :current="request()->routeIs('login')" wire:navigate>
            {{ __('Login') }}
        </flux:navbar.item>
        <flux:navbar.item icon="user-group" :href="route('register')" :current="request()->routeIs('register')" wire:navigate>
            {{ __('Register') }}
        </flux:navbar.item>
    </flux:navbar>

</flux:header>

<!-- Mobile Menu -->
<flux:sidebar stashable sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

    <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
        <x-app-logo />
    </a>

    <flux:navlist>
        <flux:navlist.group>
            <flux:navbar.item icon="home" :href="route('login')" :current="request()->routeIs('login')" wire:navigate>
                {{ __('Login') }}
            </flux:navbar.item>
            <flux:navbar.item icon="user-group" :href="route('register')" :current="request()->routeIs('register')" wire:navigate>
                {{ __('Register') }}
            </flux:navbar.item>
        </flux:navlist.group>
    </flux:navlist>
</flux:sidebar>

{{ $slot }}

@fluxScripts
</body>
</html>
