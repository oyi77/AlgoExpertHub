@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <div class="card-header-left">
                        <h4>{{ __('All Pages') }}</h4>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.frontend.pages.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus mr-2"></i>
                            {{ __('Create Page') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table" id="example">
                            <thead>
                                <tr>

                                    <th>{{ __('Page Name') }}</th>
                                    <th>{{ __('Page Order') }}</th>
                                    <th>{{ __('Dropdown') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pages as $key => $page)
                                    <tr>

                                        <td>
                                            {{ $page->name }}
                                        </td>

                                        <td>
                                            {{ $page->order }}
                                        </td>

                                        <td>
                                            @if ($page->is_dropdown)
                                                <span class="badge badge-primary">{{ __('Yes') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ __('No') }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($page->status)
                                                <span class="badge badge-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ __('In active') }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            <a href="{{ route('admin.frontend.pages.edit', $page) }}"
                                                class="btn btn-sm btn-outline-primary edit">
                                                <i class="fa fa-pen"></i>
                                            </a>
                                            @php
                                                $pageBuilderEnabled = \App\Support\AddonRegistry::active('page-builder-addon') 
                                                    && \App\Support\AddonRegistry::moduleEnabled('page-builder-addon', 'admin_ui');
                                            @endphp
                                            @if ($pageBuilderEnabled)
                                                <a href="{{ route('admin.page-builder.edit', $page->id) }}"
                                                    class="btn btn-sm btn-outline-success" title="{{ __('Edit in Page Builder') }}">
                                                    <i class="fa fa-magic"></i>
                                                </a>
                                            @endif
                                            @if (!$loop->first)
                                                <a href="#" class="btn btn-sm btn-outline-danger delete"
                                                    data-url="{{ route('admin.frontend.pages.delete', $page) }}"><i
                                                        class="fa fa-trash"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center text-danger" colspan="100%">
                                            {{ __('No Data Found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($pages->hasPages())
                    <div class="card-footer">
                        {{ $pages->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>



@endsection

@push('script')
    <script>
        'use strict'
        $(function() {
            $('.delete').on('click', function(e) {
                e.preventDefault()
                const url = $(this).data('url')
                
                Swal.fire({
                    title: '{{ __('Delete Page') }}',
                    text: '{{ __('Are You Sure To Delete Pages') }}?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Delete Page') }}',
                    cancelButtonText: '{{ __('Close') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('<form>', {
                            'method': 'POST',
                            'action': url
                        })
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_token',
                            'value': '{{ csrf_token() }}'
                        }))
                        $('body').append(form)
                        form.submit()
                    }
                })
            })
        })
    </script>
@endpush
