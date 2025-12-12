@props([
    'title',
    'value',
    'icon' => null,
    'trend' => null, // 'up', 'down', or null
    'trendValue' => null,
])

@php
    $trendClasses = [
        'up' => 'text-success-600',
        'down' => 'text-danger-600',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg p-6 shadow-md']) }}>
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-600 mb-1">{{ $title }}</p>
            <p class="text-2xl font-bold text-gray-900">{{ $value }}</p>
            @if($trend && $trendValue)
                <div class="mt-2 flex items-center">
                    @if($trend === 'up')
                        <svg class="w-4 h-4 {{ $trendClasses['up'] }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <svg class="w-4 h-4 {{ $trendClasses['down'] }}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                    <span class="ml-1 text-sm font-medium {{ $trendClasses[$trend] ?? '' }}">
                        {{ $trendValue }}
                    </span>
                </div>
            @endif
        </div>
        @if($icon)
            <div class="ml-4 flex-shrink-0">
                <div class="p-3 bg-primary-100 rounded-lg">
                    {!! $icon !!}
                </div>
            </div>
        @endif
    </div>
</div>

