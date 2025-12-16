@props([
    'variant' => 'info', // success, danger, warning, info
    'dismissible' => false,
])

@php
    $baseClasses = 'rounded-lg p-4 mb-4';
    
    $variantClasses = [
        'success' => 'bg-success-50 border border-success-200 text-success-800',
        'danger' => 'bg-danger-50 border border-danger-200 text-danger-800',
        'warning' => 'bg-warning-50 border border-warning-200 text-warning-800',
        'info' => 'bg-info-50 border border-info-200 text-info-800',
    ];
    
    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['info']);
    $alertId = 'alert-' . uniqid();
@endphp

<div 
    {{ $attributes->merge(['class' => $classes, 'role' => 'alert', 'id' => $alertId]) }}
    @if($dismissible) x-data="{ show: true }" x-show="show" @endif
>
    <div class="flex items-start">
        <div class="flex-1">
            {{ $slot }}
        </div>
        @if($dismissible)
            <button 
                type="button"
                class="ml-4 text-current opacity-70 hover:opacity-100 focus-ring rounded"
                @click="show = false"
                aria-label="Dismiss alert"
            >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        @endif
    </div>
</div>

