<div class="tpay-blik0">
    <?php
    if ($description) { ?>
        <div class="bottom">
            <span class="show-blik-info no-margin-left">
                <?php
                echo __('What is BLIK Pay Later?', 'tpay') ?>
                <span class="tooltip-text"><?php
                    echo $description ?></span>
            </span
        </div>
    <?php
    }; ?>
    <?php
    echo $agreements ?>
</div>

