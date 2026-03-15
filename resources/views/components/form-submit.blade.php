@props([
    'value' => 'Submit',
    'class' => '',
])
<button
    type="submit"
    class="{{ $class ?: 'inline-flex items-center rounded-md border border-transparent px-4 py-2 text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2' }}"
    {{ $attributes->except(['class', 'value'])->merge([]) }}
>
    {{ $value }}
</button>
