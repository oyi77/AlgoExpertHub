@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <div class="card-header-left">
                        <h4 class="mb-0">{{ __('OpenRouter Configurations') }}</h4>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.openrouter.models.index') }}" class="btn btn-sm btn-info me-2">
                            <i class="fa fa-list"></i> {{ __('View Models') }}
                        </a>
                        <a href="{{ route('admin.openrouter.configurations.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> {{ __('Create Configuration') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            <thead>
                                <tr>
                                    <th>{{ __('SL') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Model') }}</th>
                                    <th>{{ __('Usage') }}</th>
                                    <th>{{ __('Priority') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($configurations as $config)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $config->name }}</td>
                                        <td>
                                            <small class="text-muted">{{ $config->model_id }}</small>
                                        </td>
                                        <td>
                                            @if($config->use_for_parsing)
                                                <span class="badge badge-primary">Parsing</span>
                                            @endif
                                            @if($config->use_for_analysis)
                                                <span class="badge badge-success">Analysis</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $config->priority }}</span>
                                        </td>
                                        <td>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" name="status" {{ $config->enabled ? 'checked' : '' }}
                                                    class="custom-control-input config_status" id="status{{ $config->id }}"
                                                    data-id="{{ $config->id }}"
                                                    data-route="{{ route('admin.openrouter.configurations.toggle', $config->id) }}">
                                                <label class="custom-control-label" for="status{{ $config->id }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-info test-connection" 
                                                data-id="{{ $config->id }}"
                                                data-route="{{ route('admin.openrouter.configurations.test', $config->id) }}">
                                                <i class="fa fa-plug"></i>
                                            </button>
                                            <a href="{{ route('admin.openrouter.configurations.edit', $config->id) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.openrouter.configurations.destroy', $config->id) }}"
                                                method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="100%">
                                            {{ __('No Configuration Created Yet') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($configurations->hasPages())
                    <div class="card-footer">
                        {{ $configurations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    $(document).ready(function() {
        // Handle status toggle
        $('.config_status').on('change', function() {
            let checkbox = $(this);
            let route = checkbox.data('route');
            
            $.ajax({
                url: route,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success('Status updated successfully');
                },
                error: function() {
                    checkbox.prop('checked', !checkbox.prop('checked'));
                    toastr.error('Failed to update status');
                }
            });
        });

        // Handle test connection
        $('.test-connection').on('click', function() {
            let btn = $(this);
            let route = btn.data('route');
            let originalHtml = btn.html();
            
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: route,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message);
                    btn.prop('disabled', false).html(originalHtml);
                },
                error: function(xhr) {
                    let message = xhr.responseJSON?.message || 'Connection test failed';
                    toastr.error(message);
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
    });
</script>
@endpush

