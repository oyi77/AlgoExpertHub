@extends(Config::themeView('layout.auth'))


@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="sp_site_card">

                <div class="card-header text-end">
                    <form action="" method="get" class="row justify-content-md-end g-3">
                        
                        <div class="col-auto">
                            <input type="date" class="form-control me-3" placeholder="Search User" name="date">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn sp_theme_btn">{{ __('Search') }}</button>
                        </div>
                    </form>

                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table sp_site_table">
                            <thead>
                                <tr>
                                    <th>{{ __('Commission From') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Commission Date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($interestLogs as $item)
                                    <tr>
                                        <td data-caption="From">
                                            {{ optional($item->whoSendTheMoney)->username ?? __('N/A') }}
                                        </td>
                                        <td data-caption="Amount">{{ Config::formatter($item->amount) }}</td>
                                        <td data-caption="Type">{{ ucfirst($item->type ?? 'N/A') }}</td>
                                        <td data-caption="{{ __('Date') }}">{{ $item->created_at->format('d M , Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td data-caption="Data" class="text-center" colspan="100%">{{ __('No Data Found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($interestLogs->hasPages())
                    <div class="card-footer">
                        {{ $interestLogs->links() }}

                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

