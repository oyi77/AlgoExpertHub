@extends('backend.layout.master')

@section('element')
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
        display: flex;
        justify-content: space-between;
        align-items: center;
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
                        <small class="text-muted">{{ __('Edit template content') }}</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-primary" id="saveTemplate">
                            <i data-feather="save"></i> {{ __('Save') }}
                        </button>
                        <a href="{{ route('admin.page-builder.templates.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="template-editor"></div>
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
        container: '#template-editor',
        height: '600px',
        width: 'auto',
        plugins: ['gjs-preset-webpage'],
        pluginsOpts: {
            'gjs-preset-webpage': {}
        }
    });

    // Load template content if exists
    // TODO: Load from template model

    document.getElementById('saveTemplate').addEventListener('click', function() {
        const html = editor.getHtml();
        const css = editor.getCss();
        const content = editor.getComponents().toJSON();

        fetch('{{ route("admin.page-builder.templates.update", $templateId) }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                content: content,
                html: html,
                css: css
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
@endsection
