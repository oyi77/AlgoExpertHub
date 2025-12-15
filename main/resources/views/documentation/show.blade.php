@extends('backend.layout.master')

@section('element')
<div class="content-body">
    <div class="container-fluid">
        <div class="row page-titles mx-0">
            <div class="col-sm-6 p-md-0">
                <div class="welcome-text">
                    <h4>{{ $title }}</h4>
                    <p class="mb-0">{{ $type === 'wiki' ? 'Wiki Documentation' : 'Guide' }}</p>
                </div>
            </div>
            <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('wiki.index') }}">Documentation</a></li>
                    <li class="breadcrumb-item active">{{ $title }}</li>
                </ol>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Navigation</h5>
                    </div>
                    <div class="card-body" style="max-height: 80vh; overflow-y: auto;">
                        <!-- Search -->
                        <form action="{{ route('wiki.search') }}" method="GET" class="mb-3">
                            <div class="input-group input-group-sm">
                                <input type="text" name="q" class="form-control" placeholder="Search...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </form>

                        <!-- Wiki Structure -->
                        @foreach($wikiStructure as $section => $files)
                            @if(count($files) > 0)
                                <h6 class="text-primary mt-3">{{ $section }}</h6>
                                <ul class="list-unstyled ml-2">
                                    @foreach($files as $file)
                                        <li class="mb-1">
                                            <a href="{{ route('wiki.show', $file['path']) }}" 
                                               class="text-dark {{ $type === 'wiki' && $currentPath === $file['path'] ? 'font-weight-bold text-primary' : '' }}">
                                                <i class="fa fa-angle-right"></i> {{ $file['title'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endforeach

                        <!-- Docs Files -->
                        <h6 class="text-success mt-3">Guides</h6>
                        <ul class="list-unstyled ml-2">
                            @foreach($docsFiles as $file)
                                <li class="mb-1">
                                    <a href="{{ route('wiki.docs', $file['path']) }}" 
                                       class="text-dark {{ $type === 'docs' && $currentPath === $file['path'] ? 'font-weight-bold text-success' : '' }}">
                                        <i class="fa fa-angle-right"></i> {{ $file['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-body documentation-content">
                        {!! $content !!}
                    </div>
                </div>

                <!-- Back to top button -->
                <button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" 
                        class="btn btn-primary btn-sm" 
                        style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
                    <i class="fa fa-arrow-up"></i> Top
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<style>
    .documentation-content {
        font-size: 15px;
        line-height: 1.7;
    }
    
    .documentation-content h1 {
        font-size: 2rem;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.5rem;
    }
    
    .documentation-content h2 {
        font-size: 1.75rem;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        color: #495057;
    }
    
    .documentation-content h3 {
        font-size: 1.5rem;
        margin-top: 1.25rem;
        margin-bottom: 0.75rem;
        color: #6c757d;
    }
    
    .documentation-content h4 {
        font-size: 1.25rem;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
    }
    
    .documentation-content pre {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 1rem;
        overflow-x: auto;
        margin: 1rem 0;
    }
    
    .documentation-content code {
        background: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        font-size: 0.9em;
        color: #e83e8c;
    }
    
    .documentation-content pre code {
        background: transparent;
        padding: 0;
        color: inherit;
    }
    
    .documentation-content table {
        width: 100%;
        margin: 1rem 0;
        border-collapse: collapse;
    }
    
    .documentation-content table th,
    .documentation-content table td {
        border: 1px solid #dee2e6;
        padding: 0.75rem;
        text-align: left;
    }
    
    .documentation-content table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    
    .documentation-content blockquote {
        border-left: 4px solid #007bff;
        padding-left: 1rem;
        margin: 1rem 0;
        color: #6c757d;
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 4px;
    }
    
    .documentation-content ul,
    .documentation-content ol {
        margin: 0.5rem 0 0.5rem 1.5rem;
    }
    
    .documentation-content li {
        margin: 0.25rem 0;
    }
    
    .documentation-content a {
        color: #007bff;
        text-decoration: none;
    }
    
    .documentation-content a:hover {
        text-decoration: underline;
    }
    
    .documentation-content img {
        max-width: 100%;
        height: auto;
        margin: 1rem 0;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Mermaid diagrams */
    .documentation-content .mermaid {
        text-align: center;
        margin: 1.5rem 0;
    }
    
    /* Sidebar scrollbar */
    .card-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .card-body::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .card-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }
    
    .card-body::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>
@endsection

@section('script')
<!-- Mermaid for diagrams -->
<script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
<script>
    mermaid.initialize({ startOnLoad: true, theme: 'default' });
</script>

<!-- Prism for syntax highlighting -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-bash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-sql.min.js"></script>

<script>
    // Re-run Prism highlighting after content loads
    Prism.highlightAll();
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+K or Cmd+K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.querySelector('input[name="q"]').focus();
        }
    });
</script>
@endsection

