<div class="payment-section">
    <div class="title-wrapper">
        <h3 class="page-title">
            <?php esc_html_e('Payment with Tpay', 'tpay') ?>:</h3>
        <div class="page-title-line"></div>
    </div>
    <div class="payments-container">
        <!--        BLIK-->
        <div class="blik_payment">
            <input
                    class="payment-input"
                    type="radio"
                    name="payment"
                    value="blik"
                    id="blik-radio"
                    checked
            >
            <div class="payment-option payment-option-blik">
                <label class="payment-label" for="blik-radio">
                    <span class="radio-mark"></span>
                    <span class="payment-title">BLIK</span>
                    <img
                            src="<?php echo plugin_dir_url(__FILE__) . '../img/blik.png'; ?>"
                            alt="Logo Blik"
                            style="width: 50px; height: auto;"
                    />
                    <img
                            src="<?php echo plugin_dir_url(__FILE__) . '../img/check.svg'; ?>"
                            alt="Check ico"
                            class="check-ico"
                    />
                </label>
                <div class="blik-code-section" style="display: none">
                    <label for="blik-code" class="blik-code-label"><?php esc_html_e('Enter BLIK code', 'tpay') ?></label>
                    <input type="hidden" id="transaction_counter" value="1">
                    <input
                            type="text"
                            id="blik-code"
                            class="blik-input"
                            maxlength="7"
                            placeholder="000 000"
                            pattern="\d*"
                    />
                    <p class="error-message">
                        <?php esc_html_e('Payment error, please try again.', 'tpay') ?>
                    </p>
                    <p class="info-text">
                        <?php
                        $regulationUrl = "https://tpay.com/user/assets/files_for_download/payment-terms-and-conditions.pdf";
                        $clauseUrl = "https://tpay.com/user/assets/files_for_download/information-clause-payer.pdf";
                        $locale = get_locale();
                        if (strpos($locale, 'pl') !== false) {
                            $regulationUrl = "https://tpay.com/user/assets/files_for_download/regulamin.pdf";
                            $clauseUrl = "https://tpay.com/user/assets/files_for_download/klauzula-informacyjna-platnik.pdf";
                        }
                        ?>

                        <?php esc_html_e('Paying, you accept the', 'tpay') ?> <a
                                href="<?php echo esc_url($regulationUrl); ?>"
                                target="_blank"><?php esc_html_e('terms and conditions.', 'tpay') ?></a> <?php esc_html_e('The administrator of the personal data is Krajowy Integrator Płatności spółka akcyjna, based in Poznań.', 'tpay') ?>
                        <a
                                href="<?php echo esc_url($clauseUrl); ?>"
                                target="_blank"><?php esc_html_e('Read the full content.', 'tpay') ?></a>
                    </p>
                </div>
                <div class="blik-waiting">
                    <img
                            src="<?php echo plugin_dir_url(__FILE__) . '../img/device-mobile-check.svg'; ?>"
                            alt="Ikona"/>
                    <?php esc_html_e("Confirm the payment in your bank's mobile app.", 'tpay') ?>
                </div>
            </div>
        </div>

        <p class="blik-master-error">
            <?php esc_html_e('BLIK payment error, try paying online.', 'tpay') ?>
        </p>

        <!--        Bank transfer-->
        <div class="transfer_payment" style="display: none">
            <input
                    class="payment-input"
                    type="radio"
                    name="payment"
                    value="bank_transfer"
                    id="bank-transfer-radio"
                    disabled
            >
            <div class="payment-option">
                <label class="payment-label" for="bank-transfer-radio">
                    <span class="radio-mark"></span>
                    <span class="payment-title"><?php esc_html_e('Online payment', 'tpay') ?></span>
                    <img
                            src="<?php echo plugin_dir_url(__FILE__) . '../img/tpay--small.svg'; ?>"
                            alt="Logo Tpay"
                            style="width: 50px; height: auto;"
                    />
                    <img
                            src="<?php echo plugin_dir_url(__FILE__) . '../img/check.svg'; ?>"
                            alt="Check ico"
                            class="check-ico"
                    />
                </label>
            </div>
        </div>
    </div>
    <button class="btn blue pay-button" id="payment-button" disabled>
        <span class="spinner"><img src="<?php echo plugin_dir_url(__FILE__) . '../img/spinner.svg'; ?>"/></span>
        <span class="label"><?php esc_html_e('Pay for your purchase with Tpay!', 'tpay') ?></span>
    </button>
    <div class="section-divider"></div>
</div>
<div class="payment-confirmation-container success">
    <div class="icon-wrapper">
        <img
                src="<?php echo plugin_dir_url(__FILE__) . '../img/success.svg'; ?>"
                alt="Icon"/>
    </div>
    <div class="message">
        <p class="title"><?php esc_html_e('Payment completed successfully!', 'tpay') ?></p>
        <p class="subtitle"><?php esc_html_e('Thank you for using Tpay.', 'tpay') ?></p>
    </div>
    <div class="underline"></div>
</div>
