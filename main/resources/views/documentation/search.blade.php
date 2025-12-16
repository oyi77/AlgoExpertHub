@extends('backend.layout.master')

@section('element')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Search Results</h4>
                    <p class="mb-0">{{ count($results) }} results for "{{ $query }}"</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('wiki.index') }}">Documentation</a></li>
                    <li class="breadcrumb-item active">Search</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Search</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('wiki.search') }}" method="GET">
                            <div class="form-group">
                                <input type="text" name="q" class="form-control" placeholder="Search documentation..." value="{{ $query }}" autofocus>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-search"></i> Search
                            </button>
                        </form>

                        <div class="mt-4">
                            <h6>Quick Links</h6>
                            <ul class="list-unstyled">
                                <li><a href="{{ route('wiki.index') }}"><i class="fa fa-home"></i> Documentation Home</a></li>
                                <li><a href="{{ route('wiki.show', 'Project Overview.md') }}"><i class="fa fa-file-alt"></i> Project Overview</a></li>
                                <li><a href="{{ route('wiki.docs', 'laravel-10-upgrade-summary') }}"><i class="fa fa-rocket"></i> Laravel 10 Upgrade</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Search Results ({{ count($results) }})</h5>
                    </div>
                    <div class="card-body">
                        @if(count($results) > 0)
                            @foreach($results as $result)
                                <div class="search-result mb-4 pb-3 border-bottom">
                                    <h5>
                                        @if($result['type'] === 'wiki')
                                            <a href="{{ route('wiki.show', $result['path']) }}">
                                                {{ $result['title'] }}
                                            </a>
                                        @else
                                            <a href="{{ route('wiki.docs', $result['path']) }}">
                                                {{ $result['title'] }}
                                            </a>
                                        @endif
                                        <span class="badge badge-{{ $result['type'] === 'wiki' ? 'primary' : 'success' }} ml-2">
                                            {{ $result['type'] === 'wiki' ? 'Wiki' : 'Guide' }}
                                        </span>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        <small>
                                            <i class="fa fa-folder"></i> {{ $result['path'] }}
                                        </small>
                                    </p>
                                    <p class="mb-0">{!! $result['excerpt'] !!}</p>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <i class="fa fa-search fa-3x text-muted mb-3"></i>
                                <h5>No results found</h5>
                                <p class="text-muted">Try different keywords or browse the documentation.</p>
                                <a href="{{ route('wiki.index') }}" class="btn btn-primary">
                                    <i class="fa fa-home"></i> Back to Documentation
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<style>
    .search-result h5 a {
        color: #007bff;
        text-decoration: none;
    }
    
    .search-result h5 a:hover {
        text-decoration: underline;
    }
    
    .search-result mark {
        background-color: #fff3cd;
        padding: 2px 4px;
        border-radius: 2px;
    }
    
    .search-result:last-child {
        border-bottom: none !important;
    }
</style>
@endsection

@section('script')
<script>
    // Auto-focus search input
    document.querySelector('input[name="q"]').focus();
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Escape to go back
        if (e.key === 'Escape') {
            window.location.href = '{{ route('wiki.index') }}';
        }
    });
</script>
@endsection

