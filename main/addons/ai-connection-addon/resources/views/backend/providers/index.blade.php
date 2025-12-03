@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <div class="card-header-left">
                        <h4 class="card-title">{{ __('AI Providers') }}</h4>
                    </div>
                    <div class="card-header-right">
                        <a href="{{ route('admin.ai-connections.providers.create') }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i> {{ __('Add Provider') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            <thead>
                                <tr>
                                    <th>{{ __('SL') }}.</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Slug') }}</th>
                                    <th>{{ __('Connections') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($providers as $provider)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $provider->name }}</td>
                                        <td><code>{{ $provider->slug }}</code></td>
                                        <td>
                                            <span class="badge badge-info">{{ $provider->connections_count }} {{ __('connections') }}</span>
                                        </td>
                                        <td>
                                            @if ($provider->status === 'active')
                                                <span class="badge badge-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.ai-connections.providers.edit', $provider->id) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.ai-connections.providers.destroy', $provider->id) }}"
                                                method="POST" style="display: inline-block;"
                                                onsubmit="return confirm('{{ __('Are you sure?') }}')">
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
                                            {{ __('No Providers Created Yet') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

