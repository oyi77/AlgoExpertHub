@extends(Config::theme() . 'layout.auth')

@section('content')
    <div class="sp_site_card">
        <div class="card-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <h4>{{ __($title) }}</h4>
                        <div>
                            <form action="{{ route('user.trading-presets.set-default', $preset) }}" 
                                  method="POST" 
                                  class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fa fa-star"></i> {{ __('Set as Default') }}
                                </button>
                            </form>
                            <form action="{{ route('user.trading-presets.destroy', $preset) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('{{ __('Are you sure you want to delete this preset?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fa fa-trash"></i> {{ __('Delete') }}
                                </button>
                            </form>
                            <a href="{{ route('user.trading-presets.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fa fa-arrow-left"></i> {{ __('Go Back') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(auth()->user()->default_preset_id == $preset->id)
                            <div class="alert alert-info">
                                <i class="fa fa-star"></i>
                                {{ __('This is your default preset.') }}
                            </div>
                        @endif

                        <form action="{{ route('user.trading-presets.update', $preset) }}" method="post" id="preset-form">
                            @csrf
                            @method('PUT')

                            {{-- Form Tabs --}}
                            <ul class="nav nav-tabs mb-4" id="presetTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab">
                                        <i class="fa fa-info-circle"></i> {{ __('Basic') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="position-tab" data-toggle="tab" href="#position" role="tab">
                                        <i class="fa fa-chart-line"></i> {{ __('Position & Risk') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="sl-tp-tab" data-toggle="tab" href="#sl-tp" role="tab">
                                        <i class="fa fa-shield-alt"></i> {{ __('SL & TP') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="advanced-tab" data-toggle="tab" href="#advanced" role="tab">
                                        <i class="fa fa-cogs"></i> {{ __('Advanced') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="layering-tab" data-toggle="tab" href="#layering" role="tab">
                                        <i class="fa fa-layer-group"></i> {{ __('Layering & Hedging') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="schedule-tab" data-toggle="tab" href="#schedule" role="tab">
                                        <i class="fa fa-calendar-alt"></i> {{ __('Schedule') }}
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content" id="presetTabsContent">
                                {{-- Basic Tab --}}
                                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                    @include('trading-preset-addon::backend.presets.partials.basic-info')
                                </div>

                                {{-- Position & Risk Tab --}}
                                <div class="tab-pane fade" id="position" role="tabpanel">
                                    @include('trading-preset-addon::backend.presets.partials.position-risk')
                                </div>

                                {{-- SL & TP Tab --}}
                                <div class="tab-pane fade" id="sl-tp" role="tabpanel">
                                    @include('trading-preset-addon::backend.presets.partials.sl-tp')
                                </div>

                                {{-- Advanced Tab --}}
                                <div class="tab-pane fade" id="advanced" role="tabpanel">
                                    @include('trading-preset-addon::backend.presets.partials.advanced-features')
                                </div>

                                {{-- Layering & Hedging Tab --}}
                                <div class="tab-pane fade" id="layering" role="tabpanel">
                                    @include('trading-preset-addon::backend.presets.partials.layering-hedging')
                                </div>

                                {{-- Schedule Tab --}}
                                <div class="tab-pane fade" id="schedule" role="tabpanel">
                                    @include('trading-preset-addon::backend.presets.partials.schedule-target')
                                </div>
                            </div>

                            {{-- Meta Fields (User Only) --}}
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fa fa-cog mr-2"></i>
                                        {{ __('Visibility Settings') }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="visibility">{{ __('Visibility') }}</label>
                                                <select class="form-control @error('visibility') is-invalid @enderror" 
                                                        id="visibility" 
                                                        name="visibility">
                                                    <option value="PRIVATE" {{ old('visibility', $preset->visibility ?? 'PRIVATE') == 'PRIVATE' ? 'selected' : '' }}>
                                                        {{ __('Private') }} (Only You)
                                                    </option>
                                                    <option value="PUBLIC_MARKETPLACE" {{ old('visibility', $preset->visibility ?? '') == 'PUBLIC_MARKETPLACE' ? 'selected' : '' }}>
                                                        {{ __('Public Marketplace') }} (Visible to All)
                                                    </option>
                                                </select>
                                                @error('visibility')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                                <small class="form-text text-muted">{{ __('Who can see this preset') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch mt-4">
                                                    <input type="checkbox" 
                                                           class="custom-control-input" 
                                                           id="clonable" 
                                                           name="clonable" 
                                                           value="1"
                                                           {{ old('clonable', $preset->clonable ?? true) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="clonable">
                                                        {{ __('Allow Cloning') }}
                                                    </label>
                                                </div>
                                                <small class="form-text text-muted">{{ __('Allow other users to clone this preset') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Form Actions --}}
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> {{ __('Update Preset') }}
                                </button>
                                <a href="{{ route('user.trading-presets.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-times"></i> {{ __('Cancel') }}
                                </a>
                            </div>
                        </form>
        </div>
    </div>
@endsection

@push('external-style')
    <link rel="stylesheet" href="{{ Config::cssLib('backend', 'select2.min.css') }}">
@endpush

@push('external-script')
    <script src="{{ Config::jsLib('backend', 'select2.min.js') }}"></script>
@endpush

@push('script')
    @include('trading-preset-addon::backend.presets.partials.scripts')
@endpush

