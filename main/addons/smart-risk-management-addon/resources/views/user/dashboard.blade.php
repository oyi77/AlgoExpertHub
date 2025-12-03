@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title ?? 'AI Smart Risk Management' }}
@endsection

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4>{{ __('AI Smart Risk Management Dashboard') }}</h4>
                    <p class="mb-0 text-muted small">
                        {{ __('Pantau bagaimana SRM AlgoExpertHub menyesuaikan risiko dan melindungi akun Anda secara otomatis.') }}
                    </p>
                </div>
                <div class="card-body">
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="mb-1">{{ __('Total Penyesuaian SRM') }}</h6>
                                    <h3>{{ $stats['total_adjustments'] ?? 0 }}</h3>
                                    <p class="mb-0 small opacity-75">
                                        {{ __('Jumlah trade yang telah disentuh oleh mesin SRM (lot, SL buffer, atau filter).') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="mb-1">{{ __('Rata-rata Skor Kualitas Sinyal') }}</h6>
                                    <h3>{{ number_format($stats['avg_performance_score'] ?? 0, 2) }}</h3>
                                    <p class="mb-0 small opacity-75">
                                        {{ __('Semakin tinggi, semakin konsisten kualitas sinyal yang dieksekusi.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="mb-1">{{ __('Estimasi Reduksi Slippage') }}</h6>
                                    <h3>{{ number_format($stats['slippage_reduction'] ?? 0, 2) }}%</h3>
                                    <p class="mb-0 small opacity-75">
                                        {{ __('Perkiraan pengurangan dampak slippage berkat penyesuaian SRM.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="mb-1">{{ __('Estimasi Reduksi Drawdown') }}</h6>
                                    <h3>{{ number_format($stats['drawdown_reduction'] ?? 0, 2) }}%</h3>
                                    <p class="mb-0 small opacity-75">
                                        {{ __('Semakin tinggi, semakin efektif SRM membatasi penurunan ekuitas.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Score Chart -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>{{ __('Performa SRM dari Waktu ke Waktu') }}</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="performanceChart" height="100"></canvas>
                            <p class="text-muted small mt-2">
                                {{ __('Grafik ini akan terisi seiring SRM mengumpulkan histori trade dan melakukan penyesuaian risiko.') }}
                            </p>
                        </div>
                    </div>

                    <!-- quick links to configuration addons -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>{{ __('Optimalkan Ekosistem SRM Anda') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @if (Route::has('user.filter-strategies.index'))
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="mb-1">{{ __('Filter Strategies') }}</h6>
                                            <p class="mb-2 small text-muted">
                                                {{ __('Bantu SRM dengan hanya mengizinkan sinyal yang lolos rule filter Anda (trend, volatilitas, news, dll).') }}
                                            </p>
                                            <a href="{{ route('user.filter-strategies.index') }}" class="btn btn-sm btn-outline-primary">
                                                {{ __('Kelola Filter Strategies') }}
                                            </a>
                                        </div>
                                    </div>
                                @endif

                                @if (Route::has('user.ai-model-profiles.index'))
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="mb-1">{{ __('AI Model Profiles') }}</h6>
                                            <p class="mb-2 small text-muted">
                                                {{ __('Tambahkan layer analisa AI sebelum eksekusi supaya SRM bekerja di atas sinyal yang sudah terkonfirmasi.') }}
                                            </p>
                                            <a href="{{ route('user.ai-model-profiles.index') }}" class="btn btn-sm btn-outline-primary">
                                                {{ __('Kelola AI Model Profiles') }}
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Recent Adjustments -->
                    <div class="card">
                        <div class="card-header">
                            <h5>{{ __('Penyesuaian Terbaru oleh SRM') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Waktu') }}</th>
                                            <th>{{ __('Koneksi') }}</th>
                                            <th>{{ __('Signal ID') }}</th>
                                            <th>{{ __('Jenis Penyesuaian') }}</th>
                                            <th>{{ __('Alasan SRM') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recent_adjustments as $adjustment)
                                            <tr>
                                                <td>{{ $adjustment->created_at->format('Y-m-d H:i') }}</td>
                                                <td>{{ $adjustment->connection->name ?? 'N/A' }}</td>
                                                <td>#{{ $adjustment->signal_id ?? 'N/A' }}</td>
                                                <td>
                                                    @if($adjustment->srm_adjusted_lot)
                                                        <span class="badge badge-info">Lot Adjustment</span>
                                                    @endif
                                                    @if($adjustment->srm_sl_buffer)
                                                        <span class="badge badge-warning">SL Buffer</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        $reason = json_decode($adjustment->srm_adjustment_reason ?? '{}', true);
                                                    @endphp
                                                    {{ $reason['reasons'][0]['message'] ?? 'N/A' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">
                                                    {{ __('Belum ada penyesuaian SRM yang tercatat di akun Anda.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('performanceChart');
        if (ctx) {
            const labels = @json($chart_labels ?? []);
            const values = @json($chart_values ?? []);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '{{ __('Rata-rata skor kualitas sinyal per hari') }}',
                        data: values,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.15)',
                        fill: true,
                        tension: 0.25
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: '{{ __('Skor') }}'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: '{{ __('Tanggal') }}'
                            }
                        }
                    }
                }
            });
        }
    </script>
@endsection

