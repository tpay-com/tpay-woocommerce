<script>
    jQuery(document).ready(function () {
        if (!window.ApplePaySession || !window.ApplePaySession.canMakePayments()) {
            jQuery('.payment_method_tpayapplepay').remove();
        }
    })
</script>