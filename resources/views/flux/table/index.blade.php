@props([
    'class' => '',
])

<div class="overflow-x-auto">
    <table {{ $attributes->class(['min-w-full divide-y divide-zinc-200 dark:divide-zinc-700', $class]) }}>
        {{ $slot }}
    </table>
</div>