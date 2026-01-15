<?php

namespace Tpay;

use Tpay\Api\Client;
use Tpay\Api\Transactions;
use Tpay\Helpers\Cache;

class TpaySettings
{
    public const CANCEL_DEFAULT_PERIOD = 30;
    public const GENERICS_MOVED_OUTSIDE = [
        84, // BLIK BNPL
    ];

    private $tpay_settings_options;
    private $fields;
    private $transactions;

    public function __construct()
    {
        $this->fields = $this->tpay_fields();
        $this->transactions = new Transactions(new Client(), new Cache());
        add_action('admin_menu', [$this, 'tpay_settings_add_plugin_page']);
        add_action('admin_init', [$this, 'tpay_settings_page_init']);
    }

    /** @return array */
    public static function tpay_fields()
    {
        return [
            'security_code' => [
                'label' => esc_html__('Security code', 'tpay'),
                'description' => esc_html__('You find in Merchant\'s Panel: Settings -> Notifications', 'tpay'),
            ],
            'api_key' => [
                'label' => esc_html__('Client ID', 'tpay'),
                'description' => esc_html__(
                    'You find in Merchant\'s Panel: Integration -> API -> Open Api Keys section',
                    'tpay'
                ),
            ],
            'api_key_password' => [
                'label' => esc_html__('Secret', 'tpay'),
                'description' => esc_html__(
                    'You find in Merchant\'s Panel: Integration -> API -> Open Api Keys section',
                    'tpay'
                ),
            ],
        ];
    }

    public function tpay_settings_add_plugin_page()
    {
        $capability = apply_filters('tpay_settings_add_plugin_page_capability', 'manage_options', []);

        add_submenu_page(
            'woocommerce',
            esc_html__('Tpay settings', 'tpay'), // page_title
            esc_html__('Tpay settings', 'tpay'), // menu_title
            $capability, // capability
            'tpay-settings', // menu_slug
            [$this, 'tpay_settings_create_admin_page'], // function
            100
        );
    }

    public function tpay_settings_create_admin_page()
    {
        (new Cache())->erase();
        $this->tpay_settings_options = tpayOption(); ?>

        <div class="wrap">
            <h2><?php
                echo esc_html__('Tpay settings', 'tpay'); ?></h2>
            <p></p>
            <?php
            settings_errors(); ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('tpay_settings_option_group');
        do_settings_sections('tpay-settings-admin');
        submit_button();
        ?>
            </form>
        </div>
        <?php
    }

    public function tpay_settings_page_init()
    {
        register_setting(
            'tpay_settings_option_group', // option_group
            'tpay_settings_option_name', // option_name
            [$this, 'tpay_settings_sanitize'] // sanitize_callback
        );

        // global
        add_settings_section(
            'tpay_settings_setting_section', // id
            esc_html__('Tpay config global', 'tpay'), // title
            [], // callback
            'tpay-settings-admin' // page
        );
        foreach ($this->fields as $field => $desc) {
            $args = [
                'id' => 'global_'.$field,
                'desc' => $desc['label'],
                'name' => 'tpay_settings_option_name',
                'description' => $desc['description'],
            ];
            add_settings_field(
                $args['id'], // id
                $args['desc'], // title
                [$this, 'global_callback'], // callback
                'tpay-settings-admin', // page
                'tpay_settings_setting_section',
                $args
            );
        }

        add_settings_field(
            'global_tpay_environment', // id
            esc_html__('Tpay environment', 'tpay'), // title
            [$this, 'global_change_environment_callback'], // callback
            'tpay-settings-admin', // page
            'tpay_settings_setting_section' // section
        );

        $args = [
            'id' => 'global_merchant_email',
            'desc' => esc_html__('Merchant email', 'tpay'),
            'name' => 'tpay_settings_option_name',
            'required' => true,
            'type' => 'email',
            'step' => '',
        ];
        add_settings_field(
            $args['id'], // id
            $args['desc'], // title
            [$this, 'global_callback'], // callback
            'tpay-settings-admin', // page
            'tpay_settings_setting_section', // section
            $args
        );
        add_settings_field(
            'global_default_on_hold_status', // id
            esc_html__('Successful payment status', 'tpay'), // title
            [$this, 'global_default_on_hold_status_callback'], // callback
            'tpay-settings-admin', // page
            'tpay_settings_setting_section' // section
        );

        add_settings_field(
            'global_default_virtual_product_on_hold_status', // id
            esc_html__('Successful payment status for virtual products', 'tpay'), // title
            [$this, 'global_default_virtual_product_on_hold_status_callback'], // callback
            'tpay-settings-admin', // page
            'tpay_settings_setting_section' // section
        );

        if (false === \WC_Blocks_Utils::has_block_in_page(wc_get_page_id('checkout'), 'woocommerce/checkout')) {
            add_settings_field(
                'enable_fee', // id
                esc_html__('Enable fee', 'tpay'), // title
                [$this, 'global_enable_fee_callback_callback'], // callback
                'tpay-settings-admin', // page
                'tpay_settings_setting_section' // section
            );
            $args = [
                'id' => 'global_amount_fee',
                'desc' => esc_html__('Amount fee', 'tpay'),
                'name' => 'tpay_settings_option_name',
                'type' => 'number',
                'step' => '0.01',
            ];
            add_settings_field(
                $args['id'], // id
                $args['desc'], // title
                [$this, 'global_callback'], // callback
                'tpay-settings-admin', // page
                'tpay_settings_setting_section', // section
                $args
            );

            $args = [
                'id' => 'global_percentage_fee',
                'desc' => esc_html__('Percentage fee', 'tpay'),
                'name' => 'tpay_settings_option_name',
                'type' => 'number',
                'step' => '0.01',
            ];
            add_settings_field(
                $args['id'], // id
                $args['desc'], // title
                [$this, 'global_callback'], // callback
                'tpay-settings-admin', // page
                'tpay_settings_setting_section', // section
                $args
            );
        }

        add_settings_field(
            'global_render_payment_type', // id
            esc_html__('Displaying the list of payments', 'tpay'), // title
            [$this, 'global_render_payment_type_callback'], // callback
            'tpay-settings-admin', // page
            'tpay_settings_setting_section' // section
        );

        add_settings_field(
            'global_tax_id_meta_field_name',
            esc_html__('Tax identifier field name', 'tpay'),
            [$this, 'global_callback'],
            'tpay-settings-admin',
            'tpay_settings_setting_section',
            [
                'id' => 'global_tax_id_meta_field_name',
                'description' => esc_html__(
                    'If you\'re added extra meta data including tax id to order - place here meta field name',
                    'tpay'
                ),
            ]
        );

        $channels = [];
        try {
            $channels = $this->transactions->channels();
        } catch (\Exception $e) {
            if (is_admin()) {
                @add_settings_error(
                    'general',
                    'settings_updated',
                    __('Failed to load payment channels. Please try again.', 'tpay'),
                    'error'
                );
            }
        }
        $channelNames = [];
        foreach ($channels as $channel) {
            $channelNames[$channel->id] = $channel->name;
        }

        foreach (self::GENERICS_MOVED_OUTSIDE as $id) {
            if (!isset($channelNames[$id])) {
                if (TpayGeneric::BLIK_BNPL == $id) {
                    add_settings_field(
                        'global_generic_payments_DISABLED_'.$id,
                        'BLIK Płacę Później',
                        [$this, 'global_generic_payments_checkbox_disabled_callback'],
                        'tpay-settings-admin',
                        'tpay_settings_setting_section',
                        [
                            'id' => $id,
                        ]
                    );
                }

                continue;
            }

            add_settings_field(
                'global_generic_payments_'.$id,
                __($channelNames[$id], 'tpay'),
                [$this, 'global_generic_payments_checkbox_callback'],
                'tpay-settings-admin',
                'tpay_settings_setting_section',
                [
                    'id' => $id,
                ]
            );
        }

        add_settings_field(
            'global_generic_payments',
            __('Easy ON-site', 'tpay'),
            [$this, 'global_generic_payments_callback'],
            'tpay-settings-admin',
            'tpay_settings_setting_section',
            [
                'id' => 'global_generic_payments',
                'description' => __('To select multiple items hold CTRL button', 'tpay'),
            ]
        );

        add_settings_field(
            'global_generic_auto_cancel_enabled',
            __('Automatic order cancellation', 'tpay'),
            [$this, 'global_cancel_callback'],
            'tpay-settings-admin',
            'tpay_settings_setting_section',
            [
                'id' => 'global_generic_auto_cancel_enabled',
                'description' => esc_html__(
                    'When enabled once a day Your store will try to analyse orders in pending payment status and cancel those older than number of days specified in next configuration option',
                    'tpay'
                ),
                'type' => 'checkbox',
            ]
        );
    }

    public function global_generic_payments_checkbox_callback($args)
    {
        $checkedChannels = tpayOption('global_generic_payments') ?? [];
        if (!is_array($checkedChannels)) {
            $checkedChannels = [];
        }
        $checked = '';

        if (in_array($args['id'], $checkedChannels)) {
            $checked = 'checked="checked"';
        }
        $id = $args['id'];
        printf(
            '<input type="checkbox" class="regular-text" value="%s" name="tpay_settings_option_name[global_generic_payments][]" id="global_generic_payments_%s" %s/>',
            $id,
            $id,
            $checked
        );
        if (TpayGeneric::BLIK_BNPL === $id) {
            echo '<a href="javascript:jQuery(\'#what-is-blik\').toggle()">'.__(
                'What is BLIK Pay Later?',
                'tpay'
            ).'</a><br/>'
                .'<span id="what-is-blik" style="display:none;">'
                .__('BLIK Pay Later is a deferred payment service for transactions ranging from 30 PLN to 4,000 PLN.<br/>'
                .'You will receive the money for the sold goods immediately, while the Customer will have 30 days to make the payment.<br/>'
                .'<a href="https://www.blik.com/place-pozniej" target="blank">Learn more.</a>', 'tpay')
                .'</span><br/>';
        }
    }

    public function global_generic_payments_checkbox_disabled_callback($args)
    {
        echo '<input type="checkbox" disabled class="regular-text" name="" id=""/>';
        echo '<a href="javascript:jQuery(\'#blik-enable-info\').toggle()">'.__('Can\'t enable BLIK Pay Later?', 'tpay').'</a><br/>'
            .'<span id="blik-enable-info" style="display:none;">'
            .__('Log in to the <a href="https://panel.tpay.com" target="_blank">Tpay Merchant Panel</a> and check if BLIK Pay Later is active.<br/>'
                .'If the payment option is not enabled, activate it and then re-enable it in your store.', 'tpay')
            .'<br/></span><br/>';
        echo '<a href="javascript:jQuery(\'#what-is-blik\').toggle()">'.__(
            'What is BLIK Pay Later?',
            'tpay'
        ).'</a><br/>'
            .'<span id="what-is-blik" style="display:none;">'
            .__('BLIK Pay Later is a deferred payment service for transactions ranging from 30 PLN to 4,000 PLN.<br/>'
                .'You will receive the money for the sold goods immediately, while the Customer will have 30 days to make the payment.<br/>'
                .'<a href="https://www.blik.com/place-pozniej" target="blank">Learn more.</a>', 'tpay')
            .'</span><br/>';
    }

    /** @param array $args */
    public function global_callback($args)
    {
        $id = $args['id'];
        $required = (isset($args['required']) && $args['required']) ? 'required="required"' : '';
        $type = $args['type'] ?? 'text';
        $step = $args['step'] ?? '';
        $value = isset($this->tpay_settings_options[$id]) ? esc_attr($this->tpay_settings_options[$id]) : '';
        $checked = '';

        if ('checkbox' === $type && 1 == $value) {
            $checked = 'checked="checked"';
        }

        printf(
            '<input type="%s" step="%s" class="regular-text" value="%s" name="tpay_settings_option_name[%s]" id="%s" %s %s/>',
            $type,
            $step,
            $value,
            $id,
            $id,
            $required,
            $checked
        );

        if (isset($args['description']) && $args['description']) {
            echo "<span class='tpay-help-tip' aria-label='{$args['description']}'><strong>?</strong></span>";
        }
    }

    public function global_generic_payments_callback($args)
    {
        try {
            $channels = $this->transactions->channels();
        } catch (\Exception $e) {
            error_log('[Tpay] Unable to load channels in settings: '.$e->getMessage());
            $channels = [];
        }

        $checkedChannels = tpayOption('global_generic_payments') ?? [];
        if (!is_array($checkedChannels)) {
            $checkedChannels = [];
        }

        ?>
        <select class="tpay-select" id="global_generic_payments" multiple
                name="tpay_settings_option_name[global_generic_payments][]">
            <?php
            foreach ($channels as $channel) {
                if (in_array($channel->id, self::GENERICS_MOVED_OUTSIDE)) {
                    continue;
                }
                $checked = in_array($channel->id, $checkedChannels) ? 'selected' : '';

                echo "<option {$checked} value='{$channel->id}'>{$channel->name}</option>";
            } ?>
        </select>
        <?php

        if (isset($args['description']) && $args['description']) {
            echo "<span class='tpay-help-tip' aria-label='{$args['description']}'><strong>?</strong></span>";
        }
    }

    public function global_cancel_callback($args)
    {
        $id = $args['id'];
        $value = isset($this->tpay_settings_options[$id]) ? esc_attr($this->tpay_settings_options[$id]) : '';
        $checked = '';

        if (1 == $value) {
            $checked = 'checked="checked"';
        }

        printf(
            '<input type="checkbox" class="regular-text" value="1" name="tpay_settings_option_name[%s]" id="%s" %s onchange="document.getElementById(\'global_generic_auto_cancel_days\').disabled = !this.checked;"/>',
            $id,
            $id,
            $checked
        );

        echo ' '.esc_html__('Enable', 'tpay');
        if (isset($args['description']) && $args['description']) {
            echo "<span class='tpay-help-tip' aria-label='{$args['description']}'><strong>?</strong></span>";
        }

        echo '<br />';

        echo esc_html__('Number of days', 'tpay').': ';

        $id = 'global_generic_auto_cancel_days';
        $value = isset($this->tpay_settings_options[$id]) ? esc_attr(
            $this->tpay_settings_options[$id]
        ) : self::CANCEL_DEFAULT_PERIOD;
        $disabled = empty($checked) ? 'disabled="disabled"' : '';

        printf(
            '<input type="number" min="1" max="30" step="1" value="%s" name="tpay_settings_option_name[%s]" id="%s" %s/>',
            $value,
            $id,
            $id,
            $disabled,
        );
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function tpay_settings_sanitize($input)
    {
        foreach ($this->fields as $field => $desc) {
            if (isset($input['global_'.$field])) {
                $value = sanitize_text_field($input['global_'.$field]);

                if (in_array($field, ['security_code', 'api_key', 'api_key_password'])) {
                    $value = preg_replace('/\s+/', '', $value);
                }

                $sanitary_values['global_'.$field] = $value;
            }
        }

        $arrayToSearch = $input['generics'];
        if (!is_array($arrayToSearch)) {
            $arrayToSearch = [];
        }

        if (isset($input['global_merchant_email'])) {
            $sanitary_values['global_merchant_email'] = sanitize_text_field($input['global_merchant_email']);
        }

        if (isset($input['global_default_on_hold_status'])) {
            $sanitary_values['global_default_on_hold_status'] = sanitize_text_field(
                $input['global_default_on_hold_status']
            );
        }

        if (isset($input['global_default_virtual_product_on_hold_status'])) {
            $sanitary_values['global_default_virtual_product_on_hold_status'] = sanitize_text_field(
                $input['global_default_virtual_product_on_hold_status']
            );
        }

        if (isset($input['global_enable_fee'])) {
            $sanitary_values['global_enable_fee'] = sanitize_text_field($input['global_enable_fee']);
        }

        if (isset($input['global_amount_fee'])) {
            $sanitary_values['global_amount_fee'] = sanitize_text_field(
                str_replace(',', '.', $input['global_amount_fee'])
            );
        }

        if (isset($input['global_percentage_fee'])) {
            $sanitary_values['global_percentage_fee'] = sanitize_text_field(
                str_replace(',', '.', $input['global_percentage_fee'])
            );
        }

        if (isset($input['global_tpay_environment'])) {
            $sanitary_values['global_tpay_environment'] = sanitize_text_field($input['global_tpay_environment']);
        }

        if (isset($input['global_render_payment_type'])) {
            $sanitary_values['global_render_payment_type'] = sanitize_text_field($input['global_render_payment_type']);
        }

        if (isset($input['global_tax_id_meta_field_name'])) {
            $sanitary_values['global_tax_id_meta_field_name'] = sanitize_text_field(
                $input['global_tax_id_meta_field_name']
            );
        }

        if (isset($input['global_generic_payments'])) {
            $sanitary_values['global_generic_payments'] = $input['global_generic_payments'];
        }

        if (isset($input['global_generic_auto_cancel_enabled'])) {
            $sanitary_values['global_generic_auto_cancel_enabled'] = (int) $input['global_generic_auto_cancel_enabled'];
        }

        if (isset($input['global_generic_auto_cancel_days'])) {
            $sanitary_values['global_generic_auto_cancel_days'] = abs((int) $input['global_generic_auto_cancel_days']);
        }
        if (empty($this->tpay_settings_options)) {
            $this->tpay_settings_options = tpayOption();
        }
        if (empty($sanitary_values['global_generic_auto_cancel_days'])) {
            $sanitary_values['global_generic_auto_cancel_days'] = self::CANCEL_DEFAULT_PERIOD;
            if (isset($this->tpay_settings_options['global_generic_auto_cancel_days'])) {
                $sanitary_values['global_generic_auto_cancel_days'] = $this->tpay_settings_options['global_generic_auto_cancel_days'];
            }
        }

        return $sanitary_values;
    }

    public function global_default_on_hold_status_callback()
    {
        ?>
        <select class="regular-text" type="text" name="tpay_settings_option_name[global_default_on_hold_status]"
                id="global_default_on_hold_status">
            <?php
            foreach ($this->before_payment_statuses() as $key => $value) { ?>
                <option <?php
                if (@$this->tpay_settings_options['global_default_on_hold_status'] === $key) {
                    echo 'selected="selected"';
                } ?>
                        value="<?php
                        echo esc_attr($key); ?>"><?php
                    echo $value; ?></option>
                <?php
            } ?>
        </select>
        <?php
    }

    public function global_default_virtual_product_on_hold_status_callback()
    {
        ?>
        <select class="regular-text" type="text"
                name="tpay_settings_option_name[global_default_virtual_product_on_hold_status]"
                id="global_default_virtual_product_on_hold_status">
            <?php
            foreach ($this->before_payment_statuses() as $key => $value) { ?>
                <option <?php
                if (@$this->tpay_settings_options['global_default_virtual_product_on_hold_status'] === $key) {
                    echo 'selected="selected"';
                } ?>
                        value="<?php
                        echo esc_attr($key); ?>"><?php
                    echo $value; ?></option>
                <?php
            } ?>
        </select>
        <?php
    }

    public function global_change_environment_callback()
    {
        $options = [
            'prod' => esc_html__('Production', 'tpay'),
            'sandbox' => esc_html__('Sandbox', 'tpay'),
        ];
        ?>
        <select class="regular-text" type="text" name="tpay_settings_option_name[global_tpay_environment]"
                id="global_tpay_environment">
            <?php
            foreach ($options as $key => $value) { ?>
                <option <?php
                if (@$this->tpay_settings_options['global_tpay_environment'] === $key) {
                    echo 'selected="selected"';
                } ?>
                        value="<?php
                        echo $key; ?>"><?php
                    echo $value; ?></option>
                <?php
            } ?>
        </select>
        <?php
    }

    public function global_enable_fee_callback_callback()
    {
        $options = [
            'disabled' => esc_html__('Disabled', 'tpay'),
            'amount' => esc_html__('Amount', 'tpay'),
            'percentage' => esc_html__('Percentage', 'tpay'),
        ];
        ?>
        <select class="regular-text" type="text" name="tpay_settings_option_name[global_enable_fee]"
                id="global_enable_fee">
            <?php
            foreach ($options as $key => $value) { ?>
                <option <?php
                if (@$this->tpay_settings_options['global_enable_fee'] === $key) {
                    echo 'selected="selected"';
                } ?>
                        value="<?php
                        echo $key; ?>"><?php
                    echo $value; ?></option>
                <?php
            } ?>
        </select>
        <?php
    }

    public function global_render_payment_type_callback()
    {
        $options = [
            'tiles' => esc_html__('Tiles', 'tpay'),
            'list' => esc_html__('Dropdown list', 'tpay'),
        ];
        ?>
        <select class="regular-text" type="text" name="tpay_settings_option_name[global_render_payment_type]"
                id="global_render_payment_type">
            <?php
            foreach ($options as $key => $value) { ?>
                <option <?php
                if (@$this->tpay_settings_options['global_render_payment_type'] === $key) {
                    echo 'selected="selected"';
                } ?>
                        value="<?php
                        echo $key; ?>"><?php
                    echo $value; ?></option>
                <?php
            } ?>
        </select>
        <?php
    }

    public function before_payment_statuses(): array
    {
        $statuses = wc_get_order_statuses();
        $available = [];

        foreach ($statuses as $key => $value) {
            $key = str_replace('wc-', '', $key);
            $available[$key] = $value;
        }

        ksort($available);

        return $available;
    }
}
