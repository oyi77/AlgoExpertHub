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
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .pagebuilder-actions {
        display: flex;
        gap: 10px;
    }
    #section-editor {
        min-height: 600px;
    }
    .editor-loading {
        text-align: center;
        padding: 50px;
        color: #666;
    }
    .editor-error {
        padding: 20px;
        background: #fee;
        border: 1px solid #fcc;
        color: #c00;
        border-radius: 4px;
        margin: 20px;
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
                        <small class="text-muted">{{ __('Drag and drop to build your section') }}</small>
                    </div>
                    <div class="pagebuilder-actions">
                        <button type="button" class="btn btn-sm btn-secondary" id="previewSection">
                            <i data-feather="eye"></i> {{ __('Preview') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="saveSection">
                            <i data-feather="save"></i> {{ __('Save Section') }}
                        </button>
                        <a href="{{ route('admin.page-builder.sections.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="section-editor">
                        <div class="editor-loading">
                            <i data-feather="loader" class="spin"></i>
                            <p>{{ __('Loading editor...') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('external-script')
<script src="https://unpkg.com/grapesjs@0.21.7"></script>
<script src="https://unpkg.com/grapesjs-preset-webpage@1.0.2"></script>
<script>
'use strict';
let editor;

// Check if GrapesJS is loaded
function checkGrapesJS() {
    if (typeof grapesjs === 'undefined') {
        document.getElementById('section-editor').innerHTML = 
            '<div class="editor-error">' +
            '<h5>{{ __("Error Loading Editor") }}</h5>' +
            '<p>{{ __("GrapesJS library failed to load. Please check your internet connection and try again.") }}</p>' +
            '<button onclick="location.reload()" class="btn btn-sm btn-primary">{{ __("Reload Page") }}</button>' +
            '</div>';
        return false;
    }
    return true;
}

// Wait for both DOM and scripts to be ready
function initEditor() {
    // Check if GrapesJS loaded
    if (typeof grapesjs === 'undefined') {
        // Retry after a short delay
        setTimeout(function() {
            if (typeof grapesjs === 'undefined') {
                if (!checkGrapesJS()) {
                    return;
                }
            } else {
                initEditor();
            }
        }, 500);
        return;
    }

    if (!checkGrapesJS()) {
        return;
    }

    try {
        // Initialize GrapesJS editor
        editor = grapesjs.init({
            container: '#section-editor',
            height: '600px',
            width: 'auto',
            plugins: ['gjs-preset-webpage'],
            pluginsOpts: {
                'gjs-preset-webpage': {
                    modalImportTitle: 'Import Template',
                    modalImportLabel: '<div style="margin-bottom: 10px; font-size: 13px;">Paste here your HTML/CSS and click Import</div>',
                    filestackOpts: null,
                    blocks: ['link-block', 'quote', 'text-basic'],
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
                        name: 'Mobile Landscape',
                        width: '568px',
                        widthMedia: '768px',
                    },
                    {
                        name: 'Mobile Portrait',
                        width: '320px',
                        widthMedia: '568px',
                    }
                ]
            },
            blockManager: {
                blocks: [
                    {
                        id: 'section',
                        label: '<b>Section</b>',
                        attributes: { class: 'gjs-block-section' },
                        content: '<section class="bdg-sect"><div class="sect100"><h1>Insert title here</h1><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit</p></div></section>',
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

        // Load existing section content if available
        @if(isset($elements) && $elements->count() > 0)
            // Load first element content if exists
            const firstElement = @json($elements->first());
            if (firstElement && firstElement.content) {
                try {
                    const content = typeof firstElement.content === 'string' 
                        ? JSON.parse(firstElement.content) 
                        : firstElement.content;
                    editor.setComponents(content);
                    if (firstElement.css) {
                        editor.setStyle(firstElement.css);
                    }
                } catch (e) {
                    console.warn('Could not load existing content:', e);
                }
            }
        @endif

        // Save button handler
        const saveBtn = document.getElementById('saveSection');
        if (saveBtn) {
            saveBtn.addEventListener('click', function() {
                const html = editor.getHtml();
                const css = editor.getCss();
                const content = editor.getComponents().toJSON();

                // Disable button during save
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i data-feather="loader" class="spin"></i> {{ __("Saving...") }}';

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
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Success") }}',
                            text: data.message || '{{ __("Section saved successfully") }}',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert('{{ __("Section saved successfully") }}');
                    }
                })
                .catch(error => {
                    console.error('Error saving section:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("Error") }}',
                            text: '{{ __("Failed to save section. Please try again.") }}'
                        });
                    } else {
                        alert('{{ __("Failed to save section") }}');
                    }
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i data-feather="save"></i> {{ __("Save Section") }}';
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                });
            });
        }

        // Preview button handler
        const previewBtn = document.getElementById('previewSection');
        if (previewBtn) {
            previewBtn.addEventListener('click', function() {
                editor.Commands.run('sw-visibility');
            });
        }

        // Initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

    } catch (error) {
        console.error('Error initializing editor:', error);
        document.getElementById('section-editor').innerHTML = 
            '<div class="editor-error">' +
            '<h5>{{ __("Editor Initialization Error") }}</h5>' +
            '<p>' + error.message + '</p>' +
            '<button onclick="location.reload()" class="btn btn-sm btn-primary">{{ __("Reload Page") }}</button>' +
            '</div>';
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initEditor);
} else {
    // DOM is already ready
    initEditor();
}
</script>
<style>
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
@endpush
@endsection
