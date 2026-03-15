@props([
    'action',
    'method' => 'POST',
    'class' => '',
])
<form
    action="{{ $action }}"
    method="{{ strtoupper($method) }}"
    class="{{ $class }}"
    {{ $attributes->except(['action', 'method', 'class']) }}
>
    @csrf
    {{ $slot }}
</form>
