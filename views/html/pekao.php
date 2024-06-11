<div class="tpay-pbl-container">
    <div class="tpay-pbl">
        <?php foreach ($list as $item): ?>
            <label class="tpay-item" data-groupID="<?php echo esc_attr($item->id) ?>">
                <input name="tpay-channel-id" type="radio" value="<?php echo esc_html($item->id) ?>"/>
                <div>
                    <div>
                        <div class="tpay-group-logo-holder">
                            <img src="<?php echo esc_url($item->image->url) ?>" class="tpay-group-logo"
                                 alt="<?php echo esc_attr($item->name) ?>">
                        </div>
                        <span class="name"><?php echo esc_html($item->name) ?></span>
                    </div>
                </div>
            </label>
        <?php endforeach; ?>
        <?php echo $agreements ?>
    </div>
</div>
