<?php

namespace Tpay;

use Tpay\Helpers\Cache;

class TpaySettings
{
    private $tpay_settings_options;
    private $fields;

    public function __construct()
    {
        $this->fields = $this->tpay_fields();
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
    }

    /** @param array $args */
    public function global_callback($args)
    {
        $id = $args['id'];
        $required = (isset($args['required']) && $args['required']) ? 'required="required"' : '';
        $type = (isset($args['type']) && $args['type']) ?: 'text';
        $step = (isset($args['step']) && $args['step']) ?: $type = '';
        $value = isset($this->tpay_settings_options[$id]) ? esc_attr($this->tpay_settings_options[$id]) : '';
        printf(
            '<input type="%s" step="%s" class="regular-text" value="%s" name="tpay_settings_option_name[%s]" id="%s" %s />',
            $type,
            $step,
            $value,
            $id,
            $id,
            $required
        );

        if (isset($args['description']) && $args['description']) {
            echo "<span class='tpay-help-tip' aria-label='{$args['description']}'><strong>?</strong></span>";
        }
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
                $sanitary_values['global_'.$field] = sanitize_text_field($input['global_'.$field]);
            }
        }

        if (isset($input['global_merchant_email'])) {
            $sanitary_values['global_merchant_email'] = sanitize_text_field($input['global_merchant_email']);
        }

        if (isset($input['global_default_on_hold_status'])) {
            $sanitary_values['global_default_on_hold_status'] = sanitize_text_field(
                $input['global_default_on_hold_status']
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
            if (in_array($key, ['wc-completed', 'wc-processing'])) {
                $available[str_replace('wc-', '', $key)] = $value;
            }
        }

        ksort($available);

        return $available;
    }
}
