let settings = window.wc.wcSettings.getPaymentMethodData('tpayblik', {});
let label = window.wp.htmlEntities.decodeEntities(settings.title || 'tpayblik') || window.wp.i18n.__('Tpay', 'tpay');
const react = window.React;

const {decodeEntities} = wp.htmlEntities;
const {getSetting} = wc.wcSettings;
const {registerPaymentMethod} = wc.wcBlocksRegistry;
const {useEffect} = wp.element;

function validateBlikZero(code: string): boolean {
    let match: RegExpExecArray | null = /[0-9]{6}/.exec(code);

    return !!match;
}

const Content = (props: { eventRegistration: any; emitResponse: any; }) => {
    const {eventRegistration, emitResponse} = props;
    const {onPaymentSetup} = eventRegistration;

    function blikError(): { type: string, message: string } {
        return {
            type: emitResponse.responseTypes.ERROR,
            message: settings.blikCodeNotTyped,
        };
    }

    useEffect(() => {
        return onPaymentSetup(() => {
            let data = {};
            const blikInput: HTMLInputElement = document.querySelector('input[name="blik0"]');
            const blikCode = blikInput ? blikInput.value : false;

            if (settings.blikZero && !blikCode) {
                return blikError();
            }

            if (settings.blikZero && blikCode) {
                if (!validateBlikZero(blikCode)) {
                    return blikError();
                }

                data = {
                    'blik0': blikCode,
                    'blik-type': 'code'

                }
            }

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: data
                },
            }
        })
    }, [onPaymentSetup]);

    return react.createElement('div', {dangerouslySetInnerHTML: {__html: settings.fields}});
};

const TpayBlikOptions = {
    name: 'tpayblik',
    label: react.createElement('label', null, `${settings.title} `, react.createElement('img', {src: settings.icon})),
    content: react.createElement(Content),
    edit: null,
    canMakePayment: () => true,
    ariaLabel: settings.title ?? ''
};

registerPaymentMethod(TpayBlikOptions);
