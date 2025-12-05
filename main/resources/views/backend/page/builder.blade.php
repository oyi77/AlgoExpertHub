@extends('backend.layout.master')

@section('element')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header site-card-header justify-content-between">
                <div class="card-header-left">
                    <h4>{{ __('Page Builder') }} - {{ $page->name }}</h4>
                </div>
                <div class="card-header-right">
                    <a href="{{ route('admin.frontend.pages') }}" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left mr-2"></i>{{ __('Back to Pages') }}
                    </a>
                    <a href="{{ route('admin.manage.theme') }}" class="btn btn-sm btn-info">
                        <i class="fa fa-palette mr-2"></i>{{ __('Manage Theme') }}
                    </a>
                    <a href="{{ route('admin.frontend.pages.edit', $page) }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-cog mr-2"></i>{{ __('Page Settings') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Left Sidebar - Widgets/Sections -->
                    <div class="col-lg-3">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Available Sections') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="widget-list" id="widgetList">
                                    @foreach ($sections as $key => $section)
                                        <div class="widget-item" data-section="{{ $section }}">
                                            <i class="fa fa-grip-vertical mr-2"></i>
                                            {{ Config::frontendformatter($section) }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Center - Page Builder Canvas -->
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Page Content') }}</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.frontend.pages.edit', $page) }}" id="pageBuilderForm">
                                    @csrf
                                    <input type="hidden" name="page" value="{{ $page->name }}">
                                    <input type="hidden" name="order" value="{{ $page->order }}">
                                    <input type="hidden" name="is_dropdown" value="{{ $page->is_dropdown }}">
                                    <input type="hidden" name="status" value="{{ $page->status }}">
                                    <input type="hidden" name="seo_description" value="{{ $page->seo_description ?? '' }}">
                                    @if($page->seo_keywords)
                                        @foreach($page->seo_keywords as $keyword)
                                            <input type="hidden" name="keyword[]" value="{{ $keyword }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="keyword[]" value="">
                                    @endif
                                    <div class="page-builder-canvas" id="pageBuilderCanvas">
                                        @if ($page->widgets && $page->widgets->count() > 0)
                                            @foreach ($page->widgets as $widget)
                                                <div class="builder-section-item" data-section="{{ $widget->sections }}">
                                                    <div class="section-header">
                                                        <i class="fa fa-grip-vertical handle"></i>
                                                        <span>{{ Config::frontendformatter($widget->sections) }}</span>
                                                        <div class="section-actions">
                                <a href="{{ route('admin.frontend.section.manage', ['name' => $widget->sections]) }}" 
                                   class="btn btn-sm btn-info" title="{{ __('Edit Section') }}">
                                    <i class="fa fa-edit"></i>
                                </a>
                                                            <button type="button" class="btn btn-sm btn-danger remove-section" title="{{ __('Remove') }}">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="sections[]" value="{{ $widget->sections }}">
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="empty-state">
                                                <i class="fa fa-magic fa-3x mb-3"></i>
                                                <p>{{ __('Drag sections from the left sidebar to build your page') }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save mr-2"></i>{{ __('Save Page') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar - Page Info & Theme Tools -->
                    <div class="col-lg-3">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Page Info') }}</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>{{ __('Name') }}:</strong> {{ $page->name }}</p>
                                <p><strong>{{ __('Slug') }}:</strong> {{ $page->slug }}</p>
                                <p><strong>{{ __('Status') }}:</strong> 
                                    @if($page->status)
                                        <span class="badge badge-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                    @endif
                                </p>
                                <p><strong>{{ __('Sections') }}:</strong> {{ $page->widgets->count() ?? 0 }}</p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">{{ __('Theme Tools') }}</h5>
                            </div>
                            <div class="card-body">
                                <a href="{{ route('admin.manage.theme') }}" class="btn btn-block btn-outline-primary mb-2">
                                    <i class="fa fa-palette mr-2"></i>{{ __('Edit Theme') }}
                                </a>
                                <a href="{{ route('admin.frontend.section.manage', 'banner') }}" class="btn btn-block btn-outline-info">
                                    <i class="fa fa-layout mr-2"></i>{{ __('Manage Sections') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .widget-list {
        max-height: 500px;
        overflow-y: auto;
    }

    .widget-item {
        padding: 10px;
        margin-bottom: 8px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        cursor: move;
        transition: all 0.3s;
    }

    .widget-item:hover {
        background: #e9ecef;
        border-color: #007bff;
    }

    .page-builder-canvas {
        min-height: 400px;
        padding: 20px;
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 4px;
    }

    .builder-section-item {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
        position: relative;
        transition: all 0.3s;
    }

    .builder-section-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .builder-section-item.ui-sortable-helper {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: rotate(2deg);
    }

    .section-header {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }

    .section-header .handle {
        cursor: move;
        color: #6c757d;
        margin-right: 10px;
    }

    .section-header span {
        flex: 1;
        font-weight: 600;
    }

    .section-actions {
        display: flex;
        gap: 5px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }

    .empty-state i {
        color: #dee2e6;
    }
</style>
@endpush

@push('script')
<script>
    $(function() {
        'use strict'

        // Make widgets draggable
        $('.widget-item').draggable({
            helper: 'clone',
            revert: 'invalid',
            cursor: 'move',
            zIndex: 1000
        });

        // Make canvas droppable
        $('#pageBuilderCanvas').droppable({
            accept: '.widget-item',
            hoverClass: 'border-primary',
            drop: function(event, ui) {
                const section = ui.draggable.data('section');
                const sectionName = ui.draggable.text().trim();
                
                // Remove empty state if exists
                $('.empty-state').remove();
                
                // Add new section
                const sectionHtml = `
                    <div class="builder-section-item" data-section="${section}">
                        <div class="section-header">
                            <i class="fa fa-grip-vertical handle"></i>
                            <span>${sectionName}</span>
                            <div class="section-actions">
                                <a href="/admin/frontend/manage/section/${section}" 
                                   class="btn btn-sm btn-info" title="{{ __('Edit Section') }}">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-danger remove-section" title="{{ __('Remove') }}">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="sections[]" value="${section}">
                    </div>
                `;
                
                $(this).append(sectionHtml);
                
                // Make new section sortable
                initSortable();
            }
        });

        // Make sections sortable
        function initSortable() {
            $('#pageBuilderCanvas').sortable({
                items: '.builder-section-item',
                handle: '.handle',
                placeholder: 'sortable-placeholder',
                cursor: 'move',
                tolerance: 'pointer',
                opacity: 0.8,
                update: function(event, ui) {
                    // Order is automatically maintained by DOM order
                }
            });
        }

        // Initialize sortable on existing items
        initSortable();

        // Remove section
        $(document).on('click', '.remove-section', function() {
            $(this).closest('.builder-section-item').fadeOut(300, function() {
                $(this).remove();
                
                // Show empty state if no sections
                if ($('#pageBuilderCanvas .builder-section-item').length === 0) {
                    $('#pageBuilderCanvas').html(`
                        <div class="empty-state">
                            <i class="fa fa-magic fa-3x mb-3"></i>
                            <p>{{ __('Drag sections from the left sidebar to build your page') }}</p>
                        </div>
                    `);
                }
            });
        });

        // Form submission
        $('#pageBuilderForm').on('submit', function(e) {
            // Ensure sections are in correct order
            const sections = [];
            $('#pageBuilderCanvas .builder-section-item').each(function() {
                sections.push($(this).data('section'));
            });
            
            // Update hidden inputs
            $('input[name="sections[]"]').remove();
            sections.forEach(function(section) {
                $('#pageBuilderCanvas').append(`<input type="hidden" name="sections[]" value="${section}">`);
            });
        });
    });
</script>
@endpush
