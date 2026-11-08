@can('manageSpareParts', $work)
    <livewire:spare-parts.work-spare-part-add :work="$work" @spare-part-added="$refresh" />
@endcan
