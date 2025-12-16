@extends('backend.layout.master')

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">{{ $title }}</h4>
                    <div>
                        <select id="theme-selector" class="form-control form-control-sm" style="width: auto; display: inline-block;">
                            <option value="">{{ __('Select Theme') }}</option>
                            @foreach($themes ?? [] as $theme)
                                <option value="{{ $theme['name'] }}" {{ (isset($selectedTheme) && $selectedTheme === $theme['name']) ? 'selected' : '' }}>
                                    {{ $theme['display_name'] }} {{ $theme['is_active'] ? '(Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    @if(isset($selectedTheme))
                        <div class="alert alert-info">
                            <strong>{{ __('Theme:') }}</strong> {{ collect($themes ?? [])->firstWhere('name', $selectedTheme)['display_name'] ?? $selectedTheme }}
                        </div>
                    @endif
                    <div class="row">
                        @forelse($themeSections ?? $sections as $section)
                            @php
                                $sectionName = is_array($section) ? $section['name'] : $section;
                                $hasContent = is_array($section) ? ($section['has_content'] ?? false) : true;
                            @endphp
                            <div class="col-md-3 mb-3">
                                <div class="card {{ !$hasContent ? 'border-warning' : '' }}">
                                    <div class="card-body text-center">
                                        <h5>{{ ucwords(str_replace(['_', '-'], ' ', $sectionName)) }}</h5>
                                        @if(!$hasContent && isset($selectedTheme))
                                            <span class="badge badge-warning badge-sm mb-2">{{ __('No Content Yet') }}</span>
                                        @endif
                                        <div class="btn-group-vertical w-100" style="gap: 5px;">
                                            <a href="{{ route('admin.page-builder.sections.edit', $sectionName) }}?theme={{ $selectedTheme ?? '' }}" class="btn btn-sm btn-primary">
                                                <i data-feather="edit"></i> {{ __('Edit in Builder') }}
                                            </a>
                                            <a href="{{ route('admin.frontend.section.manage', $sectionName) }}" class="btn btn-sm btn-outline-secondary">
                                                <i data-feather="settings"></i> {{ __('Legacy Editor') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info text-center">
                                    {{ __('No sections found. Please select a theme first.') }}
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    document.getElementById('theme-selector').addEventListener('change', function() {
        const theme = this.value;
        if (theme) {
            window.location.href = '{{ route("admin.page-builder.sections.index") }}?theme=' + theme;
        } else {
            window.location.href = '{{ route("admin.page-builder.sections.index") }}';
        }
    });
</script>
@endpush
@endsection

