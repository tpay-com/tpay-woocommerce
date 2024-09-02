import {JSEncrypt} from "jsencrypt";
import {getSetting} from '@woocommerce/settings';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useEffect} from '@wordpress/element';
import {getCreditCardNameByNumber} from 'creditcard.js'

let settings = getSetting('tpaysf_data');

type CardData = {
    'saved-card'?: number
    carddata?: string
}

function hashAsync(algo: AlgorithmIdentifier, str: string) {
    return crypto.subtle.digest(algo, new TextEncoder("utf-8").encode(str)).then(buf => {
        return Array.prototype.map.call(new Uint8Array(buf), (x: number) => (('00' + x.toString(16)).slice(-2))).join('');
    });
}

function tokenize_card(pubkey: string) {
    var numberInput: HTMLInputElement = document.querySelector('#card_number'),
        expiryInput: HTMLInputElement = document.querySelector('#expiry_date'),
        cvcInput: HTMLInputElement = document.querySelector('#cvc');
    var cardNumber = numberInput.value.replace(/\s/g, ''),
        cd = cardNumber + '|' + expiryInput.value.replace(/\s/g, '') + '|' + cvcInput.value.replace(/\s/g, '') + '|' + document.location.origin,
        encrypt = new JSEncrypt(),
        decoded = atob(pubkey),
        encrypted;
    encrypt.setPublicKey(decoded);
    encrypted = encrypt.encrypt(cd);
    document.querySelector('#carddata').value = encrypted;
    document.querySelector('#card_vendor').value = getCreditCardNameByNumber(cardNumber);
    document.querySelector('#card_short_code').value = cardNumber.substr(-4);
    hashAsync("SHA-256", cardNumber).then(outputHash => document.querySelector('#card_hash').value = outputHash);
    numberInput.value = '';
    expiryInput.value = '';
    cvcInput.value = '';
}

const Content = (props) => {
    const {eventRegistration, emitResponse} = props;
    const {onPaymentSetup} = eventRegistration;
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            const data: CardData = {};
            const savedCard: HTMLInputElement = document.querySelector('input[name="saved-card"]:checked');

            if (savedCard) {
                data['saved-card'] = Number(savedCard.value);
            } else {
                const saveCard: HTMLInputElement = document.querySelector('input[name="save-card"]:checked')
                tokenize_card(document.querySelector('.tpay-sf').getAttribute('data-pubkey'))

                data.carddata = document.querySelector('#carddata').value;
                data.card_vendor = document.querySelector('#card_vendor').value;
                data.card_hash = document.querySelector('#card_hash').value;
                data.card_short_code = document.querySelector('#card_short_code').value;

                if (saveCard) {
                    data['save-card'] = saveCard.value;
                }
            }

            if (data.carddata || data['saved-card']) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: data,
                    },
                };
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                message: 'Payment method ID does not exists',
            };
        });
        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
    ]);

    return (
        <>
            <div dangerouslySetInnerHTML={{__html: settings.fields}}></div>
        </>
    )
};

let Label = () => {
    return (
        <>
            <label>
                {settings.title} <img src={settings.icon}/>
            </label>
        </>
    )
};

let Block_Gateway = {
    name: 'tpaysf',
    label: <Label/>,
    content: <Content/>,
    edit: null,
    canMakePayment: () => true,
    ariaLabel: settings.title ?? '',
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(Block_Gateway);


