@extends('backend.layout.master')

@section('element')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>Technical Documentation</h4>
                    <p class="mb-0">Comprehensive platform documentation</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Documentation</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">AlgoExpertHub Technical Documentation</h4>
                        <form action="{{ route('wiki.search') }}" method="GET" class="d-inline-block ml-auto">
                            <div class="input-group">
                                <input type="text" name="q" class="form-control" placeholder="Search documentation..." value="{{ request('q') }}">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fa fa-book text-primary"></i> Platform Overview</h5>
                                <p>AlgoExpertHub is an AI-powered trading signal platform built on <strong>Laravel 10</strong> with advanced performance optimizations.</p>
                                
                                <h6 class="mt-4">Technology Stack</h6>
                                <ul>
                                    <li><strong>Framework:</strong> Laravel 10.x with Octane 2.0</li>
                                    <li><strong>Queue:</strong> Laravel Horizon 5.0 with Redis</li>
                                    <li><strong>API:</strong> Laravel Sanctum 3.2</li>
                                    <li><strong>Performance:</strong> 5x faster with &lt; 200ms response time</li>
                                    <li><strong>AI:</strong> OpenAI, Google Gemini, OpenRouter (400+ models)</li>
                                </ul>

                                <h6 class="mt-4">Quick Links</h6>
                                <div class="list-group">
                                    <a href="{{ route('wiki.show', 'Project Overview.md') }}" class="list-group-item list-group-item-action">
                                        <i class="fa fa-file-alt"></i> Project Overview
                                    </a>
                                    <a href="{{ route('wiki.docs', 'laravel-10-upgrade-summary') }}" class="list-group-item list-group-item-action">
                                        <i class="fa fa-rocket"></i> Laravel 10 Upgrade Summary
                                    </a>
                                    <a href="{{ route('wiki.docs', 'deployment-guide') }}" class="list-group-item list-group-item-action">
                                        <i class="fa fa-server"></i> Deployment Guide
                                    </a>
                                    <a href="{{ route('wiki.docs', 'api-reference') }}" class="list-group-item list-group-item-action">
                                        <i class="fa fa-code"></i> API Reference
                                    </a>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5><i class="fa fa-sitemap text-info"></i> Documentation Sections</h5>
                                
                                @foreach($wikiStructure as $section => $files)
                                    @if(count($files) > 0)
                                        <div class="mb-3">
                                            <h6 class="text-primary">{{ $section }}</h6>
                                            <ul class="list-unstyled ml-3">
                                                @foreach(array_slice($files, 0, 5) as $file)
                                                    <li>
                                                        <a href="{{ route('wiki.show', $file['path']) }}">
                                                            <i class="fa fa-angle-right"></i> {{ $file['title'] }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                                @if(count($files) > 5)
                                                    <li class="text-muted"><small>+ {{ count($files) - 5 }} more...</small></li>
                                                @endif
                                            </ul>
                                        </div>
                                    @endif
                                @endforeach

                                <div class="mt-4">
                                    <h6 class="text-success">Guides & Tutorials</h6>
                                    <ul class="list-unstyled ml-3">
                                        @foreach(array_slice($docsFiles, 0, 8) as $file)
                                            <li>
                                                <a href="{{ route('wiki.docs', $file['path']) }}">
                                                    <i class="fa fa-angle-right"></i> {{ $file['title'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h6><i class="fa fa-info-circle"></i> About This Documentation</h6>
                                    <p class="mb-0">
                                        This documentation is automatically generated from the codebase using Qoder and includes 
                                        <strong>76 wiki pages</strong> and <strong>{{ count($docsFiles) }} guides</strong>. 
                                        Use the search function above to quickly find specific topics.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // Auto-focus search on Ctrl+K or Cmd+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.querySelector('input[name="q"]').focus();
        }
    });
</script>
@endsection

