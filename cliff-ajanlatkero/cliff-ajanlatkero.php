<?php
/**
 * Plugin Name: Cliff Ajánlatkérő Form
 * Plugin URI: https://cliffkonyhabutor.hu
 * Description: Online ajánlatkérő wizard form a Cliff Konyhák számára. Használat: [cliff_ajanlatkero] shortcode.
 * Version: 2.0.0
 * Author: Cliff Konyhák
 * Text Domain: cliff-ajanlatkero
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CLIFF_FORM_VERSION', '2.0.0');
define('CLIFF_FORM_PATH', plugin_dir_path(__FILE__));
define('CLIFF_FORM_URL', plugin_dir_url(__FILE__));

require_once CLIFF_FORM_PATH . 'includes/class-cliff-form.php';
require_once CLIFF_FORM_PATH . 'includes/class-cliff-ajax.php';
require_once CLIFF_FORM_PATH . 'includes/class-cliff-admin.php';

add_action('plugins_loaded', function () {
    Cliff_Form::init();
    Cliff_Ajax::init();
    Cliff_Admin::init();
});

register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table = $wpdb->prefix . 'cliff_ajanlat';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        form_data longtext DEFAULT '',
        alaprajz_url varchar(500) DEFAULT '',
        foto_url varchar(500) DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    if (!get_option('cliff_form_email')) {
        update_option('cliff_form_email', get_option('admin_email'));
    }

    // Set default options if not present
    if (!get_option('cliff_form_options')) {
        update_option('cliff_form_options', Cliff_Form::default_options());
    }
});

/* =========================================
   Global helper: get text from content settings
   ========================================= */

function cliff_text(string $key): string
{
    static $content = null;
    if ($content === null) {
        $content = get_option('cliff_form_content', []);
    }

    $val = $content[$key] ?? '';
    if ($val !== '') {
        return $val;
    }

    $defaults = Cliff_Form::defaults();
    return $defaults[$key] ?? '';
}

function cliff_img(string $key): string
{
    return cliff_text('img_' . $key);
}
