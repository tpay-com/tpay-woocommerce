<div class="tpay-blik0">
    <?php
    $blikform = '';
    $blikform_checked = false;
    if($alias): ?>
    <div class="tpay_blik-payment-alias active">
        <div class="top">
            <label>
                <input type="radio" name="blik-type" value="alias" checked="checked" />
                <?php esc_html_e('I pay with BLIK from saved account', 'tpay') ?>
            </label>
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../img/tpay-small.svg') ?>"/>
        </div>
        <div class="bottom">
            <a href="#" class="show-blik-info"><?php esc_html_e('Why don\'t I have to enter a code?', 'tpay'); ?></a>
        </div>
    </div>
    <?php else:
        $blikform = 'active';
        $blikform_checked = true;
        ?>
    <?php endif ?>
    <div class="tpay_blik-payment-form <?php echo esc_attr($blikform) ?>">
        <div class="top">
            <label>
                <input type="radio" name="blik-type" value="code" <?php if($blikform_checked) echo 'checked="checked"' ?> />
                <?php esc_html_e('Enter BLIK code', 'tpay') ?>
            </label>
            <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . '../img/tpay-small.svg') ?>"/>
        </div>
        <div class="bottom">
            <input type="text" name="blik0" required minlength="7" maxlength="7" />
        </div>
    </div>
    <?php echo esc_html($agreements) ?>
</div>
<div class="modal-tpay-blik-container">
    <div class="modal-tpay-blik">
        <p><?php esc_html_e('You do not need to enter the BLIK code, because you linked your account to this device during one of the previous payments. The payment still requires confirmation in the app.', 'tpay') ?></p>
        <a class="close" href="#"><?php esc_html_e('I understand', 'tpay') ?></a>
    </div>
</div>
