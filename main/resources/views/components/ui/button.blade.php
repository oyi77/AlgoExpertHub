@props([
    'variant' => 'primary', // primary, secondary, outline, ghost
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'loading' => false,
    'disabled' => false,
    'icon' => null,
    'iconPosition' => 'left', // left, right
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-standard focus-ring disabled:opacity-50 disabled:cursor-not-allowed';
    
    $variantClasses = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800 shadow-md hover:shadow-lg',
        'secondary' => 'bg-gray-200 text-gray-900 hover:bg-gray-300 active:bg-gray-400',
        'outline' => 'border-2 border-primary-600 text-primary-600 hover:bg-primary-50 active:bg-primary-100',
        'ghost' => 'text-gray-700 hover:bg-gray-100 active:bg-gray-200',
    ];
    
    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-base',
        'lg' => 'px-6 py-3 text-lg',
    ];
    
    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled || $loading) disabled @endif
>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif
    
    @if($icon && $iconPosition === 'left' && !$loading)
        <span class="mr-2">{{ $icon }}</span>
    @endif
    
    {{ $slot }}
    
    @if($icon && $iconPosition === 'right')
        <span class="ml-2">{{ $icon }}</span>
    @endif
</button>

