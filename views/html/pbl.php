<?php
$renderType = @get_option('tpay_settings_option_name')['global_render_payment_type'];
$list = $this->getBanksList(false);
$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
$tpay_gateways_list = \Tpay\TpayGateways::gateways_list();
if ($available_gateways) {
    foreach ($available_gateways as $available_gateway => $data) {
        if (($available_gateway === TPAYGPAY_ID) && $data->enabled === 'yes') {
            array_push($this->unset_banks, TPAYGPAY);
        }
        if (($available_gateway === TPAYBLIK_ID) && $data->enabled === 'yes') {
            array_push($this->unset_banks, TPAYBLIK);
        }
        if ((in_array($available_gateway, [TPAYCC_ID, TPAYSF_ID])) && $data->enabled === 'yes') {
            array_push($this->unset_banks, TPAYSF);
        }
        if (($available_gateway === TPAYTWISTO_ID) && $data->enabled === 'yes') {
            array_push($this->unset_banks, TPAYTWISTO);
        }
        if (($available_gateway === TPAYINSTALLMENTS_ID) && $data->enabled === 'yes') {
            array_push($this->unset_banks, TPAYINSTALLMENTS);
        }
    }
    if($this->try_disable_gateway_by_cart_total(TPAYINSTALLMENTS_ID)){
        array_push($this->unset_banks, TPAYINSTALLMENTS);
    }
    if($this->try_disable_gateway_by_cart_total(TPAYTWISTO_ID)){
        array_push($this->unset_banks, TPAYTWISTO);
    }
    if($this->try_disable_gateway_by_cart_total(TPAYPEKAOINSTALLMENTS_ID)){
        array_push($this->unset_banks, TPAYPEKAOINSTALLMENTS);
    }
}
if($cr = get_option('woocommerce_tpaypbl_settings')['custom_order']){
    $new_list = [];
    $cr = explode(',', $cr);
    foreach($list as $key => $item){
        if(in_array($item['id'], $cr)) {
            array_push($new_list, $item);
            unset($list[$key]);
        }
    }

    $list = $new_list + $list;
}
?>
<div id="tpay-payment" class="tpay-pbl-container">
    <div class="tpay-pbl">
        <?php if ($renderType == 'list'): ?>
            <select class="tpay-item" name="tpay-groupID">
                <?php foreach ($list as $item): ?>
                    <?php if (!in_array($item['id'], $this->unset_banks)): ?>
                        <option value="<?php echo $item['id'] ?>"><?php echo $item['name'] ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <?php foreach ($list as $item): ?>
                <?php if (!in_array($item['id'], $this->unset_banks)): ?>
                    <label class="tpay-item" data-groupID="<?php echo $item['id'] ?>">
                        <input name="tpay-groupID" type="radio" value="<?php echo $item['id'] ?>"/>
                        <div>
                            <div>
                                <div class="tpay-group-logo-holder">
                                    <img src="<?php echo $item['img'] ?>" class="tpay-group-logo"
                                         alt="<?php echo $item['name'] ?>">
                                </div>
                                <span class="name"><?php echo $item['name'] ?></span>
                            </div>
                        </div>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php echo $agreements ?>
</div>
