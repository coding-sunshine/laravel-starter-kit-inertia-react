@props([
    'name',
    'label' => '',
    'labelClasses' => 'block text-sm font-medium text-gray-700',
    'class' => '',
])
<div>
    @if($label !== false)
        <label for="{{ $name }}" class="{{ $labelClasses }}">{{ $label ?: ucfirst(str_replace('_', ' ', $name)) }}</label>
    @endif
    <input
        type="email"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name) }}"
        {{ $attributes->merge(['class' => $class ?: 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'])->only('class') }}
        {{ $attributes->except('class') }}
    />
</div>
