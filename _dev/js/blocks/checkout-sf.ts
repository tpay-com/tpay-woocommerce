let settings = window.wc.wcSettings.getPaymentMethodData('tpaysf', {});
const react = window.React;

const {registerPaymentMethod} = wc.wcBlocksRegistry;
const {useEffect} = wp.element;
const creditCardType = require("credit-card-type");


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
        decoded = Base64.decode(pubkey),
        encrypted;
    encrypt.setPublicKey(decoded);
    encrypted = encrypt.encrypt(cd);
    document.querySelector('#carddata').value = encrypted;
    document.querySelector('#card_vendor').value = creditCardType(cardNumber)[0].niceType;
    document.querySelector('#card_short_code').value = cardNumber.substr(-4);
    hashAsync("SHA-256", cardNumber).then(outputHash => document.querySelector('#card_hash').value = outputHash);
    numberInput.value = '';
    expiryInput.value = '';
    cvcInput.value = '';
}

const Content = (props: { eventRegistration: any; emitResponse: any; }) => {
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
    return react.createElement('div', {dangerouslySetInnerHTML: {__html: settings.fields}});
};

let Block_Gateway = {
    name: 'tpaysf',
    label: react.createElement('label', null, `${settings.title} `, react.createElement('img', {src: settings.icon})),
    content: react.createElement(Content),
    edit: null,
    canMakePayment: () => true,
    ariaLabel: settings.title ?? '',
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(Block_Gateway);


