@extends('backend.layout.master')

@section('element')
@push('external-style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.css">
<style>
    .menu-builder {
        display: flex;
        gap: 20px;
    }
    .menu-items {
        flex: 1;
        min-height: 400px;
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 4px;
    }
    .menu-item {
        padding: 10px;
        margin: 5px 0;
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: move;
    }
    .menu-item:hover {
        background: #e9ecef;
    }
    .available-pages {
        flex: 1;
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 4px;
    }
    .page-item {
        padding: 8px;
        margin: 5px 0;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
    }
    .page-item:hover {
        background: #f8f9fa;
    }
</style>
@endpush

@section('element')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ $title }}</h4>
                    <div>
                        <a href="{{ route('admin.page-builder.menus.create') }}" class="btn btn-primary">
                            <i data-feather="plus"></i> {{ __('Create Menu') }}
                        </a>
                        <button type="button" class="btn btn-success" id="syncMenu">
                            <i data-feather="refresh-cw"></i> {{ __('Sync from Pages') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($menus) > 0)
                        <div class="menu-builder">
                            <div class="available-pages">
                                <h5>{{ __('Available Pages') }}</h5>
                                <div id="availablePages">
                                    @foreach($pages as $page)
                                        <div class="page-item" data-page-id="{{ $page->id }}" data-page-name="{{ $page->name }}" data-page-slug="{{ $page->slug }}">
                                            <i data-feather="file-text"></i> {{ $page->name }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="menu-items">
                                <h5>{{ __('Menu Items') }}</h5>
                                <div id="menuItems" data-menu-id="{{ $menus[0]->id ?? '' }}">
                                    @if(isset($menus[0]) && $menus[0]->structure)
                                        @foreach($menus[0]->structure as $item)
                                            <div class="menu-item" data-item-id="{{ $item['id'] ?? '' }}">
                                                {{ $item['name'] ?? '' }}
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary" id="saveMenu">{{ __('Save Menu') }}</button>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p>{{ __('No menus found.') }}</p>
                            <a href="{{ route('admin.page-builder.menus.create') }}" class="btn btn-primary">
                                <i data-feather="plus"></i> {{ __('Create Your First Menu') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('external-script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
'use strict';
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sortable for menu items
    const menuItems = document.getElementById('menuItems');
    if (menuItems) {
        new Sortable(menuItems, {
            animation: 150,
            ghostClass: 'sortable-ghost'
        });
    }

    // Add page to menu on click
    const availablePages = document.getElementById('availablePages');
    if (availablePages) {
        availablePages.addEventListener('click', function(e) {
            const pageItem = e.target.closest('.page-item');
            if (pageItem) {
                const pageId = pageItem.dataset.pageId;
                const pageName = pageItem.dataset.pageName;
                const pageSlug = pageItem.dataset.pageSlug;
                
                // Check if already in menu
                const existing = Array.from(menuItems.children).find(item => 
                    item.dataset.itemId === pageId
                );
                
                if (!existing) {
                    const menuItem = document.createElement('div');
                    menuItem.className = 'menu-item';
                    menuItem.dataset.itemId = pageId;
                    menuItem.innerHTML = `<i data-feather="file-text"></i> ${pageName}`;
                    menuItems.appendChild(menuItem);
                    
                    // Reinitialize feather icons
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
            }
        });
    }

    // Save menu
    const saveBtn = document.getElementById('saveMenu');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const menuId = menuItems.dataset.menuId;
            const items = Array.from(menuItems.children).map((item, index) => ({
                id: item.dataset.itemId,
                name: item.textContent.trim(),
                order: index
            }));

            fetch(`{{ url('admin/page-builder/menus') }}/${menuId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    structure: items
                })
            })
            .then(response => response.json())
            .then(data => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("Success") }}',
                        text: '{{ __("Menu saved successfully") }}',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert('{{ __("Menu saved successfully") }}');
                }
            })
            .catch(error => {
                console.error('Error saving menu:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Error") }}',
                        text: '{{ __("Failed to save menu") }}'
                    });
                } else {
                    alert('{{ __("Failed to save menu") }}');
                }
            });
        });
    }

    // Sync menu from pages
    const syncBtn = document.getElementById('syncMenu');
    if (syncBtn) {
        syncBtn.addEventListener('click', function() {
            fetch('{{ route("admin.page-builder.menus.sync") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                location.reload();
            })
            .catch(error => {
                console.error('Error syncing menu:', error);
            });
        });
    }
});
</script>
@endpush

