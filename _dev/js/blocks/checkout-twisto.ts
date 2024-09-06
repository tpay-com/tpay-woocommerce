let settings = window.wc.wcSettings.getPaymentMethodData( 'tpaytwisto', {} );
let react = window.React;

let Block_Gateway = {
    name: 'tpaytwisto',
    label: react.createElement('label', null, `${settings.title} `, react.createElement('img', {src: settings.icon})),
    content: react.createElement('div', {dangerouslySetInnerHTML: {__html: settings.fields}}),
    edit: null,
    canMakePayment: () => true,
    ariaLabel: settings.title ?? '',
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);


