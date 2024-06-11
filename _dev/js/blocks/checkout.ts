let settings = window.wc.wcSettings.getPaymentMethodData('tpaypbl', {});
const react = window.React;

const {registerPaymentMethod} = wc.wcBlocksRegistry;
const {useEffect} = wp.element;

const Content = (props: { eventRegistration: any; emitResponse: any; }) => {
    const {eventRegistration, emitResponse} = props;
    const {onPaymentSetup} = eventRegistration;
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            const data = {};
            const paymentMethodIdInput: HTMLInputElement = document.querySelector('input[name="tpay-channel-id"]:checked') ?? document.querySelector('select[name="tpay-channel-id"] option:checked');
            const paymentMethodId = paymentMethodIdInput ? paymentMethodIdInput.value : false;

            if (paymentMethodId === false && settings.tpayDirect === false) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: settings.channelNotSelectedMessage ?? 'Select a bank',
                };
            }

            if (paymentMethodId) {
                data['tpay-channel-id'] = paymentMethodId;

            }

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: data
                },
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
    name: 'tpaypbl',
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
