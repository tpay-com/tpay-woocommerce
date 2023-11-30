<?php
if ($this->valid_mid) {
    $sf_rsa = get_option('woocommerce_tpaysf_settings')['sf_rsa' . $this->valid_mid];
} else {
    $sf_rsa = get_option('woocommerce_tpaysf_settings')['sf_rsa'];
}
?>
<div class="tpay-sf" data-pubkey="<?php echo $sf_rsa ?>">
    <div class="tpay-sf-form">
        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../img/tpay-small.svg') ?>"/>
        <div class="card-container">
            <div class="card-number-container">
                <label class="card-number"><?php esc_html_e('Card number', 'tpay'); ?>
                    <input id="card_number" type="text" name="card-number" pattern="\d*" autocompletetype="cc-number" size="30"
                           type="tel" autocomplete="off" maxlength="23" placeholder="0000 0000 0000 0000" tabindex="1" value=""  style="" class="soft-wrong" />
                </label>
            </div>
            <div class="date-container">
                <label class="card-expiry"><?php esc_html_e('Expiry date', 'tpay') ?>
                    <input id="expiry_date" type="text" name="card-expiry" placeholder="00 / 0000" autocomplete="off"
                           autocompletetype="cc-exp" tabindex="2" value="" style="" class="soft-wrong" />
                </label>
            </div>
            <div class="cvc-container">
                <label class="card-cvc"><?php esc_html_e('CVC', 'tpay') ?>
                    <input id="cvc" type="text" name="card-cvc" placeholder="000" autocomplete="off" autocompletetype="cc-cvc"
                           tabindex="3" value=""  style="" class="soft-wrong" />
                </label>
            </div>
        </div>
        <?php if(get_current_user_id() && WC()->cart->cart_contents_total >= 1): ?>
        <label class="save-card">
            <input type="checkbox" value="save" name="save-card" /> <?php esc_html_e('Save card', 'tpay') ?>
        </label>
        <?php endif ?>
        <?php if($cards): ?>
            <div class="saved-cards">
                <p class="saved-cards-title"><?php esc_html_e('Pay by saved card', 'tpay') ?></p>
                <?php foreach($cards as $key => $card): ?>
                    <label>
                        <input type="checkbox" name="saved-card-unchecked" value="<?php echo esc_attr($key) ?>" /> <?php echo esc_html(strtoupper($card['vendor']).' * * * '.$card['short_code']) ?>
                    </label>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <input type="hidden" name="carddata" id="carddata" value=""/>
        <input type="hidden" name="card_hash" id="card_hash" value=""/>
        <input type="hidden" name="card_vendor" id="card_vendor" value=""/>
        <input type="hidden" name="card_short_code" id="card_short_code" value=""/>
    </div>
    <?php echo $agreements ?>
</div>
