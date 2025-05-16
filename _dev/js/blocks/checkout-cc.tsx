import {getSetting} from '@woocommerce/settings';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';

let settings = getSetting('tpaycc_data');

let Label = () => {
    return (
        <>
            <span>
                {settings.title} <img class="tpay-inline" src={settings.icon}/>
            </span>
        </>
    );
};

let Content = () => {
    return (
        <>
            <div dangerouslySetInnerHTML={{__html: settings.fields}}></div>
        </>
    )
};

let Block_Gateway = {
    name: 'tpaycc',
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


