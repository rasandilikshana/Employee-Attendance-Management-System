@props([
    'class' => '',
])

<tr {{ $attributes->class(['hover:bg-zinc-50 dark:hover:bg-zinc-800', $class]) }}>
    {{ $slot }}
</tr>