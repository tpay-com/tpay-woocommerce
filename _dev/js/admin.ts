(function($){
    $(document).ready(function(){
        //email test
        if($('#global_merchant_email').length > 0){
            // $('#global_merchant_email').attr('type', 'email');
            // $('#global_merchant_email').attr('required', 'required');
        }
        //fee
        if($('input[value="tpay_settings_option_group"]').length > 0){
            $('.form-table tbody tr').each(function(){
                if($(this).find('#global_amount_fee').length > 0 || $(this).find('#global_percentage_fee').length > 0){
                    $(this).addClass($(this).find('input').attr('id') + '-container');
                    $(this).hide();
                }
                if($('#global_enable_fee').val() != 'disabled'){
                    $('.form-table tbody tr.global_' + $('#global_enable_fee').val() + '_fee-container').show();
                }
            });
            $('#global_enable_fee').on('change', function(){
                $('.global_amount_fee-container, .global_percentage_fee-container').hide();
                if($(this).val() != 'disabled'){
                    $('.form-table tbody tr.global_' + $(this).val() + '_fee-container').show();
                }
            })
        }
        //global values
        if ($('.form-table input[data-global="can-be-global"]').length) {
            $('.form-table').addClass('tpay-table');
            $('.form-table .forminp').each(function () {
                if ($(this).find('input[data-global="can-be-global"]').length) {
                    $(this).addClass('absolute-global');
                }
            });
            test_values_type();
        }
        $('input[data-toggle-global]').on('click', function () {
            test_values_type();
        });
    });



    function test_values_type() {
        if($('.mid-field').length > 0) {
            $('.mid-field').each(function () {
                $(this).closest('tr').hide();
            });
        }
        if ($('input[data-toggle-global]').prop('checked') == true) {
            $('input[data-global="can-be-global"]').each(function () {
                $(this).attr('readonly', true);
                var global_value = $(this).attr('global-value');
                $('<span>' + global_value + '</span>').insertAfter($(this));
                try_set_fields_by_mid();

            });
        } else {
            $('input[data-global="can-be-global"]').each(function () {
                $(this).attr('readonly', false);
                $(this).next('span').remove();
                try_set_fields_by_mid();
            });
        }
    }
    function try_set_fields_by_mid(){
        if($('.mid-field').length > 0) {
            var is_global = $('input[data-toggle-global]').prop('checked');
            $('.mid-field').each(function(){
                $(this).closest('tr').hide();
            })
            if(is_global){
                $('#woocommerce_tpaysf_security_code').closest('tr').show();
                $('#woocommerce_tpaysf_api_key').closest('tr').show();
                $('#woocommerce_tpaysf_api_key_password').closest('tr').show();
                $('#woocommerce_tpaysf_sf_rsa').closest('tr').show();
                $('.mid-selector').closest('tr').hide();
            }
            else{
                $('#woocommerce_tpaysf_security_code').closest('tr').hide();
                $('#woocommerce_tpaysf_api_key').closest('tr').hide();
                $('#woocommerce_tpaysf_api_key_password').closest('tr').hide();
                $('#woocommerce_tpaysf_sf_rsa').closest('tr').hide();
            }
        }
    }


})(jQuery)
