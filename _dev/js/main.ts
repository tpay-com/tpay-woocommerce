(function ($) {
    $(document).ready(function () {
        var $form = $('form.checkout');

        $(document.body).on('change', 'input[name="payment_method"]', function () {
            $('body').trigger('update_checkout');
        });

        $('form').on('submit', function (e) {
            var paymentMethod = $(this).find('input[name="payment_method"]:checked').val();
            var validateResult = true;

            switch (paymentMethod) {
                case 'tpaypbl': {
                    validateResult = validateTpayPbl();
                    break;
                }
                case 'tpayblik': {
                    validateResult = validateTpayBlik();
                    break;
                }
                default: {
                    validateResult = true;
                    break;
                }
            }

            if (!validateResult) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });

        function validateTpayPbl() {
            if (!$('input.tpay-item:checked, select.tpay-item').length && $('input.tpay-item, select.tpay-item').length !== 0) {
                $('html, body').animate({
                    scrollTop: $('.tpay-pbl-container').offset().top - 180
                }, 300);
                $('.pbl-error').slideDown(250);

                return false;
            } else {
                $('.pbl-error').slideUp(250);

                return true;
            }
        }

        function validateTpayBlik() {
            const blikInput = $('input[name=blik0]');

            if (blikInput.length) {
                let x = blikInput[0].value;
                let match = /[0-9]{6}/.exec(x);

                if (match === null) {
                    $('html, body').animate({
                        scrollTop: $('.tpay_blik-payment-form').offset().top - 180
                    }, 300);
                    $('.blik0-error').slideDown(250);
                }

                return match !== null;
            }

            return true;
        }


        $('body').on('click', 'li.wc_payment_method > label:not([for="payment_method_tpaysf"])', function () {
            $("#carddata").val('');
            $("#card_vendor").val($.payment.cardType(''));
            $("#card_short_code").val('');
            $('#card_number').val('');
            $('#expiry_date').val('');
            $('#cvc').val('');
            $('#place_order').removeClass('stop_propagation');
            $('.card-container .wrong').removeClass('wrong');
        });
        $('body').on('click', '.saved-cards input', function () {
            if ($(this).prop('checked') === true) {
                $('.saved-cards input').not($(this)).prop('checked', false);
                $('.saved-cards input').not($(this)).attr('name', 'saved-card-unchecked');
                $(this).attr('name', 'saved-card');
                clear_card_fields();
                $('.card-container').find('input').attr('readonly', true);
            } else {
                $(this).attr('name', 'saved-card-unchecked');
                $('.card-container').find('input').attr('readonly', false);
            }

        });

        function clear_card_fields() {
            $("#carddata").val('');
            $("#card_vendor").val($.payment.cardType(''));
            $("#card_short_code").val('');
            $('#card_number').val('');
            $('#expiry_date').val('');
            $('#cvc').val('');
            $('.card-container .wrong').removeClass('wrong');
        }

        // $('body').on('click', '.saved-cards > label', function(){
        //     if($(this).find('input').hasClass('active-sc')){
        //         $(this).find('input').prop('checked', false);
        //         $(this).find('input').removeClass('active-sc');
        //         $("#card_short_code").attr('readonly', false);
        //         $('#card_number').attr('readonly', false);
        //         $('#expiry_date').attr('readonly', false);
        //     }
        //     else{
        //         $(this).find('input').prop('checked', true);
        //         $(this).find('input').addClass('active-sc');
        //         $("#card_short_code").attr('readonly', true);
        //         $('#card_number').attr('readonly', true);
        //         $('#expiry_date').attr('readonly', true);
        //     }
        //     // var th = $(this);
        //     // $('.saved-cards > label').each(function(){
        //     //     if($(this) != th){
        //     //         $(this).find('input').removeClass('active-sc');
        //     //     }
        //     // })
        // })

        $('body').on('click', '[class*="tpay_blik-payment-"]', function () {
            $('[class*="tpay_blik-payment-"]').removeClass('active');
            $(this).addClass('active');
            $(this).find('[name="blik-type"]').prop('checked', 'checked');
        });
        $('body').on('click', '.modal-tpay-blik .close', function (e) {
            e.preventDefault();
            $('.modal-tpay-blik-container').hide();
            $('.tpay-blik-alias').click();
            $('.tpay-blik-alias').addClass('active');
        })
        $('body').on('click', '.show-blik-info', function (e) {
            e.preventDefault();
            $('.modal-tpay-blik-container').css('display', 'flex');
        })
        $('body').on('keyup', '.tpay-sf [name="card-number"]', function () {
            $('.saved-cards').find('input[type="radio"]').prop('checked', false);
            new CardPayment($('.tpay-sf').attr('data-pubkey'));
        });
        $('body').on('click', '#place_order', function (e) {
            if ($('.tpay-sf').attr('data-pubkey') != '' && $('.tpay-sf').length > 0 && $('#place_order').hasClass('stop_propagation')) {
                e.preventDefault();
                e.stopImmediatePropagation();
                console.log('sf');
                if ($('.card-container .wrong').length == 0) {
                    tokenize_card($('.tpay-sf').attr('data-pubkey'));
                    setTimeout(function () {
                        $('#place_order').closest('form').submit();
                    }, 400);
                }
            }
        });
    });

    function hashAsync(algo, str) {
        return crypto.subtle.digest(algo, new TextEncoder("utf-8").encode(str)).then(buf => {
            return Array.prototype.map.call(new Uint8Array(buf), x => (('00' + x.toString(16)).slice(-2))).join('');
        });
    }

    function tokenize_card(pubkey) {
        var numberInput = $('#card_number'),
            expiryInput = $('#expiry_date'),
            cvcInput = $('#cvc');
        var cardNumber = numberInput.val().replace(/\s/g, ''),
            cd = cardNumber + '|' + expiryInput.val().replace(/\s/g, '') + '|' + cvcInput.val().replace(/\s/g, '') + '|' + document.location.origin,
            encrypt = new JSEncrypt(),
            decoded = Base64.decode(pubkey),
            encrypted;
        encrypt.setPublicKey(decoded);
        encrypted = encrypt.encrypt(cd);
        $("#carddata").val(encrypted);
        $("#card_vendor").val($.payment.cardType(cardNumber));
        $("#card_short_code").val(cardNumber.substr(-4));
        hashAsync("SHA-256", cardNumber).then(outputHash => $("#card_hash").val(outputHash));
        numberInput.val('');
        expiryInput.val('');
        cvcInput.val('');
    }

    function CardPayment(pubkey) {
        this.pubkey = pubkey;
        var numberInput = $('#card_number'),
            expiryInput = $('#expiry_date'),
            cvcInput = $('#cvc'),
            nameInput = $('#c_name'),
            emailInput = $('#c_email'),
            termsOfServiceInput = $('#tpay-cards-accept-regulations-checkbox');
        const TRIGGER_EVENTS = 'input change blur';

        function SubmitPayment() {
            var cardNumber = numberInput.val().replace(/\s/g, ''),
                cd = cardNumber + '|' + expiryInput.val().replace(/\s/g, '') + '|' + cvcInput.val().replace(/\s/g, '') + '|' + document.location.origin,
                encrypt = new JSEncrypt(),
                decoded = Base64.decode(pubkey),
                encrypted;
            $("#card_continue_btn").fadeOut();
            $("#loading_scr").fadeIn();
            encrypt.setPublicKey(decoded);
            encrypted = encrypt.encrypt(cd);
            $("#carddata").val(encrypted);
            $("#card_vendor").val($.payment.cardType(cardNumber));
            $("#card_short_code").val(cardNumber.substr(-4));
            numberInput.val('');
            expiryInput.val('');
            cvcInput.val('');
        }

        function setWrong($elem) {
            $elem.addClass('wrong').removeClass('valid');
        }

        function setValid($elem) {
            $elem.addClass('valid').removeClass('wrong');
        }

        function validateCcNumber($elem) {
            var isValid = false,
                ccNumber = $.payment.formatCardNumber($elem.val()),
                supported = ['mastercard', 'maestro', 'visa'],
                type = $.payment.cardType(ccNumber),
                notValidNote = $('#info_msg_not_valid'),
                cardTypeHolder = $('.tpay-card-icon'),
                notSupportedNote = $('#info_msg_not_supported');
            $elem.val($.payment.formatCardNumber($elem.val()));
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
                cardTypeHolder.addClass('tpay-' + type + '-icon');
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
            $elem.val($.payment.formatExpiry($elem.val()));
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
            if (!$.payment.validateCardCVC($elem.val(), $.payment.cardType(numberInput.val().replace(/\s/g, '')))) {
                setWrong($elem);
            } else {
                setValid($elem);
                isValid = true;
            }

            return isValid;
        }

        function validateName($elem) {
            var isValid = false;
            if ($elem.val().length < 3) {
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

        $('#place_order').addClass('stop_propagation');
        // $('body').on('click', '#place_order', function (e) {
        //     e.stopImmediatePropagation();
        //     return false;
        // });
        numberInput.on(TRIGGER_EVENTS, function () {
            validateCcNumber($(this));
        });
        expiryInput.on(TRIGGER_EVENTS, function () {
            validateExpiryDate($(this));
        });
        cvcInput.on(TRIGGER_EVENTS, function () {
            validateCvc($(this));
        });
        nameInput.on(TRIGGER_EVENTS, function () {
            validateName($(this));
        });
        emailInput.on(TRIGGER_EVENTS, function () {
            validateEmail($(this));
        });
        termsOfServiceInput.on(TRIGGER_EVENTS, function () {
            validateTos($(this));
        });

    }

    $('document').ready(function () {
        $('input[name=savedId]').first().prop('checked', "checked");
        handleForm();
    });

    function handleForm() {
        $('input[name=savedId]').each(function () {
            $(this).click(function () {
                if ($(this).is(":checked")) {
                    if ($(this).val() !== 'new') {
                        $('#card_form').css({opacity: 1.0}).animate({opacity: 0.0}, 500);
                        setTimeout(
                            function () {
                                $('#card_form').css({display: "none"})
                            }, 500
                        );
                    }
                }

            });
        });
        $('#newCard').click(function () {
            if ($(this).is(":checked")) {
                $('#card_form').css({opacity: 0.0, display: "block"}).animate({opacity: 1.0}, 500);
            }
        });
    }

})(jQuery);

function cc_format(value) {
    var v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '')
    var matches = v.match(/\d{4,16}/g);
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


