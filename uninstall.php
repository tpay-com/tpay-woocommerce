<?php
if (!defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'tpay_plugin_version' );
