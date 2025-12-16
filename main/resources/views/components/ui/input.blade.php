@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'helper' => null,
    'required' => false,
    'id' => null,
])

@php
    $inputId = $id ?? 'input-' . uniqid();
    $errorId = $error ? $inputId . '-error' : null;
    $helperId = $helper ? $inputId . '-helper' : null;
    
    $baseClasses = 'block w-full rounded-md border-gray-300 shadow-sm transition-standard focus-ring focus:border-primary-500 focus:ring-primary-500';
    $errorClasses = $error ? 'border-danger-500 focus:border-danger-500 focus:ring-danger-500' : '';
    $classes = $baseClasses . ' ' . $errorClasses;
@endphp

<div class="mb-4">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700 mb-1">
            {{ $label }}
            @if($required)
                <span class="text-danger-500" aria-label="required">*</span>
            @endif
        </label>
    @endif
    
    <input
        type="{{ $type }}"
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($error) aria-invalid="true" aria-describedby="{{ $errorId }} @if($helper) {{ $helperId }} @endif" @endif
        @if($helper && !$error) aria-describedby="{{ $helperId }}" @endif
        @if($required) required @endif
    >
    
    @if($helper && !$error)
        <p id="{{ $helperId }}" class="mt-1 text-sm text-gray-500">{{ $helper }}</p>
    @endif
    
    @if($error)
        <p id="{{ $errorId }}" class="mt-1 text-sm text-danger-600" role="alert">{{ $error }}</p>
    @endif
</div>

