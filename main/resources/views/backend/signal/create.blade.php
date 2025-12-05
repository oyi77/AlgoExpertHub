@extends('backend.layout.master')
@section('element')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.signals.index') }}"> <i
                            class="fa fa-arrow-left"></i>
                        {{ __('Back') }}</a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.signals.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="col-form-label">{{ __('Signal Image') }}</label>
                                    <div id="image-preview" class="image-preview"
                                        style="background-image:url({{ Config::getFile('signal', '', true) }});">
                                        <label for="image-upload" id="image-label">{{ __('Choose File') }}</label>
                                        <input type="file" name="image" id="image-upload" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label for="">{{ __('Signal Title') }}</label>
                                        <input type="text" name="title" class="form-control"
                                            value="{{ old('titlte') }}">
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label for="">{{ __('Plans') }} <input type="checkbox" id="checkbox"
                                                class="mx-3">{{ __('Select All') }}</label>
                                        <select name="plans[]" id="selectMulti" multiple>
                                            @foreach ($plans as $plan)
                                                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label for="">{{ __('Currency pair') }}</label>

                                        <select name="currency_pair" class="form-control pair">
                                            @foreach ($pairs as $pair)
                                                <option value="{{ $pair->id }}">{{ $pair->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label for="">{{ __('Time Frame') }}</label>
                                        <select name="time_frame" class="form-control frame">
                                            @foreach ($times as $time)
                                                <option value="{{ $time->id }}">{{ $time->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label for="">{{ __('Starting Point') }}</label>
                                        <input type="text" class="form-control" name="open_price">
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label for="">{{ __('Stop Loss') }}</label>
                                        <input type="text" class="form-control" name="sl">
                                    </div>

                                    <div class="col-md-4 mb-4">
                                        <label for="">{{ __('Take profit') }} <small class="text-muted">(Primary/Default)</small></label>
                                        <input type="text" class="form-control" name="tp" id="primary_tp">
                                        <small class="text-muted">Used if no multiple TPs defined</small>
                                    </div>
                                    
                                    <div class="col-md-12 mb-4">
                                        <label>
                                            {{ __('Multiple Take Profits') }}
                                            <button type="button" class="btn btn-sm btn-info ml-2" id="add-tp-btn">
                                                <i class="fa fa-plus"></i> Add TP Level
                                            </button>
                                        </label>
                                        <div id="multiple-tps-container" class="mt-2">
                                            <!-- Dynamic TP fields will be added here -->
                                        </div>
                                        <small class="text-muted">Add multiple TP levels for partial closes. If multiple TPs are defined, they will be used instead of the primary TP above.</small>
                                    </div>

                                    <div class="col-md-4 mb-4">
                                        <label for="">{{ __('Place Order Direction') }}</label>
                                        <select name="direction" class="form-control">
                                            <option value="buy">{{ __('BUY') }}</option>
                                            <option value="sell">{{ __('SELL') }}</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-4">
                                        <label for="">{{ __('Markets Type') }}</label>
                                        <select name="market" class="form-control">
                                            @foreach ($markets as $market)
                                                <option value="{{ $market->id }}">{{ $market->name }}</option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <div class="col-md-12">
                                        <label for="">{{ __('Description') }}</label>
                                        <textarea name="description" cols="30" rows="10" class="form-control summernote"></textarea>
                                    </div>

                                    <div class="col-md-12 mt-4">
                                        <input type="submit" class="btn btn-warning" name="type" value="Draft">
                                        <input type="submit" class="btn btn-primary" name="type" value="Send">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('external-style')
    <link rel="stylesheet" href="{{ Config::cssLib('backend', 'select2.min.css') }}">
@endpush

@push('external-script')
    <script src="{{ Config::jsLib('backend', 'select2.min.js') }}"></script>
@endpush

@push('script')
    <script>
        $(function() {

            'use strict'

            $("#selectMulti,.pair,.frame").select2();

            $("#checkbox").on('click', function() {

                if ($("#checkbox").is(':checked') === true) {
                    $("#selectMulti > option").prop("selected", "selected"); // Select All Options
                    $("#selectMulti").trigger("change"); // Trigger change to select 2
                } else {

                    $("#selectMulti > option").removeAttr("selected");
                    $("#selectMulti").trigger("change"); // Trigger change to select 2
                }


            });

            // Multiple TP management
            let tpCounter = 0;
            
            $('#add-tp-btn').on('click', function() {
                tpCounter++;
                const tpHtml = `
                    <div class="row mb-2 tp-row" data-tp-index="${tpCounter}">
                        <div class="col-md-3">
                            <label>TP Level ${tpCounter} Price</label>
                            <input type="text" class="form-control" name="take_profits[${tpCounter}][tp_price]" placeholder="TP Price" required>
                        </div>
                        <div class="col-md-3">
                            <label>Lot Percentage (%)</label>
                            <input type="text" class="form-control" name="take_profits[${tpCounter}][lot_percentage]" placeholder="e.g., 33.33" value="${100 / (tpCounter + 1)}">
                            <small class="text-muted">% of position to close at this TP</small>
                        </div>
                        <div class="col-md-3">
                            <label>TP Percentage (%)</label>
                            <input type="text" class="form-control" name="take_profits[${tpCounter}][tp_percentage]" placeholder="Optional">
                            <small class="text-muted">Alternative to lot percentage</small>
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-sm btn-danger form-control remove-tp-btn">
                                <i class="fa fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                `;
                $('#multiple-tps-container').append(tpHtml);
            });
            
            $(document).on('click', '.remove-tp-btn', function() {
                $(this).closest('.tp-row').remove();
                // Renumber remaining TPs
                $('#multiple-tps-container .tp-row').each(function(index) {
                    $(this).find('label').first().text(`TP Level ${index + 1} Price`);
                    $(this).attr('data-tp-index', index + 1);
                    const inputs = $(this).find('input');
                    inputs.eq(0).attr('name', `take_profits[${index + 1}][tp_price]`);
                    inputs.eq(1).attr('name', `take_profits[${index + 1}][lot_percentage]`);
                    inputs.eq(2).attr('name', `take_profits[${index + 1}][tp_percentage]`);
                });
            });

            $.uploadPreview({
                input_field: "#image-upload",
                preview_box: "#image-preview",
                label_field: "#image-label",
                label_default: "Choose File",
                label_selected: "Update Image",
                no_label: false,
                success_callback: null
            });
        })
    </script>
@endpush
