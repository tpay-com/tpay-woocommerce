import $ from "jquery";

$(document).ready(function ($) {
    function addInstallmentsIframe(url) {
        let iframe = document.createElement('iframe');
        iframe.classList.add('installments-iframe');
        iframe.src = url;
        let iframeContainer = document.createElement('div');
        iframeContainer.classList.add('installments-iframe-container');
        iframeContainer.appendChild(iframe);
        document.body.appendChild(iframeContainer);
        window.addEventListener('message', function (event) {
            if (event.data.action === 'closeModal') {
                iframeContainer.remove()
            }
        });
    }

    function createInstallmentsButton() {
        let price = parseFloat($('.woocommerce-Price-amount bdi').contents()[0].data.replace(/,/g, '.'));
        let quantity = parseInt($('input[name="quantity"]').val());
        let amount = quantity * price;

        if (amount >= 100 && amount <= 20000) {
            createInstallmentContainer(amount);
        }
    }

    function createInstallmentContainer(amount) {
        let url = `https://secure.tpay.com/Installment/Pekao/page?merchantId=${tpayProduct.merchantId}&amount=${amount}`;
        let addToCartButton = document.querySelector('.single_add_to_cart_button');

        if (document.querySelector('.installments-button')) {
            document.querySelector('.installments-button').addEventListener('click', () => addInstallmentsIframe(url));
        }

        if (addToCartButton && !document.querySelector('.installments-button')) {
            let installmentsButton = document.createElement('button');
            installmentsButton.classList.add('btn', 'installments-button', 'product');
            installmentsButton.type = 'button';
            const img = document.createElement("img");
            img.src = "data:image/png;base64,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";
            img.className = "icon";
            installmentsButton.appendChild(img);
            const text = document.createTextNode(tpayProduct.translations.button);
            installmentsButton.appendChild(text);
            installmentsButton.addEventListener('click', () => addInstallmentsIframe(url));
            let productQuantityContainer = document.querySelector('.quantity');
            if (productQuantityContainer) {
                productQuantityContainer.parentNode.insertBefore(installmentsButton, productQuantityContainer.nextSibling);
            }
        }
    }

    createInstallmentsButton();

    document.querySelector('input[name="quantity"]').addEventListener('input', function () {
        createInstallmentsButton();
    });
});
