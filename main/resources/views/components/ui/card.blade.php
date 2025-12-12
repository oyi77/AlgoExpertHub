@props([
    'variant' => 'standard', // standard, elevated, outlined
    'padding' => true,
])

@php
    $baseClasses = 'rounded-lg transition-standard';
    
    $variantClasses = [
        'standard' => 'bg-white border border-gray-200 shadow-sm',
        'elevated' => 'bg-white shadow-lg hover:shadow-xl',
        'outlined' => 'bg-white border-2 border-gray-300',
    ];
    
    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['standard']);
    $paddingClass = $padding ? 'p-6' : '';
@endphp

<div {{ $attributes->merge(['class' => $classes . ' ' . $paddingClass]) }}>
    {{ $slot }}
</div>

