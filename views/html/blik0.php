<div class="tpay-blik0">
    <?php
    $blikform = '';
    $blikform_checked = false;
    if($alias): ?>
    <div class="tpay_blik-payment-alias active">
        <div class="separator blik-code"></div>
        <div class="top">
            <label>
                <input type="radio" name="blik-type" value="alias" checked="checked" />
                <span class="blik-label">
                    <?php esc_html_e('I pay with BLIK from saved account', 'tpay') ?>
                </span>
            </label>
        </div>
        <div class="bottom">
            <span class="show-blik-info">
                <?php esc_html_e('Why don\'t I have to enter a code?', 'tpay'); ?>
                <span class="tooltip-text"> <?php esc_html_e('You do not need to enter the BLIK code, because you linked your account to this device during one of the previous payments. The payment still requires confirmation in the app.', 'tpay') ?> </span>
            </span>
        </div>
        <div class="separator"></div>
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
        <?php if(!$alias): ?>
            <div class="separator blik-code"></div>
        <?php endif; ?>
        <?php if($alias): ?>
            <div class="top">
                <label>
                    <input type="radio" name="blik-type" value="code" <?php if($blikform_checked) echo 'checked="checked"' ?> />
                    <span class="blik-label">
                        <?php esc_html_e('Pay with BLIK code', 'tpay') ?>
                    </span>
                </label>
            </div>
        <?php endif; ?>
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
    <div class="separator"></div>
    <?php echo $agreements ?>
</div>

