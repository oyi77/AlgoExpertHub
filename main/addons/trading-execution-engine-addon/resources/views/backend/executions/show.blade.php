@extends('backend.layout.master')

@section('title')
    {{ $title }}
@endsection

@section('element')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">{{ $title }}</h4>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Signal ID</dt>
                            <dd class="col-sm-9">{{ $log->signal_id }}</dd>
                            <dt class="col-sm-3">Connection</dt>
                            <dd class="col-sm-9">{{ $log->connection->name }}</dd>
                            <dt class="col-sm-3">Symbol</dt>
                            <dd class="col-sm-9">{{ $log->symbol }}</dd>
                            <dt class="col-sm-3">Direction</dt>
                            <dd class="col-sm-9">{{ strtoupper($log->direction) }}</dd>
                            <dt class="col-sm-3">Quantity</dt>
                            <dd class="col-sm-9">{{ $log->quantity }}</dd>
                            <dt class="col-sm-3">Entry Price</dt>
                            <dd class="col-sm-9">{{ $log->entry_price }}</dd>
                            <dt class="col-sm-3">Status</dt>
                            <dd class="col-sm-9">
                                <span class="badge badge-{{ $log->status === 'executed' ? 'success' : 'warning' }}">
                                    {{ $log->status }}
                                </span>
                            </dd>
                        </dl>
                        <a href="{{ route('admin.execution-executions.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

