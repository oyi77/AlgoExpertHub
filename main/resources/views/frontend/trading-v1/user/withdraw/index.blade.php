@extends(Config::themeView('layout.auth'))

@section('content')
    <div class="row gy-4">
        <div class="col-md-6">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4 class="mb-0">
                        {{ __('Current Balance: ') }} <span
                            class="badge bg-primary" style="font-size: 1.2em;">{{ Config::formatter(auth()->user()->balance, 2) }}</span>
                    </h4>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        @csrf

                        <div class="form-group">
                            <label for="">{{ __('Withdraw Method') }}</label>
                            <select name="method" id="" class="form-select">
                                <option value="" selected>{{ __('Select Method') }}</option>
                                @foreach ($withdraws as $withdraw)
                                    <option value="{{ $withdraw->id }}"
                                        data-url="{{ route('user.withdraw.fetch', $withdraw->id) }}">
                                        {{ $withdraw->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row appendData"></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 withdraw-ins">
            <div class="sp_site_card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('Withdraw Instruction') }}</h4>
                </div>
                <div class="card-body">
                    <div class="instruction-placeholder" style="display: block;">
                        <div class="text-center py-5">
                            <i class="las la-hand-point-up la-3x" style="opacity: 0.3;"></i>
                            <p class="text-muted mt-3">{{ __('Select a withdraw method to view instructions') }}</p>
                        </div>
                    </div>
                    <div class="instruction" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $(function() {
            'use strict'

            $('select[name=method]').on('change', function() {
                if ($(this).val() == '') {
                    $('.appendData').addClass('d-none');
                    $('.instruction').hide().html('');
                    $('.instruction-placeholder').show();
                    return;
                }
                $('.appendData').removeClass('d-none');
                $('.instruction-placeholder').hide();
                $('.instruction').show();
                getData($('select[name=method] option:selected').data('url'))
            })

            $(document).on('keyup', '.amount', function() {
                const withdraw_charge_type = $('.withdraw_charge_type').text();

                if ($(this).val() == '') {
                    $('.final_amo').val(0);
                    return
                }

                const charge = $('.charge').val();

                if (withdraw_charge_type.localeCompare("percent") == 1) {
                    let percentAmount = Number.parseFloat($(this).val()) - Number.parseFloat((charge * $(
                        this).val()) / 100);

                    $('.final_amo').val(percentAmount.toFixed(2));
                    return
                }
                if (withdraw_charge_type.localeCompare("fixed") == 1) {

                    let totalAmount = Number.parseFloat($(this).val()) - Number.parseFloat(charge);

                    $('.final_amo').val(totalAmount).toFixed(2);
                }



            })

            function getData(url) {
                $.ajax({
                    url: url,
                    method: "GET",
                    success: function(response) {

                        $('.instruction').html(response.instruction)
                        let html = `

                                <div class="col-md-12 mb-3 mt-3">
                                    <label for="">{{ __('Withdraw Amount') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="amount" class="form-control amount" required>
                                    <p class="small text-muted mb-0 mt-1"><span>{{ __('Min Amount ') }}  ${Number.parseFloat(response.min_withdraw_amount).toFixed(2)}</span> <span>{{ __('& Max Amount') }} ${Number.parseFloat(response.max_withdraw_amount).toFixed(2)}</span></p>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label>{{ __('Withdraw Charge') }}</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control charge" value="${Number.parseFloat(response.charge).toFixed(2)}" required disabled>
                                        <div class="input-group-text">
                                            <span class="withdraw_charge_type">${response.type}<span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="">{{ __('Getable Amount') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="final_amo" class="form-control final_amo" required readonly>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="">{{ __('Account Email / Wallet Address') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="email" class="form-control" required>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="">{{ __('Account Information') }}</label>
                                   <textarea class="form-control" name="account_information" rows="5"></textarea>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="">{{ __('Additional Note') }}</label>
                                   <textarea class="form-control" name="note" rows="5"></textarea>
                                </div>

                                <div class="col-md-12 mt-2">
                                   <button class="btn sp_theme_btn w-100" type="submit">{{ __('Withdraw Now') }}</button>
                                </div>
                   `;

                        $('.appendData').html(html);
                    }
                })
            }
        })
    </script>
@endpush

