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

    /**
     * @return array
     */
    public static function tpay_fields()
    {
        return [
            'security_code' => [
                'label' => __('Secret key (in notifications)', 'tpay'),
                'description' => __('The security code for your tpay.com account.', 'tpay')
            ],
            'api_key' => [
                'label' => __('Client ID', 'tpay'),
                'description' => __('API key generated in tpay.com payment recipient\'s panel.',
                    'tpay'),
            ],
            'api_key_password' => [
                'label' => __('API key password', 'tpay'),
                'description' => __('API key password', 'tpay'),
            ]
        ];
    }

    /**
     * @return null
     */
    public function tpay_settings_add_plugin_page()
    {
        add_submenu_page(
            'woocommerce',
            __('Tpay settings', 'tpay'), // page_title
            __('Tpay settings', 'tpay'), // menu_title
            'manage_options', // capability
            'tpay-settings', // menu_slug
            [$this, 'tpay_settings_create_admin_page'], // function
            100
        );
    }

    /**
     * @return void
     */
    public function tpay_settings_create_admin_page()
    {
        (new Cache())->erase();
        $this->tpay_settings_options = get_option('tpay_settings_option_name'); ?>

        <div class="wrap">
            <h2><?php echo __('Tpay settings', 'tpay') ?></h2>
            <p></p>
            <?php settings_errors(); ?>
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

    /**
     * @return void
     */
    public function tpay_settings_page_init()
    {
        register_setting(
            'tpay_settings_option_group', // option_group
            'tpay_settings_option_name', // option_name
            [$this, 'tpay_settings_sanitize'] // sanitize_callback
        );

        //global
        add_settings_section(
            'tpay_settings_setting_section', // id
            __('Tpay config global', 'tpay'), // title
            [], // callback
            'tpay-settings-admin' // page
        );
        foreach ($this->fields as $field => $desc) {
            $args = [
                'id' => 'global_' . $field,
                'desc' => $desc['label'],
                'name' => 'tpay_settings_option_name'
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
            __('Tpay environment', 'tpay'), // title
            [$this, 'global_change_environment_callback'], // callback
            'tpay-settings-admin', // page
            'tpay_settings_setting_section' // section
        );

        $args = [
            'id' => 'global_merchant_email',
            'desc' => __('Merchant email', 'tpay'),
            'name' => 'tpay_settings_option_name',
            'required' => true,
            'type' => 'email'
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
            __('Default on-hold status', 'tpay'), // title
            [$this, 'global_default_on_hold_status_callback'], // callback
            'tpay-settings-admin', // page
            'tpay_settings_setting_section' // section
        );
        add_settings_field(
            'enable_fee', // id
            __('Enable fee', 'tpay'), // title
            [$this, 'global_enable_fee_callback_callback'], // callback
            'tpay-settings-admin', // page
            'tpay_settings_setting_section' // section
        );
        $args = [
            'id' => 'global_amount_fee',
            'desc' => __('Amount fee', 'tpay'),
            'name' => 'tpay_settings_option_name'
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
            'desc' => __('Percentage fee', 'tpay'),
            'name' => 'tpay_settings_option_name'
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
            'global_render_payment_type', // id
            __('Displaying the list of payments', 'tpay'), // title
            [$this, 'global_render_payment_type_callback'], // callback
            'tpay-settings-admin', // page
            'tpay_settings_setting_section' // section
        );
    }

    /**
     * @param array $args
     * @return void
     */
    public function global_callback($args)
    {
        $id = $args['id'];
        $required = @$args['required'] ? 'required="required"' : '';
        $type = @$args['type'] ? $type = @$args['type'] : $type = "text";
        $value = isset($this->tpay_settings_options[$id]) ? esc_attr($this->tpay_settings_options[$id]) : '';
        printf('<input type="' . $type . '" class="regular-text" value="%s" name="tpay_settings_option_name[%s]" id="%s" ' . $required . ' />',
            $value, $id, $id);
    }

    /**
     * @param array $input
     * @return array
     */
    public function tpay_settings_sanitize($input)
    {
        foreach ($this->fields as $field => $desc) {
            if (isset($input['global_' . $field])) {
                $sanitary_values['global_' . $field] = sanitize_text_field($input['global_' . $field]);
            }
        }

        if (isset($input['global_merchant_email'])) {
            $sanitary_values['global_merchant_email'] = sanitize_text_field($input['global_merchant_email']);
        }

        if (isset($input['global_default_on_hold_status'])) {
            $sanitary_values['global_default_on_hold_status'] = sanitize_text_field($input['global_default_on_hold_status']);
        }

        if (isset($input['global_enable_fee'])) {
            $sanitary_values['global_enable_fee'] = sanitize_text_field($input['global_enable_fee']);
        }

        if (isset($input['global_amount_fee'])) {
            $sanitary_values['global_amount_fee'] = sanitize_text_field(str_replace(',', '.', $input['global_amount_fee']));
        }

        if (isset($input['global_percentage_fee'])) {
            $sanitary_values['global_percentage_fee'] = sanitize_text_field(str_replace(',', '.', $input['global_percentage_fee']));
        }

        if (isset($input['global_tpay_environment'])) {
            $sanitary_values['global_tpay_environment'] = sanitize_text_field($input['global_tpay_environment']);
        }

        if (isset($input['global_render_payment_type'])) {
            $sanitary_values['global_render_payment_type'] = sanitize_text_field($input['global_render_payment_type']);
        }

        return $sanitary_values;
    }


    /**
     * @return null
     */
    public function global_default_on_hold_status_callback()
    {
        ?>
        <select class="regular-text" type="text" name="tpay_settings_option_name[global_default_on_hold_status]"
                id="global_default_on_hold_status">
            <?php foreach ($this->before_payment_statuses() as $key => $value): ?>
                <option <?php if (@$this->tpay_settings_options['global_default_on_hold_status'] === $key) echo 'selected="selected"' ?>
                        value="<?php echo $key ?>"><?php echo $value ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * @return null
     */
    public function global_change_environment_callback()
    {
        $options = [
            'prod' => __('Production', 'tpay'),
            'sandbox' => __('Sandbox', 'tpay'),
        ];
        ?>
        <select class="regular-text" type="text" name="tpay_settings_option_name[global_tpay_environment]"
                id="global_tpay_environment">
            <?php foreach ($options as $key => $value): ?>
                <option <?php if (@$this->tpay_settings_options['global_tpay_environment'] === $key) echo 'selected="selected"' ?>
                        value="<?php echo $key ?>"><?php echo $value ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    public function global_enable_fee_callback_callback()
    {
        $options = [
            'disabled' => __('Disabled', 'tpay'),
            'amount' => __('Amount', 'tpay'),
            'percentage' => __('Percentage', 'tpay'),
        ];
        ?>
        <select class="regular-text" type="text" name="tpay_settings_option_name[global_enable_fee]"
                id="global_enable_fee">
            <?php foreach ($options as $key => $value): ?>
                <option <?php if (@$this->tpay_settings_options['global_enable_fee'] === $key) echo 'selected="selected"' ?>
                        value="<?php echo $key ?>"><?php echo $value ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * @return null
     */
    public function global_render_payment_type_callback()
    {
        $options = [
            'tiles' => __('Tiles', 'tpay'),
            'list' => __('Dropdown list', 'tpay'),
        ];
        ?>
        <select class="regular-text" type="text" name="tpay_settings_option_name[global_render_payment_type]"
                id="global_render_payment_type">
            <?php foreach ($options as $key => $value): ?>
                <option <?php if (@$this->tpay_settings_options['global_render_payment_type'] === $key) echo 'selected="selected"' ?>
                        value="<?php echo $key ?>"><?php echo $value ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * @return array
     */
    public function before_payment_statuses()
    {
        $statuses = wc_get_order_statuses();
        $available = [];
        foreach ($statuses as $key => $value) {
            if (in_array($key, ['wc-completed', 'wc-pending', 'wc-on-hold'])) {
                $available[str_replace('wc-', '', $key)] = $value;
            }
        }
        ksort($available);
        return $available;
    }

}

