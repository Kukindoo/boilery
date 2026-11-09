<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-primary-bg dark:bg-primary-dark-bg">
<flux:sidebar sticky stashable
              class="border-e border-primary-border bg-navigation-bg dark:border-primary-dark-border-strong dark:bg-navigation-dark-bg">
    <flux:sidebar.toggle class="lg:hidden" icon="x-mark"/>

    <a href="{{ route('home') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
        <x-app-logo/>
    </a>

    <flux:navlist variant="outline">
        <flux:navlist.group :heading="__('Business')" class="grid">
            <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                               wire:navigate>
                Přehled
            </flux:navlist.item>
            @can('viewAny', \App\Models\Quote::class)
                <flux:navlist.item icon="clipboard-document-list" :href="route('quotes.index')"
                                   :current="request()->routeIs('quotes.*')" wire:navigate>
                    Poptávky
                </flux:navlist.item>
            @endcan
            @can('viewAny', \App\Models\Work::class)
                <flux:navlist.item icon="clipboard-document-list" :href="route('works.index')"
                                   :current="request()->routeIs('works.*')" wire:navigate>
                    Zakázky
                </flux:navlist.item>
            @endcan
            @can('viewAny', \App\Models\User::class)
                <flux:navlist.item icon="users" :href="route('customers.index')"
                                   :current="request()->routeIs('customers.*')" wire:navigate>
                    Zákazníci
                </flux:navlist.item>
            @endcan
        </flux:navlist.group>
    </flux:navlist>

    <flux:spacer/>
            @can('viewAny', \App\Models\SparePart::class)
                <flux:navlist.item icon="clipboard-document-list" :href="route('spare-parts.index')"
                                   :current="request()->routeIs('spare-parts.*')" wire:navigate>
                    Náhradní díly
                </flux:navlist.item>
            @endcan

    <!-- Desktop User Menu -->
    <flux:dropdown class="hidden lg:block" position="bottom" align="start">
        <flux:profile
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
                icon:trailing="chevrons-up-down"
        />

        <flux:menu class="w-[220px]">
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-secondary-bg text-primary-text dark:bg-secondary-dark-bg dark:text-primary-dark-text"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <flux:menu.radio.group>
                <flux:menu.item :href="route('settings.profile')" icon="cog"
                                wire:navigate>{{ __('Settings') }}</flux:menu.item>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:sidebar>

<!-- Mobile User Menu -->
<flux:header class="lg:hidden">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left"/>

    <flux:spacer/>

    <flux:dropdown position="top" align="end">
        <flux:profile
                :initials="auth()->user()->initials()"
                icon-trailing="chevron-down"
        />

        <flux:menu>
            <flux:menu.radio.group>
                <div class="p-0 text-sm font-normal">
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-secondary-bg text-primary-text dark:bg-secondary-dark-bg dark:text-primary-dark-text"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </div>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <flux:menu.radio.group>
                <flux:menu.item :href="route('settings.profile')" icon="cog"
                                wire:navigate>{{ __('Settings') }}</flux:menu.item>
            </flux:menu.radio.group>

            <flux:menu.separator/>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                    {{ __('Log Out') }}
                </flux:menu.item>
            </form>
        </flux:menu>
    </flux:dropdown>
</flux:header>

{{ $slot }}

@fluxScripts
</body>
</html>
