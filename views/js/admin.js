(function($){
    $(document).ready(function(){
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
        if ($('input[data-toggle-global]').prop('checked') == true) {
            $('input[data-global="can-be-global"]').each(function () {
                $(this).attr('readonly', true);
                var global_value = $(this).attr('global-value');
                $('<span>' + global_value + '</span>').insertAfter($(this));
            });
        } else {
            $('input[data-global="can-be-global"]').each(function () {
                $(this).attr('readonly', false);
                $(this).next('span').remove();
            });
        }
    }


})(jQuery)