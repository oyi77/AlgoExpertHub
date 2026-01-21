@extends('backend.layout.master')

@section('content')
<div class="card">
    <div class="card-header">
        <h4>Behavior Clustering</h4>
    </div>
    <div class="card-body">
        <pre>{{ json_encode($clusters, JSON_PRETTY_PRINT) }}</pre>
    </div>
</div>
@endsection
