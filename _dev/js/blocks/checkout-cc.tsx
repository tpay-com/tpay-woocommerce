import {getSetting} from '@woocommerce/settings';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';

let settings = getSetting('tpaycc_data');

let Label = () => {
    return (
        <>
            <label>
                {settings.title} <img src={settings.icon}/>
            </label>
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


