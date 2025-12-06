@extends('backend.layout.master')

@section('title', $title ?? 'Performance Settings')

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="m-0">Performance Settings</h4>
                </div>
            </div>
        </div>
    </div>

    @include('backend.setting.performance')
</div>
@endsection
