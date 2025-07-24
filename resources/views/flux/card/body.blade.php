@props([
    'class' => '',
])

<div {{ $attributes->class(['px-6 py-4', $class]) }}>
    {{ $slot }}
</div>