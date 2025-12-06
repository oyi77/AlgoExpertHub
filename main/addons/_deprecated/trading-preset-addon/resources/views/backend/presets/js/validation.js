<script>
'use strict'

$(function() {
    // Real-time validation
    function validateField(field, rules) {
        const value = $(field).val();
        const fieldGroup = $(field).closest('.form-group');
        
        // Remove previous validation classes
        $(field).removeClass('is-invalid is-valid');
        fieldGroup.find('.invalid-feedback').remove();
        
        let isValid = true;
        let errorMessage = '';

        // Required validation
        if ($(field).prop('required') && (!value || value.trim() === '')) {
            isValid = false;
            errorMessage = 'This field is required.';
        }

        // Numeric validation
        if (value && $(field).attr('type') === 'number') {
            const numValue = parseFloat(value);
            const min = parseFloat($(field).attr('min'));
            const max = parseFloat($(field).attr('max'));

            if (isNaN(numValue)) {
                isValid = false;
                errorMessage = 'Please enter a valid number.';
            } else if (min !== undefined && numValue < min) {
                isValid = false;
                errorMessage = `Value must be at least ${min}.`;
            } else if (max !== undefined && numValue > max) {
                isValid = false;
                errorMessage = `Value must be at most ${max}.`;
            }
        }

        // R:R Ratio validation (TP should be > SL)
        if ($(field).attr('id') && $(field).attr('id').includes('tp') && $(field).attr('id').includes('rr')) {
            const slMode = $('#sl_mode').val();
            const tpRR = parseFloat(value);
            
            if (slMode === 'R_MULTIPLE' && tpRR) {
                const slRR = parseFloat($('#sl_r_multiple').val()) || 1;
                if (tpRR <= slRR) {
                    isValid = false;
                    errorMessage = `TP R:R (${tpRR}) should be greater than SL R:R (${slRR}).`;
                }
            }
        }

        // Risk percentage validation
        if ($(field).attr('id') === 'risk_per_trade_pct') {
            const risk = parseFloat(value);
            if (risk > 10) {
                // Show warning but don't invalidate
                $('#risk_warning').show();
            } else {
                $('#risk_warning').hide();
            }
        }

        // Max positions validation
        if ($(field).attr('id') === 'max_positions_per_symbol') {
            const maxPerSymbol = parseInt(value) || 0;
            const maxTotal = parseInt($('#max_positions').val()) || 0;
            
            if (maxPerSymbol > maxTotal) {
                isValid = false;
                errorMessage = `Max per symbol (${maxPerSymbol}) cannot exceed max positions (${maxTotal}).`;
            }
        }

        // Risk range validation
        if ($(field).attr('id') === 'risk_max_pct') {
            const maxRisk = parseFloat(value) || 0;
            const minRisk = parseFloat($('#risk_min_pct').val()) || 0;
            
            if (maxRisk > 0 && minRisk > 0 && maxRisk < minRisk) {
                isValid = false;
                errorMessage = `Max risk (${maxRisk}%) must be greater than or equal to min risk (${minRisk}%).`;
            }
        }

        // Apply validation styling
        if (value && value.trim() !== '') {
            if (isValid) {
                $(field).addClass('is-valid');
            } else {
                $(field).addClass('is-invalid');
                if (errorMessage) {
                    fieldGroup.append(`<span class="invalid-feedback"><strong>${errorMessage}</strong></span>`);
                }
            }
        }

        return isValid;
    }

    // Validate on blur
    $('input[type="number"], input[type="text"], select').on('blur', function() {
        validateField(this);
    });

    // Validate on change for select fields
    $('select').on('change', function() {
        validateField(this);
    });

    // Form submission validation
    $('#preset-form').on('submit', function(e) {
        let isValid = true;
        const requiredFields = $(this).find('[required]');
        
        requiredFields.each(function() {
            if (!validateField(this)) {
                isValid = false;
            }
        });

        // Validate interdependent fields
        const positionMode = $('#position_size_mode').val();
        if (positionMode === 'FIXED') {
            const fixedLot = parseFloat($('#fixed_lot').val());
            if (!fixedLot || fixedLot <= 0) {
                isValid = false;
                $('#fixed_lot').addClass('is-invalid');
            }
        } else {
            const riskPct = parseFloat($('#risk_per_trade_pct').val());
            if (!riskPct || riskPct <= 0) {
                isValid = false;
                $('#risk_per_trade_pct').addClass('is-invalid');
            }
        }

        // Validate TP mode
        const tpMode = $('#tp_mode').val();
        if (tpMode === 'SINGLE' || tpMode === 'MULTI') {
            const tp1Enabled = $('#tp1_enabled').is(':checked') || $('#tp1_enabled_multi').is(':checked');
            if (tp1Enabled) {
                const tp1RR = parseFloat($('#tp1_rr').val() || $('#tp1_rr_multi').val());
                if (!tp1RR || tp1RR <= 0) {
                    isValid = false;
                    $('#tp1_rr, #tp1_rr_multi').addClass('is-invalid');
                }
            }
        }

        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 500);
            
            // Show alert
            @include('backend.layout.ajax_alert', [
                'message' => '',
                'message_error' => 'Please fix the validation errors before submitting.',
            ])
        }
    });

    // Initialize validation on page load
    $('input[type="number"], input[type="text"], select').each(function() {
        if ($(this).val()) {
            validateField(this);
        }
    });
});
</script>

