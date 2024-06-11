let settings = window.wc.wcSettings.getPaymentMethodData( 'tpaycc', {} );
const react = window.React;

const { decodeEntities } = wp.htmlEntities;
const { registerPaymentMethod } = wc.wcBlocksRegistry;

let Block_Gateway = {
    name: 'tpaycc',
    label: react.createElement('label', null, `${settings.title} `, react.createElement('img', {src: settings.icon})),
    content: react.createElement('div', {dangerouslySetInnerHTML: {__html: settings.fields}}),
    edit: null,
    canMakePayment: () => true,
    ariaLabel: settings.title ?? '',
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(Block_Gateway);


