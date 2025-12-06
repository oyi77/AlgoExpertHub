@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title ?? 'AI SRM Performance Insights' }}
@endsection

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4>{{ __('AI Smart Risk Insights') }}</h4>
                    <p class="mb-0 text-muted small">
                        {{ __('Ringkasan rekomendasi, tren, dan peringatan risiko yang dibangun dari aktivitas SRM di akun Anda.') }}
                    </p>
                </div>
                <div class="card-body">
                    <!-- Quick actions to tune ecosystem -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>{{ __('Aksi Cepat untuk Menyetel SRM') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @if (Route::has('user.filter-strategies.index'))
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="mb-1">{{ __('Refine Filter Strategies') }}</h6>
                                            <p class="mb-2 small text-muted">
                                                {{ __('Sesuaikan aturan filter Anda agar SRM bekerja hanya dengan sinyal yang paling selaras dengan gaya trading Anda.') }}
                                            </p>
                                            <a href="{{ route('user.filter-strategies.index') }}" class="btn btn-sm btn-outline-primary">
                                                {{ __('Buka Filter Strategies') }}
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                @if (Route::has('user.ai-model-profiles.index'))
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="mb-1">{{ __('Kalibrasi AI Model Profiles') }}</h6>
                                            <p class="mb-2 small text-muted">
                                                {{ __('Tinjau ulang prompt dan mode analisa AI untuk meningkatkan kualitas konfirmasi sebelum eksekusi.') }}
                                            </p>
                                            <a href="{{ route('user.ai-model-profiles.index') }}" class="btn btn-sm btn-outline-primary">
                                                {{ __('Buka AI Model Profiles') }}
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>{{ __('Rekomendasi SRM') }}</h5>
                        </div>
                        <div class="card-body">
                            @forelse($insights['recommendations'] as $recommendation)
                                <div class="alert alert-info">
                                    <strong>{{ $recommendation['title'] ?? __('Rekomendasi') }}</strong>
                                    <p>{{ $recommendation['message'] ?? '' }}</p>
                                </div>
                            @empty
                                <p class="text-muted">{{ __('Belum ada rekomendasi khusus saat ini.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Performance Trends -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>{{ __('Tren Performa') }}</h5>
                        </div>
                        <div class="card-body">
                            @forelse($insights['trends'] as $trend)
                                <div class="alert alert-{{ $trend['type'] ?? 'info' }}">
                                    <strong>{{ $trend['title'] ?? __('Trend') }}</strong>
                                    <p>{{ $trend['description'] ?? '' }}</p>
                                </div>
                            @empty
                                <p class="text-muted">{{ __('Belum ada data tren yang cukup untuk ditampilkan.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Risk Warnings -->
                    <div class="card">
                        <div class="card-header">
                            <h5>{{ __('Peringatan Risiko') }}</h5>
                        </div>
                        <div class="card-body">
                            @forelse($insights['warnings'] as $warning)
                                <div class="alert alert-warning">
                                    <strong>{{ $warning['title'] ?? __('Peringatan') }}</strong>
                                    <p>{{ $warning['message'] ?? '' }}</p>
                                </div>
                            @empty
                                <p class="text-muted">{{ __('Belum ada peringatan risiko aktif dari SRM.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

