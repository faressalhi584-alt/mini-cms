@props(['href', 'active' => false])

@php
    $classes = $active
        ? 'border-b-2 border-red-600 text-gray-900'
        : 'text-gray-600 hover:text-gray-900';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'whitespace-nowrap px-1 py-4 '.$classes]) }}>
    {{ $slot }}
</a>
