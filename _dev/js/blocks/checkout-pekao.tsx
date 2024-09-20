import {getSetting} from '@woocommerce/settings';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useEffect} from '@wordpress/element';

let settings = getSetting('pekaoinstallments_data');

const Content = (props) => {
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
                {settings.title} <img src={settings.icon} />
            </label>
        </>
    )
};

let Block_Gateway = {
    name: 'pekaoinstallments',
    label: <Label />,
    content: <Content />,
    edit: null,
    canMakePayment: () => true,
    ariaLabel: settings.title ?? '',
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(Block_Gateway);
