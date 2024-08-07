jQuery(document).ready(function ($) {
    const checkTransaction = $.ajax({
        url: tpayThankYou.url,
        method: 'POST',
        data: {action: 'tpay_blik0_transaction_status', transactionId: tpayThankYou.transactionId, nonce: tpayThankYou.nonce},
        success: function (data) {
            if (data.status === 'correct') {
                $('#tpay_pending').hide();
                $('#tpay_success').show();
            }

            if (data.status === 'pending') {
                setTimeout(checkTransaction(), 1500);
            }
        },
        error: function (error) {
            $('#tpay_pending').hide();
            $('#tpay_error').show();
        }
    })
})
