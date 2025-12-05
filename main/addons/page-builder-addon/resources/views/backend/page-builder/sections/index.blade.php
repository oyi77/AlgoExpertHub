@extends('backend.layout.master')

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ $title }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($sections as $section)
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h5>{{ ucwords(str_replace('_', ' ', $section)) }}</h5>
                                        <a href="{{ route('admin.page-builder.sections.edit', $section) }}" class="btn btn-sm btn-primary">
                                            <i data-feather="edit"></i> {{ __('Edit in Builder') }}
                                        </a>
                                        <a href="{{ route('admin.frontend.section.manage', $section) }}" class="btn btn-sm btn-outline-secondary">
                                            <i data-feather="settings"></i> {{ __('Legacy Editor') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

