<?php

$renderType = tpayOption('global_render_payment_type');
/** @var \Tpay\Dtos\Channel[] $list */
$list = $this->channels();
$availableGateways = WC()->payment_gateways()->get_available_payment_gateways();

if ($availableGateways) {
    foreach ($availableGateways as $available_gateway => $data) {
        if (($available_gateway === TPAYGPAY_ID) && $data->enabled === 'yes') {
            $this->unset_banks[] = TPAYGPAY;
        }

        if (($available_gateway === TPAYBLIK_ID) && $data->enabled === 'yes') {
            $this->unset_banks[] = TPAYBLIK;
        }

        if ((in_array($available_gateway, [TPAYCC_ID, TPAYSF_ID])) && $data->enabled === 'yes') {
            $this->unset_banks[] = TPAYSF;
        }

        if (($available_gateway === TPAYTWISTO_ID) && $data->enabled === 'yes') {
            $this->unset_banks[] = TPAYTWISTO;
        }

        if (($available_gateway === TPAYINSTALLMENTS_ID) && $data->enabled === 'yes') {
            $this->unset_banks[] = TPAYINSTALLMENTS;
        }

        if (($available_gateway === TPAYPEKAOINSTALLMENTS_ID) && $data->enabled === 'yes') {
            $this->unset_banks[] = TPAYPEKAOINSTALLMENTS;
        }
    }
}

$list = $this->filter_out_constraints($list);
$customOrderString = get_option('woocommerce_tpaypbl_settings')['custom_order'];

if ($customOrderString) {
    $orderedList = [];
    $customOrder = explode(',', $customOrderString);

    foreach ($customOrder as $orderNumber) {
        foreach ($list as $key => $item) {
            if ($item->id == $orderNumber) {
                $orderedList[] = $item;
                unset($list[$key]);
            }
        }
    }

    $list = array_merge($orderedList, $list);
}

$list = array_filter($list, function (\Tpay\Dtos\Channel $channel) {
    foreach ($channel->groups as $group) {
        if (in_array($group->id, $this->unset_banks)) {
            return false;
        }
    }

    return true;
});
?>
<div id="tpay-payment" class="tpay-pbl-container">
    <ul class="pbl-error woocommerce-error" role="alert">
        <li><?php esc_html_e('Choose payment method.', 'tpay') ?></li>
    </ul>
    <div class="tpay-pbl">
        <?php if ($renderType == 'list'): ?>
            <select class="tpay-item" name="tpay-channel-id" style="width: 100%">
                <?php foreach ($list as $key => $item): ?>
                    <?php if (!in_array($item->id, $this->unset_banks)): ?>
                        <option <?php if ($key === 0) echo 'selected'; ?> value="<?php echo esc_attr($item->id) ?>"><?php echo esc_html($item->name) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <?php
        foreach ($list as $item): ?>
            <label class="tpay-item" data-groupID="<?php echo esc_attr($item->id) ?>">
                <input class="tpay-item" name="tpay-channel-id" type="radio" value="<?php echo esc_attr($item->id) ?>"/>
                <div>
                    <div>
                        <div class="tpay-group-logo-holder">
                            <img src="<?php echo esc_url($item->image->url) ?>"
                                 class="tpay-group-logo"
                                 alt="<?php echo esc_attr($item->name) ?>">
                        </div>
                        <span class="name"><?php echo esc_html($item->fullName) ?></span>
                    </div>
                </div>
            </label>
        <?php
            endforeach; ?><?php endif; ?>
    </div>
    <?php echo $agreements ?>
</div>
