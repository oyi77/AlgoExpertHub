@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-robot"></i> {{ $title }}</h4>
                    <a href="{{ route('admin.trading-management.trading-bots.show', $bot->id) }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.trading-management.trading-bots.update', $bot->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('trading-management::backend.trading-bots.partials.form', ['bot' => $bot])
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
