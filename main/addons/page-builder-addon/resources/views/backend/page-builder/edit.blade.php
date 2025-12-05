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
    .pagebuilder-actions {
        display: flex;
        gap: 10px;
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
                        <h4 class="mb-0">{{ $title }}: {{ $page->name }}</h4>
                        <small class="text-muted">{{ __('Drag and drop to build your page') }}</small>
                    </div>
                    <div class="pagebuilder-actions">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="editor.Commands.run('sw-visibility')">
                            <i data-feather="eye"></i> {{ __('Preview') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="savePageBuilder">
                            <i data-feather="save"></i> {{ __('Save') }}
                        </button>
                        <a href="{{ route('admin.page-builder.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="pagebuilder-editor"></div>
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
    // Initialize GrapesJS editor
    editor = grapesjs.init({
        container: '#pagebuilder-editor',
        height: '600px',
        width: 'auto',
        storageManager: {
            type: 'remote',
            autosave: true,
            autoload: true,
            stepsBeforeSave: 1,
            urlStore: '{{ route("admin.page-builder.api.pages.content.save", $page->id) }}',
            urlLoad: '{{ route("admin.page-builder.api.pages.content.get", $page->id) }}',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            params: {
                _token: '{{ csrf_token() }}'
            }
        },
        plugins: ['gjs-preset-webpage'],
        pluginsOpts: {
            'gjs-preset-webpage': {
                modalImportTitle: 'Import Template',
                modalImportLabel: '<div style="margin-bottom: 10px; font-size: 13px;">Paste here your HTML/CSS and click Import</div>',
                filestackOpts: null,
                blocks: ['link-block', 'quote', 'text-basic'],
                block: {},
            }
        },
        deviceManager: {
            devices: [
                {
                    name: 'Desktop',
                    width: '',
                },
                {
                    name: 'Tablet',
                    width: '768px',
                    widthMedia: '992px',
                },
                {
                    name: 'Mobile',
                    width: '320px',
                    widthMedia: '768px',
                }
            ]
        },
        panels: {
            defaults: [
                {
                    id: 'layers',
                    el: '.panel__right',
                    resizable: {
                        maxDim: 350,
                        minDim: 200,
                        tc: 0,
                        cl: 1,
                        cr: 0,
                        bc: 0,
                        keyWidth: 'flex-basis',
                    },
                },
                {
                    id: 'panel-switcher',
                    el: '.panel__switcher',
                    buttons: [
                        {
                            id: 'show-layers',
                            active: true,
                            label: 'Layers',
                            command: 'show-layers',
                            togglable: false,
                        },
                        {
                            id: 'show-style',
                            active: true,
                            label: 'Styles',
                            command: 'show-styles',
                            togglable: false,
                        },
                        {
                            id: 'show-traits',
                            active: true,
                            label: 'Traits',
                            command: 'show-traits',
                            togglable: false,
                        }
                    ],
                },
                {
                    id: 'panel-devices',
                    el: '.panel__devices',
                    buttons: [
                        {
                            id: 'device-desktop',
                            label: 'DT',
                            command: 'set-device-desktop',
                            active: true,
                            togglable: false,
                        },
                        {
                            id: 'device-tablet',
                            label: 'TB',
                            command: 'set-device-tablet',
                            togglable: false,
                        },
                        {
                            id: 'device-mobile',
                            label: 'MB',
                            command: 'set-device-mobile',
                            togglable: false,
                        }
                    ],
                }
            ]
        },
        blockManager: {
            appendTo: '.blocks-container',
            blocks: [
                {
                    id: 'section',
                    label: '<b>Section</b>',
                    attributes: { class: 'gjs-block-section' },
                    content: '<section class="bdg-sect"><div class="sect100"><h1>Insert title here</h1><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua</p></div></section>',
                },
                {
                    id: 'text',
                    label: 'Text',
                    content: '<div data-gjs-type="text">Insert your text here</div>',
                },
                {
                    id: 'image',
                    label: 'Image',
                    select: true,
                    content: { type: 'image', src: 'https://via.placeholder.com/350x250/78c5d6/fff', alt: 'Image' },
                    activate: true,
                },
                {
                    id: 'link',
                    label: 'Link',
                    content: '<a href="#" data-gjs-type="link">Link text</a>',
                },
                {
                    id: 'button',
                    label: 'Button',
                    content: '<button class="btn btn-primary" data-gjs-type="button">Button</button>',
                },
            ]
        }
    });

    // Load existing content
    loadExistingContent();

    // Save button handler
    document.getElementById('savePageBuilder').addEventListener('click', function() {
        saveContent();
    });

    // Auto-save on change
    editor.on('update', function() {
        // Auto-save is handled by storageManager
    });
});

function loadExistingContent() {
    fetch('{{ route("admin.page-builder.api.pages.content.get", $page->id) }}', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            // Load content into editor
            if (data.data.html) {
                editor.setComponents(data.data.html);
            }
            if (data.data.css) {
                editor.setStyle(data.data.css);
            }
        } else {
            // Set default content if no existing content
            editor.setComponents('<div class="container"><h1>Welcome to {{ $page->name }}</h1><p>Start building your page by dragging components from the left panel.</p></div>');
        }
    })
    .catch(error => {
        console.error('Error loading content:', error);
        // Set default content on error
        editor.setComponents('<div class="container"><h1>Welcome to {{ $page->name }}</h1><p>Start building your page by dragging components from the left panel.</p></div>');
    });
}

function saveContent() {
    const html = editor.getHtml();
    const css = editor.getCss();
    const content = editor.getComponents().toJSON();

    fetch('{{ route("admin.page-builder.api.pages.content.save", $page->id) }}', {
        method: 'POST',
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
        if (data.success) {
            // Show success message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '{{ __("Success") }}',
                    text: data.message || '{{ __("Content saved successfully") }}',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert(data.message || '{{ __("Content saved successfully") }}');
            }
        } else {
            throw new Error(data.message || '{{ __("Failed to save content") }}');
        }
    })
    .catch(error => {
        console.error('Error saving content:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: '{{ __("Error") }}',
                text: error.message || '{{ __("Failed to save content") }}'
            });
        } else {
            alert(error.message || '{{ __("Failed to save content") }}');
        }
    });
}
</script>
@endpush
