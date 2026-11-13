<x-layouts::app :title="__('Tests')">
    <flux:heading size="xl">Tests</flux:heading>

    @if(session('status'))
        <flux:text role="status">{{ session('status') }}</flux:text>
    @endif

    <form action="{{ route('cold-tests.email') }}" method="POST">
        @csrf
        <flux:button type="submit">Send Cold Email Test</flux:button>
    </form>
</x-layouts::app>
