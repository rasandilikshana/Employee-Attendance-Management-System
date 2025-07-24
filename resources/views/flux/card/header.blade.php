@props([
    'class' => '',
])

<div {{ $attributes->class(['px-6 py-4 border-b border-zinc-200 dark:border-zinc-800', $class]) }}>
    {{ $slot }}
</div>