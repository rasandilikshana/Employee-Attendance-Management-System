@props([
    'class' => '',
])

<div {{ $attributes->class(['px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800', $class]) }}>
    {{ $slot }}
</div>