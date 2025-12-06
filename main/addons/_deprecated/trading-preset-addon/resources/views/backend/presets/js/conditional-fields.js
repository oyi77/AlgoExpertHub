<script>
'use strict'

$(function() {
    // Position Size Mode
    $('#position_size_mode').on('change', function() {
        const mode = $(this).val();
        if (mode === 'FIXED') {
            $('#fixed_lot_group').show();
            $('#risk_per_trade_group').hide();
        } else {
            $('#fixed_lot_group').hide();
            $('#risk_per_trade_group').show();
        }
    });

    // Dynamic Equity Mode
    $('#equity_dynamic_mode').on('change', function() {
        const mode = $(this).val();
        if (mode === 'NONE') {
            $('#equity_dynamic_fields').hide();
            $('#equity_step_fields').hide();
            $('#risk_range_fields').hide();
        } else {
            $('#equity_dynamic_fields').show();
            $('#risk_range_fields').show();
            if (mode === 'STEP') {
                $('#equity_step_fields').show();
            } else {
                $('#equity_step_fields').hide();
            }
        }
    });

    // Stop Loss Mode
    $('#sl_mode').on('change', function() {
        const mode = $(this).val();
        $('#sl_pips_group').hide();
        $('#sl_r_multiple_group').hide();
        $('#sl_structure_info').hide();
        
        if (mode === 'PIPS') {
            $('#sl_pips_group').show();
        } else if (mode === 'R_MULTIPLE') {
            $('#sl_r_multiple_group').show();
        } else if (mode === 'STRUCTURE') {
            $('#sl_structure_info').show();
        }
    });

    // Take Profit Mode
    $('#tp_mode').on('change', function() {
        const mode = $(this).val();
        if (mode === 'SINGLE') {
            $('#single_tp_group').show();
            $('#multi_tp_group').hide();
        } else if (mode === 'MULTI') {
            $('#single_tp_group').hide();
            $('#multi_tp_group').show();
        } else {
            $('#single_tp_group').hide();
            $('#multi_tp_group').hide();
        }
    });

    // Break-Even Toggle
    $('#be_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#be_fields').slideDown();
        } else {
            $('#be_fields').slideUp();
        }
    });

    // Trailing Stop Toggle
    $('#ts_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#ts_fields').slideDown();
        } else {
            $('#ts_fields').slideUp();
        }
    });

    // Trailing Stop Mode
    $('#ts_mode').on('change', function() {
        const mode = $(this).val();
        $('#ts_step_pips_group').hide();
        $('#ts_atr_group').hide();
        
        if (mode === 'STEP_PIPS') {
            $('#ts_step_pips_group').show();
        } else if (mode === 'STEP_ATR' || mode === 'CHANDELIER') {
            $('#ts_atr_group').show();
        }
    });

    // Layering Toggle
    $('#layering_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#layering_fields').slideDown();
        } else {
            $('#layering_fields').slideUp();
        }
    });

    // Martingale Mode
    $('#layer_martingale_mode').on('change', function() {
        const mode = $(this).val();
        if (mode === 'NONE') {
            $('#martingale_factor_group').hide();
        } else {
            $('#martingale_factor_group').show();
        }
    });

    // Hedging Toggle
    $('#hedging_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#hedging_fields').slideDown();
        } else {
            $('#hedging_fields').slideUp();
        }
    });

    // Candle Exit Toggle
    $('#auto_close_on_candle_close').on('change', function() {
        if ($(this).is(':checked')) {
            $('#candle_exit_fields').slideDown();
        } else {
            $('#candle_exit_fields').slideUp();
        }
    });

    // Trading Schedule Toggle
    $('#only_trade_in_session').on('change', function() {
        if ($(this).is(':checked')) {
            $('#trading_schedule_fields').slideDown();
        } else {
            $('#trading_schedule_fields').slideUp();
        }
    });

    // Session Profile
    $('#session_profile').on('change', function() {
        const profile = $(this).val();
        if (profile === 'CUSTOM') {
            $('#custom_hours_group').show();
        } else {
            $('#custom_hours_group').hide();
        }
    });

    // Weekly Target Toggle
    $('#weekly_target_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#weekly_target_fields').slideDown();
        } else {
            $('#weekly_target_fields').slideUp();
        }
    });

    // Trading Days Mask Calculator
    function updateTradingDaysMask() {
        let mask = 0;
        $('.trading-day-checkbox:checked').each(function() {
            mask += parseInt($(this).data('bit'));
        });
        $('#trading_days_mask').val(mask);
    }

    $('.trading-day-checkbox').on('change', function() {
        updateTradingDaysMask();
    });

    // Risk Warning
    $('#risk_per_trade_pct').on('input', function() {
        const risk = parseFloat($(this).val()) || 0;
        if (risk > 10) {
            $('#risk_warning').show();
        } else {
            $('#risk_warning').hide();
        }
    });

    // Initialize on page load
    $('#position_size_mode').trigger('change');
    $('#equity_dynamic_mode').trigger('change');
    $('#sl_mode').trigger('change');
    $('#tp_mode').trigger('change');
    $('#ts_mode').trigger('change');
    $('#layer_martingale_mode').trigger('change');
    $('#session_profile').trigger('change');
    updateTradingDaysMask();
});
</script>

