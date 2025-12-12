@props([
    'variant' => 'primary', // primary, success, danger, warning, info, gray
    'size' => 'md', // sm, md
])

@php
    $baseClasses = 'inline-flex items-center font-medium rounded-full';
    
    $variantClasses = [
        'primary' => 'bg-primary-100 text-primary-800',
        'success' => 'bg-success-100 text-success-800',
        'danger' => 'bg-danger-100 text-danger-800',
        'warning' => 'bg-warning-100 text-warning-800',
        'info' => 'bg-info-100 text-info-800',
        'gray' => 'bg-gray-100 text-gray-800',
    ];
    
    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-3 py-1 text-sm',
    ];
    
    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>

