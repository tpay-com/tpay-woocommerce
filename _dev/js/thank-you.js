import $ from "jquery";

$(document).ready(function () {
    const blikSection = document.querySelector('.payment-option-blik');
    const paymentButton = document.getElementById('payment-button');
    const blikCodeInput = document.getElementById('blik-code');
    const paymentsInputs = document.getElementsByName('payment');

    if (parseInt(parseInt(localStorage.getItem('tpay_transaction_counter'))) === 3) {
        document.querySelector('.payment-section').style.display = 'block';
        document.querySelector('.transfer_payment').style.display = 'block';
        document.querySelector('.pay-button').style.display = 'block';
        document.querySelector('.blik_payment').style.display = 'none';
        setFormState(false, true);
    } else {
        checkOrder()
        setFormState(false);
    }

    function checkOrder() {
        let paymentData = {
            action: 'tpay_blik0_transaction_status',
            transactionId: tpayThankYou.transactionId,
            nonce: tpayThankYou.nonce
        };

        const data = (new URLSearchParams(paymentData)).toString();

        fetch(tpayThankYou.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data,
        })
            .then(response => {
                return response.json().then(data => {
                    document.querySelector('.blik-waiting').style.display = 'none';

                    if (data.status === 'correct') {
                        document.querySelector('.payment-section').style.display = 'none';
                        document.querySelector('.payment-confirmation-container').style.display = 'block';
                    } else {
                        document.querySelector('.payment-section').style.display = 'block';
                        document.querySelector('.transfer_payment').style.display = 'block';
                        document.querySelector('.pay-button').style.display = 'block';
                        document.querySelector('.blik-code-section').style.display = 'block';
                        blikSection.classList.add('with-error');
                        blikSection.classList.remove('loading');
                        setFormState(false, true);
                    }
                });
            })
            .catch(function (e) {
                blikSection.classList.add('with-error');
                blikSection.classList.remove('loading');
                setFormState(false);
            })
    }

    function setFormState(isLoading, forceDisabled) {
        if (isLoading) {
            paymentButton.classList.add('loading');
        } else {
            paymentButton.classList.remove('loading');
        }
        if (forceDisabled) {
            paymentButton.disabled = true;
        } else {
            paymentButton.disabled = isLoading;
        }
        paymentsInputs.forEach(function (input) {
            input.disabled = isLoading;
        });
    }

    function changePayButtonState() {
        paymentButton.disabled = isBlik() && getCleanBlikCode().length !== 6;
    }

    function onPaymentInputClick() {
        changePayButtonState();
    }

    function onBlikCodeKeyUp() {
        changePayButtonState();
        const valueAsArray = getCleanBlikCode().split('');
        if (valueAsArray.length > 3) {
            valueAsArray.splice(3, 0, ' ');
        }
        blikCodeInput.value = valueAsArray.join('');
    }

    function getCleanBlikCode() {
        return (blikCodeInput.value || '').replaceAll(/[^0-9]/g, '').substring(0,6);
    }

    function isBlik() {
        return getSelectedPayment() === 'blik';
    }

    function getSelectedPayment() {
        const elements = document.getElementsByName('payment');
        for (let i = 0, l = elements.length; i < l; i++) {
            if (elements[i].checked) {
                return elements[i].value;
            }
        }

        return null;
    }

    function pay() {
        if (isBlik()) {
            payBlik();
        } else {
            payTransfer();
        }
    }

    function payBlik() {
        blikSection.classList.add('loading');
        blikSection.classList.remove('with-error');
        document.querySelector('.blik-waiting').style.display = 'block';

        setFormState(true);

        var paymentData = {
            action: 'tpay_blik0_repay',
            transactionId: tpayThankYou.transactionId,
            blikCode: getCleanBlikCode(),
            transactionCounter: parseInt(localStorage.getItem('tpay_transaction_counter')),
            nonce: tpayThankYou.nonce
        };

        const data = (new URLSearchParams(paymentData)).toString();
        localStorage.setItem('tpay_transaction_counter', parseInt(localStorage.getItem('tpay_transaction_counter')) + 1);

        fetch(tpayThankYou.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data,
        })
            .then(response => {
                return response.json().then(data => {
                    document.querySelector('.blik-waiting').style.display = 'none';

                    if (data.result === 'correct') {
                        document.querySelector('.payment-section').style.display = 'none';
                        document.querySelector('.payment-confirmation-container').style.display = 'block';
                    } else {
                        document.querySelector('.transfer_payment').style.display = 'block';
                        document.querySelector('.pay-button').style.display = 'block';

                        if (parseInt(parseInt(localStorage.getItem('tpay_transaction_counter'))) === 3) {
                            document.querySelector('.blik_payment').style.display = 'none';
                            document.querySelector('.blik-master-error').style.display = 'block';
                            setFormState(false, true);
                        } else {
                            document.querySelector('.payment-section').style.display = 'block';
                            document.querySelector('.blik-code-section').style.display = 'block';
                            blikSection.classList.add('with-error');
                            blikSection.classList.remove('loading');
                            setFormState(false);
                        }
                    }
                });
            })
            .catch(function (e) {
                document.querySelector('.transfer_payment').style.display = 'block';
                document.querySelector('.pay-button').style.display = 'block';

                if (parseInt(parseInt(localStorage.getItem('tpay_transaction_counter'))) === 3) {
                    document.querySelector('.blik_payment').style.display = 'none';
                    document.querySelector('.blik-master-error').style.display = 'block';
                } else {
                    document.querySelector('.payment-section').style.display = 'block';
                    document.querySelector('.blik-code-section').style.display = 'block';
                    blikSection.classList.add('with-error');
                    blikSection.classList.remove('loading');
                    setFormState(false);
                }
            })
    }

    function payTransfer() {
        setFormState(true);

        var paymentData = {
            action: 'tpay_pay_by_transfer',
            transactionId: tpayThankYou.transactionId,
            orderId: tpayThankYou.orderId,
            nonce: tpayThankYou.nonce
        };

        const data = (new URLSearchParams(paymentData)).toString();

        fetch(tpayThankYou.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data,
        })
            .then(response => {
                return response.json().then(data => {
                    if (data.status === 'correct') {
                        window.top.location.href = data.payment_url;
                    } else {
                        setFormState(false, true);
                    }
                });
            })
            .catch(function (e) {
                setFormState(false);
            });
    }

    paymentButton.addEventListener('click', pay);
    blikCodeInput.addEventListener('input', onBlikCodeKeyUp);
    paymentsInputs.forEach(function (input) {
        input.addEventListener('click', onPaymentInputClick)
    });
})
