@props([
    'class' => '',
])

<tbody {{ $attributes->class(['bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700', $class]) }}>
    {{ $slot }}
</tbody>