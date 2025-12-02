@extends('backend.layout.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Decision Log Details</h4>
                    <a href="{{ route('admin.ai-decision-logs.index') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Signal Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Signal ID</th>
                                    <td>
                                        <a href="{{ route('admin.signal.show', $channelMessage->signal_id) }}" target="_blank">
                                            #{{ $channelMessage->signal_id }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Channel Source</th>
                                    <td>{{ $channelMessage->channelSource->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Pair</th>
                                    <td>{{ $channelMessage->signal->pair->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Direction</th>
                                    <td>{{ strtoupper($channelMessage->signal->direction ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <th>Entry Price</th>
                                    <td>{{ $channelMessage->signal->open_price ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Stop Loss</th>
                                    <td>{{ $channelMessage->signal->sl ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Take Profit</th>
                                    <td>{{ $channelMessage->signal->tp ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Timeframe</th>
                                    <td>{{ $channelMessage->signal->time->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Processed At</th>
                                    <td>{{ $channelMessage->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5>Filter Strategy Evaluation</h5>
                            @if($filterEvaluation)
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Result</th>
                                        <td>
                                            @if($filterEvaluation['pass'] ?? false)
                                                <span class="badge badge-success">PASS</span>
                                            @else
                                                <span class="badge badge-danger">FAIL</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Strategy ID</th>
                                        <td>{{ $filterEvaluation['strategy_id'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Strategy Name</th>
                                        <td>{{ $filterEvaluation['strategy_name'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Reason</th>
                                        <td>{{ $filterEvaluation['reason'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Evaluated At</th>
                                        <td>{{ $filterEvaluation['evaluated_at'] ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                                @if(isset($filterEvaluation['indicators']))
                                    <h6>Indicators</h6>
                                    <pre class="bg-light p-2" style="max-height: 200px; overflow-y: auto;">{{ json_encode($filterEvaluation['indicators'], JSON_PRETTY_PRINT) }}</pre>
                                @endif
                            @else
                                <p class="text-muted">No filter evaluation performed</p>
                            @endif

                            <h5 class="mt-4">AI Confirmation Evaluation</h5>
                            @if($aiEvaluation)
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Decision</th>
                                        <td>
                                            @if($aiEvaluation['execute'] ?? false)
                                                <span class="badge badge-success">EXECUTE</span>
                                            @else
                                                <span class="badge badge-danger">REJECT</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Profile ID</th>
                                        <td>{{ $aiEvaluation['profile_id'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Adjusted Risk Factor</th>
                                        <td>
                                            @if(isset($aiEvaluation['adjusted_risk_factor']))
                                                {{ number_format($aiEvaluation['adjusted_risk_factor'] * 100, 1) }}%
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Reason</th>
                                        <td>{{ $aiEvaluation['reason'] ?? 'N/A' }}</td>
                                    </tr>
                                </table>
                                @if(isset($aiEvaluation['ai_result']))
                                    <h6>AI Analysis Result</h6>
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <th>Alignment</th>
                                            <td>{{ $aiEvaluation['ai_result']['alignment'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Safety Score</th>
                                            <td>{{ $aiEvaluation['ai_result']['safety_score'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Decision</th>
                                            <td>{{ $aiEvaluation['ai_result']['decision'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Confidence</th>
                                            <td>{{ $aiEvaluation['ai_result']['confidence'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Reasoning</th>
                                            <td>{{ $aiEvaluation['ai_result']['reasoning'] ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                @endif
                            @else
                                <p class="text-muted">No AI evaluation performed</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

