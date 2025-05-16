<div class="tpay-blik0">
    <?php
    $blikform = '';
    $blikform_checked = false;
    if($alias): ?>
    <div class="tpay_blik-payment-alias active">
        <div class="top">
            <label>
                <input type="radio" name="blik-type" value="alias" checked="checked" />
                <span class="blik-label">
                    <?php esc_html_e('I pay with BLIK from saved account', 'tpay') ?>
                </span>
                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../img/tpay-small.svg') ?>"/>
            </label>
        </div>
        <div class="bottom">
            <span class="show-blik-info">
                <?php esc_html_e('Why don\'t I have to enter a code?', 'tpay'); ?>
                <span class="tooltip-text"> <?php esc_html_e('You do not need to enter the BLIK code, because you linked your account to this device during one of the previous payments. The payment still requires confirmation in the app.', 'tpay') ?> </span>
            </span>
        </div>
    </div>
    <?php else:
        $blikform = 'active';
        $blikform_checked = true;
        ?>
    <?php endif ?>
    <ul class="blik0-error woocommerce-error" role="alert">
        <li><?php esc_html_e('Enter Blik code', 'tpay') ?></li>
    </ul>
    <div class="tpay_blik-payment-form <?php echo esc_attr($blikform) ?>">
        <div class="top">
            <?php if(!$alias): ?>
                <span class="spacer">&nbsp;</span>
            <?php endif; ?>
            <label>
                <?php if($alias): ?>
                    <input type="radio" name="blik-type" value="code" <?php if($blikform_checked) echo 'checked="checked"' ?> />
                <?php endif; ?>
                <span class="blik-label"><?php esc_html_e('Pay with BLIK code', 'tpay') ?></span>
                <?php if(!$alias): ?>
                    <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../img/tpay-small.svg') ?>"/>
                <?php endif; ?>
            </label>
        </div>
        <div class="bottom">
            <div><?php esc_html_e('Enter Blik code', 'tpay') ?></div>
            <input
                    name="blik0"
                    id="blik0-code"
                    required
                    type="text"
                    maxlength="7"
                    placeholder="000 000"
                    pattern="\d*"
            />
        </div>
    </div>
    <?php echo $agreements ?>
</div>

