@extends('backend.layout.master')
@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header site-card-header justify-content-between">
                    <div class="card-header-left">
                        <form action="" method="get">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="search ID">
                                <select name="type" id="" class="form-control form-control-sm">
                                    <option value="draft">{{__('Draft')}}</option>
                                    <option value="sent">{{__('Sent')}}</option>
                                </select>
                                <button class="btn btn-sm btn-primary"> <i class="fa fa-search"></i></button>
                            </div>
                        </form>
                    </div>

                    <div class="card-header-right">
                        <a class="btn btn-sm btn-primary" href="{{ route('admin.signals.create') }}"> <i class="fa fa-plus"></i>
                            {{ __('Create Signal') }}</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            <thead>
                                <tr>
                                    <th>{{ __('Signal Id') }}</th>
                                    <th>{{ __('Plans') }}</th>
                                    <th>{{ __('Pair') }}</th>
                                    <th>{{ __('Time Frame') }}</th>
                                    <th>{{ __('Opening point') }}</th>
                                    <th>{{ __('Stop Loss') }}</th>
                                    <th>{{ __('Take Profit') }}</th>
                                    <th>{{ __('Movement Direction') }}</th>
                                    <th>{{ __('Is Sent') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse ($signals as $signal)
                                    <tr>
                                        <td>{{ $signal->id }}</td>
                                       
                                        <td>
                                            @foreach ($signal->plans as $plan)
                                                <span class="badge badge-info">{{ $plan->name }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            {{ optional($signal->pair)->name }}
                                        </td>

                                        <td>
                                            {{ optional($signal->time)->name }}
                                        </td>

                                        <td>
                                            {{ $signal->open_price }}
                                        </td>

                                        <td>
                                            {{ $signal->sl }}
                                        </td>

                                        <td>
                                            {{ $signal->tp }}
                                        </td>

                                        <td>
                                            @if ($signal->direction === 'buy')
                                                <span class="badge badge-success">{{ $signal->direction }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ $signal->direction }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($signal->is_published)
                                                <span class="badge badge-success">{{ __('Sent') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ __('Draft') }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            <a href="{{ route('admin.signals.edit', $signal->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-edit"></i>
                                            </a>

                                            @if (!$signal->is_published)
                                                <button data-href="{{ route('admin.signals.sent', $signal->id) }}"
                                                    class="btn btn-sm btn-outline-success sent"><i class="fa fa-paper-plane"></i></button>
                                            @endif
                                            <button data-href="{{ route('admin.signals.destroy', $signal->id) }}"
                                                class="btn btn-sm btn-outline-danger delete"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="100%">{{ __('No Signals Found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>

                @if ($signals->hasPages())
                    <div class="card-footer">
                        {{ $signals->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>


@endsection

@push('script')
    <script>
        $(function() {
            'use strict'

            $('.delete').on('click', function(e) {
                e.preventDefault()
                const url = $(this).data('href')
                
                Swal.fire({
                    title: '{{ __('Confirmation') }}!',
                    text: '{{ __('Are you sure you want to Delete') }}?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '<i class="fa fa-trash"></i> {{ __('DELETE') }}',
                    cancelButtonText: '<i class="fa fa-times"></i> {{ __('Close') }}'
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
                        form.append($('<input>', {
                            'type': 'hidden',
                            'name': '_method',
                            'value': 'DELETE'
                        }))
                        $('body').append(form)
                        form.submit()
                    }
                })
            })

            $('.sent').on('click', function(e) {
                e.preventDefault()
                const url = $(this).data('href')
                
                Swal.fire({
                    title: '{{ __('Confirmation') }}!',
                    text: '{{ __('Are you sure you want to Send This Signal') }}?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Sent') }}',
                    cancelButtonText: '<i class="fa fa-times"></i> {{ __('Close') }}'
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
