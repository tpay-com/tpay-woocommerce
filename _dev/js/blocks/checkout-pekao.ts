let settings = wc.wcSettings.getPaymentMethodData('pekaoinstallments', {});
const react = window.React;

const {registerPaymentMethod} = wc.wcBlocksRegistry;
const {useEffect} = wp.element;

const Content = (props: { eventRegistration: any; emitResponse: any; }) => {
    const {eventRegistration, emitResponse} = props;
    const {onPaymentSetup} = eventRegistration;
    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            const paymentMethodIdInput: HTMLInputElement = document.querySelector('input[name="tpay-channel-id"]:checked');
            const paymentMethodId = paymentMethodIdInput ? paymentMethodIdInput.value : false;

            if (paymentMethodId === false) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: settings.channelNotSelectedMessage,
                };
            }

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {'tpay-channel-id': paymentMethodId}
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
    name: 'pekaoinstallments',
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
