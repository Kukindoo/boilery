 <div class="flex flex-col gap-6">
    <x-auth-header title="Zapomenuté heslo" description="Zadejte svůj e-mail aby jste obdrželi resetovací odkaz" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form method="POST" wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            label="E-mail"
            type="email"
            required
            autofocus
            placeholder="email@example.com"
        />

        <flux:button variant="primary" type="submit" class="w-full">Poslat resetovací odkaz</flux:button>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
        <span>nebo, vrátit se na </span>
        <flux:link :href="route('login')" wire:navigate>Přihlášení</flux:link>
    </div>
</div>
