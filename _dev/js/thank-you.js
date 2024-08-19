jQuery(document).ready(function ($) {
    function checkOrder() {
        $.ajax({
            url: tpayThankYou.url, method: 'POST', data: {
                action: 'tpay_blik0_transaction_status',
                transactionId: tpayThankYou.transactionId,
                nonce: tpayThankYou.nonce
            }, success: function (data) {
                let {attempts} = data.payments;

                switch (data.status) {
                    case 'correct':
                        $('#tpay_pending').css({display: 'none'});
                        $('#tpay_success').css({display: 'flex'});
                        break;
                    case 'pending':
                        if (attempts.length !== 0 && attempts.pop().paymentErrorCode) {
                            $('#tpay_pending').css({display: 'none'});
                            $('#tpay_error').css({display: 'flex'});
                            $('#tpay_email-sent-message').show();
                        } else {
                            setTimeout(checkOrder, 1500);
                        }
                        break;
                }
            }, error: function (error) {
                $('#tpay_pending').css({display: 'none'});
                $('#tpay_error').css({display: 'flex'});
                $('#tpay_email-sent-message').show();
            }
        })
    }

    checkOrder()
})
