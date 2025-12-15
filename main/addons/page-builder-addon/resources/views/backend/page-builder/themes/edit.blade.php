@extends('backend.layout.master')

@push('external-style')
<link rel="stylesheet" href="https://unpkg.com/grapesjs/dist/css/grapes.min.css">
<style>
    .gjs-editor {
        border: 1px solid #ddd;
        min-height: 600px;
    }
    .pagebuilder-toolbar {
        background: #fff;
        padding: 15px;
        border-bottom: 1px solid #ddd;
    }
</style>
@endpush

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="pagebuilder-toolbar">
                    <div>
                        <h4 class="mb-0">{{ $title }}</h4>
                        <small class="text-muted">{{ __('Theme:') }} {{ $theme }} | {{ __('Template:') }} {{ $templatePath }}</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary" id="saveThemeTemplate">
                            <i data-feather="save"></i> {{ __('Save Template') }}
                        </button>
                        <a href="{{ route('admin.page-builder.themes.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="theme-editor"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('external-script')
<script src="https://unpkg.com/grapesjs"></script>
<script src="https://unpkg.com/grapesjs-preset-webpage"></script>
<script>
'use strict';
let editor;

document.addEventListener('DOMContentLoaded', function() {
    editor = grapesjs.init({
        container: '#theme-editor',
        height: '600px',
        width: 'auto',
        plugins: ['gjs-preset-webpage'],
        pluginsOpts: {
            'gjs-preset-webpage': {}
        }
    });

    // Store Blade placeholders for restoration on save
    const bladePlaceholders = @json($bladePlaceholders ?? []);
    
    // Load existing template content (use pre-converted content from server)
    @if(isset($editorContent) && !empty($editorContent))
        try {
            const htmlContent = @json($editorContent);
            if (htmlContent && htmlContent.trim()) {
                // Remove any placeholder comments that might have been left
                const cleanHtml = htmlContent.replace(/<!--BLADE_PLACEHOLDERS:.*?-->/s, '').trim();
                if (cleanHtml) {
                    editor.setComponents(cleanHtml);
                }
            }
        } catch (e) {
            console.error('Error loading template content:', e);
        }
    @endif

    document.getElementById('saveThemeTemplate').addEventListener('click', function() {
        let html = editor.getHtml();
        const css = editor.getCss();
        const content = editor.getComponents().toJSON();

        // Restore Blade placeholders in HTML before saving
        if (bladePlaceholders && Object.keys(bladePlaceholders).length > 0) {
            Object.keys(bladePlaceholders).forEach(function(placeholder) {
                html = html.replace(new RegExp(placeholder.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), bladePlaceholders[placeholder]);
            });
        }

        fetch('{{ route("admin.page-builder.themes.update", $theme) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                template_path: '{{ $templatePath }}',
                content: content,
                html: html,
                css: css,
                placeholders: bladePlaceholders
            })
        })
        .then(response => response.json())
        .then(data => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __("Success") }}',
                    text: '{{ __("Template saved successfully") }}',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        })
        .catch(error => {
            console.error('Error saving template:', error);
        });
    });
});
</script>
@endpush
