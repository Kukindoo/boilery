@php
    use App\Enums\Permissions;
    use App\Enums\IssueStatus;
@endphp

<div class="space-y-4">
    @if (auth()->user()->can(($form->issue === null ? Permissions::ISSUE_CREATE_ALL : Permissions::ISSUE_EDIT_ALL)->label()))
        <flux:select variant="combobox" wire:model="form.userId" label="Zákazník" placeholder="Hledejte podle jména" empty="Žádní zákazníci nenalezeni." required>
            @foreach ($form->availableCustomers() as $customer)
                <flux:select.option :value="$customer->id">
                    {{$customer->id}} - {{ $customer->name }}
                </flux:select.option>
            @endforeach
        </flux:select>
    @endif
    @if ($form->issue !== null)
        <flux:select wire:model="form.status" label="Status">
            @foreach (IssueStatus::cases() as $status)
                <flux:select.option :value="$status->value">
                    {{ $status->label() }}
                </flux:select.option>
            @endforeach
        </flux:select>
        <flux:date-picker wire:model="form.startedAt" label="Zahájeno" />
        <flux:date-picker wire:model="form.finishedAt" label="Dokončeno" />
    @endif
</div>
