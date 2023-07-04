<?php
    if($this->valid_mid != false){
        $sf_rsa = get_option('woocommerce_tpaysf_settings')['sf_rsa' . $this->valid_mid];
    }
    else{
        $sf_rsa = get_option('woocommerce_tpaysf_settings')['sf_rsa'];
    }
?>
<div class="tpay-sf" data-pubkey="<?php echo $sf_rsa ?>">
    <div class="tpay-sf-form">
        <img src="<?php echo plugin_dir_url(__FILE__) ?>../img/tpay-small.svg"/>
        <div class="card-container">
            <div class="card-number-container">
                <label class="card-number"><?php _e('Card number', 'tpay'); ?>
                    <input id="card_number" type="text" name="card-number" pattern="\d*" autocompletetype="cc-number" size="30"
                           type="tel" autocomplete="off" maxlength="23" placeholder="0000 0000 0000 0000" tabindex="1" value=""  style="" class="soft-wrong" />
                </label>
            </div>
            <div class="date-container">
                <label class="card-expiry"><?php _e('Expiry date', 'tpay') ?>
                    <input id="expiry_date" type="text" name="card-expiry" placeholder="00 / 0000" autocomplete="off"
                           autocompletetype="cc-exp" tabindex="2" value="" style="" class="soft-wrong" />
                </label>
            </div>
            <div class="cvc-container">
                <label class="card-cvc"><?php _e('CVC', 'tpay') ?>
                    <input id="cvc" type="text" name="card-cvc" placeholder="000" autocomplete="off" autocompletetype="cc-cvc"
                           tabindex="3" value=""  style="" class="soft-wrong" />
                </label>
            </div>
        </div>
        <?php if(get_current_user_id() && WC()->cart->cart_contents_total >= 1): ?>
        <label class="save-card">
            <input type="checkbox" value="save" name="save-card" /> <?php _e('Save card', 'tpay') ?>
        </label>
        <?php endif ?>
        <?php if($cards): ?>
            <div class="saved-cards">
                <p class="saved-cards-title"><?php _e('Pay by saved card', 'tpay') ?></p>
                <?php foreach($cards as $key => $card): ?>
                    <label>
                        <input type="checkbox" name="saved-card-unchecked" value="<?php echo $key ?>" /> <?php echo strtoupper($card['vendor']).' * * * '.$card['short_code'] ?>
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