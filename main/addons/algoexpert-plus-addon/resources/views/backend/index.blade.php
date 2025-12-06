@extends(\App\Helpers\Helper\Helper::backendTheme() . 'layout.app')

@section('title', $page ?? 'AlgoExpert++')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">AlgoExpert++</h4>
                    <p class="mb-0">Integration layer for SEO, Queues dashboard, UI components, and i18n.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

