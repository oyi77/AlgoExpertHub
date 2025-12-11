@props([
  'variant' => 'primary',
  'size' => 'md',
  'type' => 'button',
  'text' => null
])
@php
  $base = 'btn transition-standard';
  $variants = [
    'primary' => 'btn-primary',
    'outline' => 'btn-outline',
  ];
  $sizes = [
    'sm' => 'btn-sm',
    'md' => '',
    'lg' => 'btn-lg'
  ];
  $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? ''));
@endphp
<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
  {{ $text }}
</button>
