@props([
  'id' => null,
  'label' => null,
  'type' => 'text',
  'name' => null,
  'value' => null,
  'hint' => null,
  'error' => null,
  'icon' => null,
])
@php
  $inputId = $id ?? $name;
@endphp
<div class="mb-3">
  @if($label)
    <label for="{{ $inputId }}" class="form-label">{{ $label }}</label>
  @endif
  <div class="sp_input_icon_field">
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $inputId }}" value="{{ $value }}" {{ $attributes->merge(['class' => 'form-control focus-ring']) }}>
    @if($icon)
      <i class="{{ $icon }}" aria-hidden="true"></i>
    @endif
  </div>
  @if($hint)
    <div class="form-hint" id="{{ $inputId }}-hint">{{ $hint }}</div>
  @endif
  @if($error)
    <div class="form-error" role="alert">{{ $error }}</div>
  @endif
</div>

