jQuery(document).ready(function ($) {
    $.ajax({
        url: tpayThankYou.url, method: 'POST', data: {
            action: 'tpay_blik0_transaction_status',
            transactionId: tpayThankYou.transactionId,
            nonce: tpayThankYou.nonce
        }, success: function (data) {
            switch (data.status) {
                case 'correct':
                    $('#tpay_pending').css('display', 'none');
                    $('#tpay_success').css('visibility', 'visible');
                    break;
                case 'pending':
                    if (data.payments.attempts.pop().paymentErrorCode !== null) {
                        $('#tpay_pending').css('display', 'none');
                        $('#tpay_error').css('visibility', 'visible');
                    } else {
                        setTimeout($.ajax(this), 1500);
                    }
                    break;
            }
        }, error: function (error) {
            $('#tpay_pending').css('display', 'none');
            $('#tpay_error').css('visibility', 'visible');
        }
    })
})
