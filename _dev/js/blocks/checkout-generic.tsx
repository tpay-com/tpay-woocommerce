import {getSetting} from '@woocommerce/settings';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useEffect} from '@wordpress/element';

let settings = getSetting('tpaygeneric_data');

function checkConstraints(constraints, total) {
    let check = false;

    Object.entries(constraints).forEach((constraint) => {
        switch (constraint[1].type) {
            case 'min':
                if (Number(total) < Number(constraint[1].value)) {
                    check = true;
                }

                break;
            case 'max':
                if (Number(total) > Number(constraint[1].value)) {
                    check = true;
                }

                break;
        }
    });

    return check;
}

Object.entries(settings).forEach((channelSetting) => {
    let setting = channelSetting[1]

    if (checkConstraints(setting.constraints, setting.total)) {
        return;
    }

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

        return (
            <>
                <div dangerouslySetInnerHTML={{__html: setting.fields}}></div>
            </>
        )
    };

    let Label = () => {
        return (
            <>
                <label>
                    {setting.title} <img src={setting.icon} />
                </label>
            </>
        )
    };

    let Block_Gateway = {
        name: `tpaygeneric-${channelSetting[0]}`,
        label: <Label/>,
        content: <Content/>,
        edit: null,
        canMakePayment: () => true,
        ariaLabel: setting.title ?? '',
    };

    registerPaymentMethod(Block_Gateway);
});
