@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title ?? 'Adjustment Details' }}
@endsection

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4>Adjustment Details</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th>Date</th>
                                <td>{{ $position->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            <tr>
                                <th>Connection</th>
                                <td>{{ $position->connection->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Signal</th>
                                <td>#{{ $position->signal_id ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Symbol</th>
                                <td>{{ $position->symbol }}</td>
                            </tr>
                            <tr>
                                <th>Performance Score at Entry</th>
                                <td>
                                    @if($position->performance_score_at_entry)
                                        <span class="badge badge-{{ $position->performance_score_at_entry >= 70 ? 'success' : ($position->performance_score_at_entry >= 50 ? 'warning' : 'danger') }}">
                                            {{ number_format($position->performance_score_at_entry, 2) }}
                                        </span>
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Predicted Slippage</th>
                                <td>{{ $position->predicted_slippage ? number_format($position->predicted_slippage, 4) . ' pips' : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>Original Lot</th>
                                <td>{{ number_format($position->quantity, 4) }}</td>
                            </tr>
                            <tr>
                                <th>SRM Adjusted Lot</th>
                                <td>
                                    @if($position->srm_adjusted_lot)
                                        <strong>{{ number_format($position->srm_adjusted_lot, 4) }}</strong>
                                        @if($position->srm_adjusted_lot != $position->quantity)
                                            <span class="badge badge-{{ $position->srm_adjusted_lot > $position->quantity ? 'success' : 'warning' }}">
                                                {{ $position->srm_adjusted_lot > $position->quantity ? '+' : '' }}{{ number_format((($position->srm_adjusted_lot - $position->quantity) / $position->quantity) * 100, 2) }}%
                                            </span>
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Original SL</th>
                                <td>{{ $position->sl_price ? number_format($position->sl_price, 8) : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>SL Buffer Added</th>
                                <td>
                                    @if($position->srm_sl_buffer)
                                        <strong>{{ number_format($position->srm_sl_buffer, 4) }} pips</strong>
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>

                    @if($adjustment_reason)
                        <div class="mt-4">
                            <h5>Adjustment Reason</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    @if(isset($adjustment_reason['reasons']))
                                        @foreach($adjustment_reason['reasons'] as $reason)
                                            <p><strong>{{ ucfirst(str_replace('_', ' ', $reason['type'])) }}:</strong> {{ $reason['message'] }}</p>
                                            <p><small>{{ $reason['impact'] }}</small></p>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

