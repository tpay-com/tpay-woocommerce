let settings = window.wc.wcSettings.getPaymentMethodData('tpaygeneric', {});
const react = window.React;

const {registerPaymentMethod} = wc.wcBlocksRegistry;
const {useEffect} = wp.element;

Object.entries(settings).forEach((channelSetting) => {
    let setting = channelSetting[1]
    let Content = (props) => {
        const {eventRegistration, emitResponse} = props;
        const {onPaymentSetup} = eventRegistration;
        useEffect(() => {
            const unsubscribe = onPaymentSetup(async () => {
                const data = {};

                data['tpay-channel'] = channelSetting[0];

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
        return react.createElement('div', {dangerouslySetInnerHTML: {__html: setting.fields}});
    };

    let Block_Gateway = {
        name: `tpaygeneric-${channelSetting[0]}`,
        label: react.createElement('label', null, `${setting.title} `, react.createElement('img', {src: setting.icon})),
        content: react.createElement(Content),
        edit: null,
        canMakePayment: () => true,
        ariaLabel: setting.title ?? '',
    };

    registerPaymentMethod(Block_Gateway);
});
