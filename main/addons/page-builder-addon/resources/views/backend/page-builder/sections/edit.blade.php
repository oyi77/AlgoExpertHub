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
    }
</style>
@endpush

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="pagebuilder-toolbar">
                    <h4 class="mb-0">{{ $title }}</h4>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-primary" id="saveSection">
                            <i data-feather="save"></i> {{ __('Save Section') }}
                        </button>
                        <a href="{{ route('admin.page-builder.sections.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="section-editor"></div>
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
        container: '#section-editor',
        height: '600px',
        width: 'auto',
        plugins: ['gjs-preset-webpage'],
        pluginsOpts: {
            'gjs-preset-webpage': {}
        }
    });

    // Load existing section content
    // TODO: Load from Content model

    document.getElementById('saveSection').addEventListener('click', function() {
        const html = editor.getHtml();
        const css = editor.getCss();
        const content = editor.getComponents().toJSON();

        fetch('{{ route("admin.page-builder.sections.update", $sectionName) }}', {
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
                    text: '{{ __("Section saved successfully") }}',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        })
        .catch(error => {
            console.error('Error saving section:', error);
        });
    });
});
</script>
@endpush
@endsection
