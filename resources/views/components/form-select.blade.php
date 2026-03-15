@props([
    'name',
    'label' => '',
    'options' => [],
    'selectedValues' => [],
    'multiple' => false,
])
@php
    $selectedValues = \Illuminate\Support\Arr::wrap($selectedValues);
@endphp
<div>
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif
    <select
        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
        id="{{ $name }}"
        {{ $multiple ? 'multiple' : '' }}
        {{ $attributes->merge(['class' => 'rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'])->only('class') }}
        {{ $attributes->except('class') }}
    >
        @foreach($options as $value => $label)
            <option value="{{ $value }}" @selected(in_array($value, $selectedValues))>
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>
