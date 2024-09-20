import {getSetting} from '@woocommerce/settings';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useEffect} from '@wordpress/element';

let settings = getSetting('tpaypbl_data');

let Content = (props) => {
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
    name: 'tpaypbl',
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
