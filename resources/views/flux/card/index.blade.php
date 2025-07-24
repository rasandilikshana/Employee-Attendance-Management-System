@props([
    'class' => '',
])

<div {{ $attributes->class(['bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800 shadow-sm p-6', $class]) }}>
    {{ $slot }}
</div>