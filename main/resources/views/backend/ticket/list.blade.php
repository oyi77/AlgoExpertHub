@extends('backend.layout.master')

@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <form action="" method="get">
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm" placeholder="support id"
                                name="search">
                            <select name="status" class="form-control form-control-sm">
                                <option value="">{{ __('Select Status') }}</option>
                                <option value="closed">{{ __('Closed') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="answered">{{ __('Answered') }}</option>
                            </select>
                            <div class="input-group-append">
                                <button class="btn btn-sm btn-primary" type="submit"> <i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table student-data-table m-t-20">
                            <thead>
                                <tr>
                                    <th>{{ __('Support Id') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Subject') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Created Date') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                    <tr>
                                        <td scope="row"><b>{{ $ticket->support_id }}</b></td>
                                        <td>{{ $ticket->user->username }}</td>
                                        <td>{{ $ticket->subject }}</td>
                                        <td>
                                            @if ($ticket->status == 1)
                                                <span class="badge badge-danger"> {{ __('Closed') }} </span>
                                            @endif
                                            @if ($ticket->status == 2)
                                                <span class="badge badge-warning"> {{ __('Pending') }} </span>
                                            @endif
                                            @if ($ticket->status == 3)
                                                <span class="badge badge-success"> {{ __('Answered') }}</span>
                                            @endif
                                        </td>

                                        <td>{{ $ticket->created_at->diffforhumans() }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary btn-action"
                                                href="{{ route('admin.ticket.show', $ticket->id) }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button data-href="{{ route('admin.ticket.destroy', $ticket->id) }}"
                                                class="btn btn-sm btn-outline-danger delete_confirm">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('NO TICKET FOUND') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($tickets->hasPages())
                    <div class="card-footer">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>


@endsection

@push('script')
    <script>
        'use strict'
        $('.delete_confirm').on('click', function(e) {
            e.preventDefault()
            const url = $(this).data('href')
            
            Swal.fire({
                title: '{{ __('Delete Support Ticket') }}',
                text: '{{ __('Are you sure you want to delete this ticket?') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-times"></i> {{ __('Delete') }}',
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
    </script>
@endpush
