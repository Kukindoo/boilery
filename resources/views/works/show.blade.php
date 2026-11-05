<x-layouts::app :title="__('Zakázky')">
    <div class="space-y-6">
        <x-stellaris.container.model-card
                :heading="__('Zakázka :order_id', ['order_id' => $work->order_id])">
            <x-slot name="subheading">
                <x-customers.customer-name :customer="$work->user" />
            </x-slot>
            <x-slot name="actions">
                <div>
                    <flux:modal.trigger name="service-protocol-preview">
                        <flux:button size="sm" icon="document-text">
                            Náhled servisního protokolu
                        </flux:button>
                    </flux:modal.trigger>

                    @can('update', $work)
                        <flux:button size="sm" icon="pencil" :href="route('works.edit', $work)" class="inline-block">
                            Upravit
                        </flux:button>
                    @endcan

                    @if( auth()->user()->can('start', $work) )
                        <form method="POST" action="{{ route('works.start', $work) }}" class="inline-block">
                            @csrf
                            <flux:button type="submit" icon="check" size="sm">
                                Zahájit zakázku
                            </flux:button>
                        </form>
                    @endif

                    @if( auth()->user()->can('end', $work) )
                        <form method="POST" action="{{ route('works.end', $work) }}" class="inline-block">
                            @csrf
                            <flux:button type="submit" icon="check" size="sm">
                                Dokončit zakázku
                            </flux:button>
                        </form>
                        <form method="POST" action="{{ route('works.cancel', $work) }}" class="inline-block">
                            @csrf
                            <flux:button type="submit" icon="x-mark" size="sm">
                                Zrušit zakázku
                            </flux:button>
                        </form>
                    @endif
                </div>
            </x-slot>

            <x-works.work-info :work="$work"/>
            <x-works.work-boiler-info :work="$work"/>
            <x-works.work-repair-info :work="$work"/>
            <x-works.work-transport-info :work="$work"/>
            <x-works.work-attached-images :work="$work"/>

        </x-stellaris.container.model-card>

        <flux:modal name="service-protocol-preview" class="w-full max-w-5xl">
            <div class="space-y-4">
                <flux:heading size="lg">Servisní protokol – {{ $work->order_id }}</flux:heading>

                <iframe
                    title="Náhled servisního protokolu {{ $work->order_id }}"
                    class="h-[75vh] w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700"
                    sandbox=""
                    srcdoc="{{ view('works.pdfs.service-protocol', [
                        'work' => $work,
                        'customer' => [
                            'name' => $work->user?->name,
                            'address' => $work->quote?->address,
                            'phone' => $work->quote?->phone,
                        ],
                    ])->render() }}"
                ></iframe>

                <div class="flex justify-end">
                    <flux:modal.close>
                        <flux:button>Zavřít</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    </div>
</x-layouts::app>
