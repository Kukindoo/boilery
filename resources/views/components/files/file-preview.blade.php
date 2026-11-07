@php
    use App\Contracts\OwnsFiles;
    use Illuminate\Database\Eloquent\Model;
    use App\Models\Quote;
    use App\Models\Work;
    use App\Models\File;
@endphp

@props(['file', 'model'])

@php
    /** @var File $file */
    /** @var Model&OwnsFiles $model */

    $modelType = match (true) {
        $model instanceof Quote => 'quote',
        $model instanceof Work => 'work',
    };
@endphp

@if (str_starts_with($file->mime_type, 'image/'))
    <img
            src="{{ route('files.preview', [$modelType, $model, $file]) }}"
            alt="{{ $file->file_type->label() }}"
            {{ $attributes->class(['max-h-[75vh] w-full object-contain']) }}
    >
@elseif ($file->mime_type === 'application/pdf')
    <iframe
            title="{{ $file->label }}"
            src="{{ route('files.preview', [$modelType, $model, $file]) }}"
            {{ $attributes->class(['h-[75vh] w-full rounded-lg border']) }}
    ></iframe>
@endif
