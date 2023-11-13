<div class="tpay-pbl-container">
    <div class="tpay-pbl">
        <?php foreach ($list as $item): ?>
            <label class="tpay-item" data-groupID="<?php echo $item['id'] ?>">
                <input name="tpay-channelID" type="radio" value="<?php echo $item['id'] ?>"/>
                <div>
                    <div>
                        <div class="tpay-group-logo-holder">
                            <img src="<?php echo $item['image']['url'] ?>" class="tpay-group-logo"
                                 alt="<?php echo $item['name'] ?>">
                        </div>
                        <span class="name"><?php echo $item['name'] ?></span>
                    </div>
                </div>
            </label>
        <?php endforeach; ?>
    </div>
</div>