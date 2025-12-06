@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title ?? 'Riwayat Penyesuaian SRM' }}
@endsection

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4>{{ __('Riwayat Penyesuaian SRM') }}</h4>
                    <p class="mb-0 text-muted small">
                        {{ __('Detail semua trade yang telah disesuaikan oleh AI Smart Risk Management (lot, buffer SL, dan logic perlindungan lainnya).') }}
                    </p>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('user.srm.adjustments.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label>{{ __('Koneksi') }}</label>
                                <select name="connection_id" class="form-control">
                                    <option value="">All Connections</option>
                                    @foreach($connections as $conn)
                                        <option value="{{ $conn->id }}" {{ request('connection_id') == $conn->id ? 'selected' : '' }}>
                                            {{ $conn->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>{{ __('Tanggal Dari') }}</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-4">
                                <label>{{ __('Tanggal Sampai') }}</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                                <a href="{{ route('user.srm.adjustments.index') }}" class="btn btn-secondary">{{ __('Reset') }}</a>
                            </div>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                            <th>{{ __('Waktu') }}</th>
                                            <th>{{ __('Koneksi') }}</th>
                                            <th>{{ __('Signal ID') }}</th>
                                            <th>{{ __('Jenis Penyesuaian') }}</th>
                                            <th>{{ __('Alasan') }}</th>
                                            <th>{{ __('Dampak') }}</th>
                                            <th>{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($adjustments as $adjustment)
                                    <tr>
                                        <td>{{ $adjustment->created_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $adjustment->connection->name ?? 'N/A' }}</td>
                                        <td>#{{ $adjustment->signal_id ?? 'N/A' }}</td>
                                        <td>
                                            @if($adjustment->srm_adjusted_lot)
                                                <span class="badge badge-info">{{ __('Lot:') }} {{ number_format($adjustment->srm_adjusted_lot, 4) }}</span>
                                            @endif
                                            @if($adjustment->srm_sl_buffer)
                                                <span class="badge badge-warning">{{ __('SL Buffer:') }} {{ number_format($adjustment->srm_sl_buffer, 4) }} pips</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $reason = json_decode($adjustment->srm_adjustment_reason ?? '{}', true);
                                            @endphp
                                            {{ $reason['reasons'][0]['message'] ?? 'N/A' }}
                                        </td>
                                        <td>
                                            @if(isset($reason['adjustment_percent']))
                                                <span class="badge badge-{{ $reason['adjustment_percent'] > 0 ? 'success' : 'danger' }}">
                                                    {{ $reason['adjustment_percent'] > 0 ? '+' : '' }}{{ number_format($reason['adjustment_percent'], 2) }}%
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('user.srm.adjustments.show', $adjustment->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> {{ __('Detail') }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">{{ __('Belum ada penyesuaian SRM yang tercatat.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $adjustments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

