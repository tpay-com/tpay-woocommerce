import {getSetting} from '@woocommerce/settings';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {useEffect} from '@wordpress/element';
import {decodeEntities} from '@wordpress/html-entities';


let settings = getSetting('tpayblik_data');

function validateBlikZero(code: string): boolean {
    let match: RegExpExecArray | null = /[0-9]{6}/.exec(code);

    return !!match;
}

function getCleanBlikCode(blikCode) {
    return (blikCode || '').replace(/[^0-9]/g, '');
}

const Content = (props) => {
    const {eventRegistration, emitResponse} = props;
    const {onPaymentSetup} = eventRegistration;

    function blikError(): { type: string, message: string } {
        return {
            type: emitResponse.responseTypes.ERROR,
            message: settings.blikCodeNotTyped,
        };
    }

    useEffect(() => {
        return onPaymentSetup(() => {
            let data = {};
            const blikInput: HTMLInputElement = document.querySelector('input[name="blik0"]');
            const blikCode = blikInput ? blikInput.value : false;
            const aliasInput = document.querySelector('input[name="blik-type"][value="alias"]') as HTMLInputElement;
            localStorage.setItem('tpay_transaction_counter', '1');

            if (aliasInput && aliasInput.checked) {
                data = {
                    'blik-type': 'alias'
                }

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: data
                    },
                }
            }

            if (settings.blikZero && !blikCode) {
                return blikError();
            }
            if (settings.blikZero && blikCode !== false) {
                const validateCode = getCleanBlikCode(blikCode);

                if (!validateBlikZero(getCleanBlikCode(validateCode))) {
                    return blikError();
                }

                data = {
                    'blik0': validateCode,
                    'blik-type': 'code'

                }
            }

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: data
                },
            }
        })
    }, [onPaymentSetup]);

    return (
        <>
            <div dangerouslySetInnerHTML={{__html: settings.fields}}></div>
        </>
    )
};

let Label = () => {
    return (
        <>
            <span>
                {decodeEntities(settings.title || 'tpayblik')} <img class="tpay-inline" src={settings.icon} />
            </span>
        </>
    )
};

const TpayBlikOptions = {
    name: 'tpayblik',
    label: <Label />,
    content: <Content />,
    edit: null,
    canMakePayment: () => true,
    ariaLabel: settings.title ?? ''
};

registerPaymentMethod(TpayBlikOptions);
