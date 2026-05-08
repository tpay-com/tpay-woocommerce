<?php
if ($this->valid_mid) {
    $sf_rsa = get_option('woocommerce_tpaysf_settings')['sf_rsa' . $this->valid_mid];
} else {
    $sf_rsa = get_option('woocommerce_tpaysf_settings')['sf_rsa'];
}
?>
<?php if ($sf_rsa): ?>
<div class="tpay-sf" data-pubkey="<?php echo $sf_rsa ?>">
    <div class="tpay-sf-form">
        <div class="card-container">
            <?php if($cards): ?>
                <div class="saved-cards">
                    <div class="separator"></div>
                    <?php foreach($cards as $key => $card): ?>
                        <label class="saved-card-label">
                            <input type="radio" name="saved-card-unchecked" value="<?php echo esc_attr($key) ?>" />
                            <img src="<?php echo esc_url( plugin_dir_url(__FILE__) . '../img/' . esc_attr( strtolower($card['vendor']) ) . '-card-icon.svg' ); ?>" />
                            <span><?php echo esc_html(strtoupper(' * * * '.$card['short_code'])) ?></span>
                        </label>
                        <div class="separator"></div>
                    <?php endforeach ?>
                    <label class="another-card-label">
                        <input type="radio" name="another-card" value="another-card" /> <?php echo esc_html_e('Pay with another card', 'tpay') ?>
                    </label>
                </div>
            <?php endif ?>
            <div id="another-card-form" style="<?php echo empty($cards) ? 'display:block;' : 'display:none;'; ?>">
                <div class="card-description-container">
                    <p class="card-label">
                        <?php esc_html_e('Enter your payment card details below', 'tpay') ?>
                    </p>
                    <?php if(!$cards): ?>
                    <div class="separator"></div>
                    <?php endif ?>
                </div>
                <div class="new-card-container">
                    <div class="card-number-container">
                        <label class="card-number"><?php esc_html_e('Card number', 'tpay'); ?>
                            <input
                                id="card_number"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9 ]*"
                                autocomplete="cc-number"
                                maxlength="23"
                                placeholder="0000 0000 0000 0000"
                                oninput="this.value = this.value.replace(/[^0-9 ]/g, '')"
                            />
                        </label>
                    </div>
                    <div class="card-row">
                        <div class="date-container">
                            <label class="card-expiry"><?php esc_html_e('Expiry date', 'tpay') ?>
                                <input
                                    id="expiry_date"
                                    type="text"
                                    placeholder="00/00"
                                    maxlength="5"
                                    inputmode="numeric"
                                    oninput="
                                        let v = this.value.replace(/\D/g, '').slice(0,4);
                                        if (v.length >= 3) {
                                          v = v.slice(0,2) + '/' + v.slice(2);
                                        }
                                        this.value = v;
                                    "
                                />
                            </label>
                        </div>
                        <div class="cvc-container">
                            <label class="card-cvc"><?php esc_html_e('CVV2/CVC2', 'tpay') ?>
                                <input id="cvc" type="text" placeholder="000" autocomplete="off" autocompletetype="cc-cvc"
                                       tabindex="3" value=""  style="" class="soft-wrong" />
                            </label>
                            <span class="show-info">
                                <img src="<?php echo esc_html_e(plugin_dir_url(__FILE__) . '../img/info-icon.svg') ?>"/>
                                <span class="tooltip-text"> <?php esc_html_e('The CVV2/CVC2 code is a 3-digit number located on the back of Mastercard and Visa cards.', 'tpay') ?> </span>
                            </span>
                        </div>
                    </div>
                </div>
                <label class="save-card">
                    <input type="checkbox" value="save" name="save-card" /> <?php esc_html_e('Save card', 'tpay') ?>
                </label>
                <?php if(get_current_user_id()): ?>
                    <div class="saved-card-notice"><?php esc_html_e('In order to use saved card on future orders You will have to be logged in.', 'tpay') ?></div>
                <?php endif ?>
            </div>
        </div>
        <input type="hidden" name="carddata" id="carddata" value=""/>
        <input type="hidden" name="card_hash" id="card_hash" value=""/>
        <input type="hidden" name="card_vendor" id="card_vendor" value=""/>
        <input type="hidden" name="card_short_code" id="card_short_code" value=""/>
    </div>
    <div class="separator"></div>
    <?php echo $agreements ?>
    <div class="powered-by-tpay">
        <p><?php esc_html_e('Powered by', 'tpay') ?></p>
        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../img/tpay-small.svg') ?>"/>
    </div>
</div>
<?php endif ?>
