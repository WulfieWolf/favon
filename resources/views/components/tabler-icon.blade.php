@props([
    'name',
    'class' => 'size-4',
    'strokeWidth' => 2,
])

@php
    $iconPaths = config('tabler-icons.'.$name)
        ?? config('tabler-icons.question-mark');
@endphp

<svg
    {{ $attributes->merge(['class' => $class]) }}
    xmlns="http://www.w3.org/2000/svg"
    width="24"
    height="24"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $strokeWidth }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @foreach ($iconPaths as $path)
        <path d="{{ $path }}" />
    @endforeach
</svg>
