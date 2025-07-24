@props([
    'class' => '',
])

<th {{ $attributes->class(['px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider', $class]) }}>
    {{ $slot }}
</th>