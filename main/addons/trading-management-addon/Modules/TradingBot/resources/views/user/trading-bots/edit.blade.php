@extends(Config::theme() . 'layout.auth')

@section('title')
    {{ $title }}
@endsection

@section('content')
<div class="sp_site_card">
    <div class="card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <h4>{{ __($title) }}</h4>
            <a href="{{ route('user.trading-bots.show', $bot->id) }}" class="btn btn-sm btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ __('Go Back') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('user.trading-bots.update', $bot->id) }}" method="POST" id="bot-form">
            @csrf
            @method('PUT')

            {{-- Same form as create, but with existing values --}}
            @include('trading-management::user.trading-bots.partials.form', ['bot' => $bot])
        </form>
    </div>
</div>
@endsection
