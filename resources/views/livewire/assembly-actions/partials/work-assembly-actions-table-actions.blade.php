@php
    use App\Models\AssemblyAction;
@endphp

@can('manageAssemblyActions', $work)
    <livewire:assembly-actions.work-assembly-action-add :work="$work" @assembly-action-added="$refresh" />
@endcan
