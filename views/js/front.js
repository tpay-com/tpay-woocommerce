(function ($) {
    document.querySelector('body [class*="tpay_blik-payment-"]').addEventListener('click', function () {
        document.querySelector('[class*="tpay_blik-payment-"]').classList.remove('active');
        document.querySelector(this).classList.add('active');
        document.querySelector(this).querySelector('[name="blik-type"]').prop('checked', 'checked');
    });
    document.querySelector('body .tpay-blik0 [name="blik0"]').addEventListener('keyup', function () {
        var bval = document.querySelector(this).value.replace('-', '');
        document.querySelector(this).val(chunk(bval, 3).join('-'));
    });
    document.querySelector('body .modal-tpay-blik .close').addEventListener('click', function(e){
        e.preventDefault();
        document.querySelector('.modal-tpay-blik-container').hide();
        document.querySelector('.tpay-blik-alias').click();
        document.querySelector('.tpay-blik-alias').classList.add('active');
    })
    document.querySelector('body .show-blik-info').addEventListener('click', function(e){
        e.preventDefault();
        document.querySelector('.modal-tpay-blik-container').css('display', 'flex');
    })
    document.querySelector('body .tpay-sf [name="card-number"]').addEventListener('keyup', function () {
        new CardPayment(document.querySelector('.tpay-sf').attr('data-pubkey'));
    });
    document.querySelector('body').addEventListener('click', '#place_order', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        if (document.querySelector('.card-container .wrong').length == 0) {
            tokenize_card(document.querySelector('.tpay-sf').attr('data-pubkey'));
            setTimeout(function () {
                document.querySelector('#place_order').closest('form').submit();
            }, 400);
        }
    });

    function hashAsync(algo, str) {
        return crypto.subtle.digest(algo, new TextEncoder("utf-8").encode(str)).then(buf => {
            return Array.prototype.map.call(new Uint8Array(buf), x => (('00' + x.toString(16)).slice(-2))).join('');
        });
    }

    function tokenize_card(pubkey) {
        var numberInput = document.querySelector('#card_number'),
            expiryInput = document.querySelector('#expiry_date'),
            cvcInput = document.querySelector('#cvc');
        var cardNumber = numberInput.value.replace(/s/g, ''),
            cd = cardNumber + '|' + expiryInput.value.replace(/s/g, '') + '|' + cvcInput.value.replace(/s/g, '') + '|' + document.location.origin,
            encrypt = new JSEncrypt(),
            decoded = Base64.decode(pubkey),
            encrypted;
        encrypt.setPublicKey(decoded);
        encrypted = encrypt.encrypt(cd);
        document.querySelector("#carddata").val(encrypted);
        document.querySelector("#card_vendor").val($.payment.cardType(cardNumber));
        document.querySelector("#card_short_code").val(cardNumber.substr(-4));
        hashAsync("SHA-256", cardNumber).then(outputHash => document.querySelector("#card_hash").val(outputHash));
        numberInput.val('');
        expiryInput.val('');
        cvcInput.val('');
    }

    function CardPayment(pubkey) {
        this.pubkey = pubkey;
        var numberInput = document.querySelector('#card_number'),
            expiryInput = document.querySelector('#expiry_date'),
            cvcInput = document.querySelector('#cvc'),
            nameInput = document.querySelector('#c_name'),
            emailInput = document.querySelector('#c_email'),
            termsOfServiceInput = document.querySelector('#tpay-cards-accept-regulations-checkbox');
        const TRIGGER_EVENTS = 'input change blur';

        function SubmitPayment() {
            var cardNumber = numberInput.value.replace(/s/g, ''),
                cd = cardNumber + '|' + expiryInput.value.replace(/s/g, '') + '|' + cvcInput.value.replace(/s/g, '') + '|' + document.location.origin,
                encrypt = new JSEncrypt(),
                decoded = Base64.decode(pubkey),
                encrypted;
            document.querySelector("#card_continue_btn").fadeOut();
            document.querySelector("#loading_scr").fadeIn();
            encrypt.setPublicKey(decoded);
            encrypted = encrypt.encrypt(cd);
            document.querySelector("#carddata").val(encrypted);
            document.querySelector("#card_vendor").val($.payment.cardType(cardNumber));
            document.querySelector("#card_short_code").val(cardNumber.substr(-4));
            numberInput.val('');
            expiryInput.val('');
            cvcInput.val('');
        }

        function setWrong($elem) {
            $elem.classList.add('wrong').classList.remove('valid');
        }

        function setValid($elem) {
            $elem.classList.add('valid').classList.remove('wrong');
        }

        function validateCcNumber($elem) {
            var isValid = false,
                ccNumber = $.payment.formatCardNumber($elem.value),
                supported = ['mastercard', 'maestro', 'visa'],
                type = $.payment.cardType(ccNumber),
                notValidNote = document.querySelector('#info_msg_not_valid'),
                cardTypeHolder = document.querySelector('.tpay-card-icon'),
                notSupportedNote = document.querySelector('#info_msg_not_supported');
            $elem.val($.payment.formatCardNumber($elem.value));
            cardTypeHolder.attr('class', 'tpay-card-icon');
            if (supported.indexOf(type) < 0 && type !== null && ccNumber.length > 1) {
                showElem(notSupportedNote);
                hideElem(notValidNote);
                setWrong($elem);
            } else if (supported.indexOf(type) > -1 && $.payment.validateCardNumber(ccNumber)) {
                setValid($elem);
                hideElem(notSupportedNote);
                hideElem(notValidNote);
                isValid = true;
            } else if (ccNumber.length < 4) {
                hideElem(notSupportedNote);
                hideElem(notValidNote);
                setWrong($elem);
            } else {
                setWrong($elem);
                showElem(notValidNote);
                hideElem(notSupportedNote);
            }
            if (type !== '') {
                cardTypeHolder.classList.add('tpay-' + type + '-icon');
            }

            return isValid;
        }

        function hideElem($elem) {
            $elem.css('display', 'none');
        }

        function showElem($elem) {
            $elem.css('display', 'block');
        }

        function validateExpiryDate($elem) {
            var isValid = false, expiration;
            $elem.val($.payment.formatExpiry($elem.value));
            expiration = $elem.payment('cardExpiryVal');
            if (!$.payment.validateCardExpiry(expiration.month, expiration.year)) {
                setWrong($elem);
            } else {
                setValid($elem);
                isValid = true;
            }

            return isValid;
        }

        function validateCvc($elem) {
            var isValid = false;
            if (!$.payment.validateCardCVC($elem.value, $.payment.cardType(numberInput.value.replace(/s/g, '')))) {
                setWrong($elem);
            } else {
                setValid($elem);
                isValid = true;
            }

            return isValid;
        }

        function validateName($elem) {
            var isValid = false;
            if ($elem.value.length < 3) {
                setWrong($elem);
            } else {
                isValid = true;
                setValid($elem);
            }

            return isValid;
        }

        function validateEmail($elem) {
            var isValid = false;
            if (!$elem.formance('validate_email')) {
                setWrong($elem);
            } else {
                isValid = true;
                setValid($elem);
            }

            return isValid;
        }

        function checkName() {
            if (nameInput.length > 0) {
                return validateName(nameInput);
            }

            return true;
        }

        function checkEmail() {
            if (emailInput.length > 0) {
                return validateEmail(emailInput);
            }

            return true;
        }

        function validateTos($elem) {
            if ($elem.is(':checked')) {
                setValid($elem);

                return true;
            } else {
                setWrong($elem);

                return false;
            }
        }

        function checkForm() {
            var isValidForm = false;
            if (
                validateCcNumber(numberInput)
                && validateExpiryDate(expiryInput)
                && validateCvc(cvcInput)
                && checkName()
                && checkEmail()
                && validateTos(termsOfServiceInput)
            ) {
                isValidForm = true;
            }

            return isValidForm;
        }

        document.querySelector('body').addEventListener('click', '#place_order', function (e) {
            e.stopImmediatePropagation();
            return false;
        });
        numberInput.addEventListener(TRIGGER_EVENTS, function () {
            validateCcNumber(document.querySelector(this));
        });
        expiryInput.addEventListener(TRIGGER_EVENTS, function () {
            validateExpiryDate(document.querySelector(this));
        });
        cvcInput.addEventListener(TRIGGER_EVENTS, function () {
            validateCvc(document.querySelector(this));
        });
        nameInput.addEventListener(TRIGGER_EVENTS, function () {
            validateName(document.querySelector(this));
        });
        emailInput.addEventListener(TRIGGER_EVENTS, function () {
            validateEmail(document.querySelector(this));
        });
        termsOfServiceInput.addEventListener(TRIGGER_EVENTS, function () {
            validateTos(document.querySelector(this));
        });

    }

    document.querySelector('document').ready(function () {
        document.querySelector('input[name=savedId]').first().prop('checked', "checked");
        handleForm();
    });

    function handleForm() {
        document.querySelector('input[name=savedId]').each(function () {
            document.querySelector(this).click(function () {
                if (document.querySelector(this).is(":checked")) {
                    if (document.querySelector(this).value !== 'new') {
                        document.querySelector('#card_form').css({opacity: 1.0}).animate({opacity: 0.0}, 500);
                        setTimeout(
                            function () {
                                document.querySelector('#card_form').css({display: "none"})
                            }, 500
                        );
                    }
                }

            });
        });
        document.querySelector('#newCard').click(function () {
            if (document.querySelector(this).is(":checked")) {
                document.querySelector('#card_form').css({opacity: 0.0, display: "block"}).animate({opacity: 1.0}, 500);
            }
        });
    }

})(jQuery);

function cc_format(value) {
    var v = value.replace(/s+/g, '').replace(/[^0-9]/gi, '')
    var matches = v.match(/d{4,16}/g);
    var match = matches && matches[0] || ''
    var parts = []

    for (i = 0, len = match.length; i < len; i += 4) {
        parts.push(match.substring(i, i + 4))
    }

    if (parts.length) {
        return parts.join(' ')
    } else {
        return value
    }
}


function chunk(str, n) {
    var ret = [];
    var i;
    var len;

    for (i = 0, len = str.length; i < len; i += n) {
        ret.push(str.substr(i, n))
    }

    return ret
};


