let settings = window.wc.wcSettings.getPaymentMethodData( 'tpayblik', {} );
let label = window.wp.htmlEntities.decodeEntities( settings.title || 'tpayblik' ) || window.wp.i18n.__( 'Tpay12', 'tpay' );
const react = window.React;

const { decodeEntities } = wp.htmlEntities;
const { getSetting } = wc.wcSettings;
const { registerPaymentMethod } = wc.wcBlocksRegistry;
const { useEffect } = wp.element;

const Content = (props: { eventRegistration: any; emitResponse: any; }) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;
    useEffect( () => {
        const unsubscribe = onPaymentSetup( async () => {
            const blikInput: HTMLInputElement = document.querySelector('input[name="blik0"]');
            const blikCode = blikInput ? blikInput.value : false;

            if ( blikCode ) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            'blik0': blikCode,
                            'blik-type': 'code'
                        },
                    },
                };
            }

            return {
                type: emitResponse.responseTypes.ERROR,
                message: 'BLIK Code does not exists',
            };
        } );
        return () => {
            unsubscribe();
        };
    }, [
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
        onPaymentSetup,
    ] );

    return react.createElement('div', {dangerouslySetInnerHTML: {__html: settings.fields}});
};

const TpayBlikOptions = {
    name: 'tpayblik',
    label: react.createElement('label', null, settings.title, react.createElement('img', {src: settings.icon})),
    content: react.createElement(Content),
    edit: null,
    canMakePayment: () => true,
    ariaLabel: settings.title
};

registerPaymentMethod(TpayBlikOptions);
