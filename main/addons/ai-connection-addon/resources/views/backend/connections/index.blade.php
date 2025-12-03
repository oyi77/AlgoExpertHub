@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <div class="card-header-left">
                        <form action="" method="get" class="form-inline">
                            <select name="provider_id" class="form-control form-control-sm mr-2">
                                <option value="">{{ __('All Providers') }}</option>
                                @foreach ($providers as $provider)
                                    <option value="{{ $provider->id }}" {{ request('provider_id') == $provider->id ? 'selected' : '' }}>
                                        {{ $provider->name }}
                                    </option>
                                @endforeach
                            </select>
                            <select name="status" class="form-control form-control-sm mr-2">
                                <option value="">{{ __('All Status') }}</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                <option value="error" {{ request('status') == 'error' ? 'selected' : '' }}>{{ __('Error') }}</option>
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">
                                <i class="fa fa-filter"></i> {{ __('Filter') }}
                            </button>
                        </form>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.ai-connections.connections.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> {{ __('Add Connection') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            <thead>
                                <tr>
                                    <th>{{ __('Priority') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Provider') }}</th>
                                    <th>{{ __('Rate Limits') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Health') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($connections as $connection)
                                    <tr>
                                        <td><span class="badge badge-secondary">{{ $connection->priority }}</span></td>
                                        <td>{{ $connection->name }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $connection->provider->name }}</span>
                                        </td>
                                        <td>
                                            <small>
                                                {{ $connection->rate_limit_per_minute ?? '∞' }}/min<br>
                                                {{ $connection->rate_limit_per_day ?? '∞' }}/day
                                            </small>
                                        </td>
                                        <td>
                                            @if ($connection->status === 'active')
                                                <span class="badge badge-success">{{ __('Active') }}</span>
                                            @elseif ($connection->status === 'inactive')
                                                <span class="badge badge-secondary">{{ __('Inactive') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ __('Error') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($connection->error_count > 0)
                                                <span class="badge badge-warning">
                                                    {{ $connection->error_count }} {{ __('errors') }}
                                                </span>
                                            @else
                                                <span class="badge badge-success">{{ __('Healthy') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-info test-connection"
                                                    data-id="{{ $connection->id }}"
                                                    data-url="{{ route('admin.ai-connections.connections.test', $connection->id) }}">
                                                    <i class="fa fa-plug"></i>
                                                </button>
                                                <a href="{{ route('admin.ai-connections.connections.edit', $connection->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.ai-connections.connections.toggle-status', $connection->id) }}"
                                                    method="POST" style="display: inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-{{ $connection->status === 'active' ? 'warning' : 'success' }}">
                                                        <i class="fa fa-{{ $connection->status === 'active' ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.ai-connections.connections.destroy', $connection->id) }}"
                                                    method="POST" style="display: inline-block;"
                                                    onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="100%">
                                            {{ __('No Connections Created Yet') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($connections->hasPages())
                    <div class="card-footer">
                        {{ $connections->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    $(function() {
        'use strict';

        $('.test-connection').on('click', function() {
            let btn = $(this);
            let url = btn.data('url');
            
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    btn.prop('disabled', false).html('<i class="fa fa-plug"></i>');
                    
                    if (response.success) {
                        alert('{{ __('Connection test successful!') }}');
                    } else {
                        alert('{{ __('Connection test failed:') }} ' + (response.message || '{{ __('Unknown error') }}'));
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html('<i class="fa fa-plug"></i>');
                    alert('{{ __('Failed to test connection') }}');
                }
            });
        });
    });
</script>
@endpush

