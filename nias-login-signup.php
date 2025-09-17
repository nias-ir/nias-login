<?php
/**
 * Nias login signup
 *
 * @package           PluginPackage
 * @author            Alireza aliniya
 * @copyright         2024 nias
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Nias login signup | پلاگین ورود و ثبت نام نیاس
 * Plugin URI:        https://nias.ir
 * Description:        این پلاگین توسط <a href="https://nias.ir"> نیاس </a>طراحی و توسعه داده شده |پلاگینی ساده در جهت ورود و ثبت نام پیامکی برای دیدن آموزش های پلاگین دوره المنتور نیاس را دنبال کنید  
 * Version:           1.2.4
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Alireza aliniya
 * Author URI:        https://nias.ir
 * Text Domain:       nias-login-signup
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
defined ('ABSPATH') || exit;

define('NIAS_LOGIN_VERSION' , '1.2.4');

define('NIAS_LOGIN_URL', plugin_dir_url(__FILE__));
define('NIAS_LOGIN_CSS', NIAS_LOGIN_URL . 'assets/css/');
define('NIAS_LOGIN_JS', NIAS_LOGIN_URL . 'assets/js/');
define('NIAS_LOGIN_IMAGES', NIAS_LOGIN_URL . 'assets/images/');

define('NIAS_LOGIN_PUBLIC' , plugin_dir_path( __FILE__ ) . 'public/');
define('NIAS_LOGIN_VIEW' , plugin_dir_path( __FILE__ ) . 'view/');
define('NIAS_LOGIN_INC' , plugin_dir_path( __FILE__ ) . 'inc/');
define('NIAS_LOGIN_ADMIN' , plugin_dir_path( __FILE__ ) . 'admin/');
define('NIAS_LOGIN_TEMPLATE' , plugin_dir_path( __FILE__ ) . 'shortcodes-template/');
define('NIAS_ELEMENTOR_WIDGET' , plugin_dir_path( __FILE__ ) . 'elementor-widget/');


global $wpdb;
$wpdb->nias_sms_login = $wpdb->prefix . 'nias_sms_login';
$wpdb->nias_blockedip_sms = $wpdb->prefix . 'nias_blockedip_sms';

// Include the Login Handler class
require_once NIAS_LOGIN_INC . 'class-nias-login-handler.php';
require_once NIAS_LOGIN_INC . 'class-email-gateway.php';
require(NIAS_ELEMENTOR_WIDGET . 'modal-widget.php');
require(NIAS_LOGIN_TEMPLATE . 'simple.php');
require(NIAS_LOGIN_INC . 'enqueue.php');
require(NIAS_LOGIN_INC . 'functions.php');
require(NIAS_LOGIN_INC . 'activation.php');
require(NIAS_LOGIN_INC . 'ajax.php');


//require(NIAS_LOGIN_INC . 'gateways.php');
require(NIAS_LOGIN_PUBLIC . 'modal.php');
if(is_admin()){
    require(NIAS_LOGIN_ADMIN . 'manage-users.php');
}


//register_activation_hook( __FILE__, 'nias_sms_login_activation' );
//register_activation_hook( __FILE__, 'nias_blockedip_sms_activation' );
register_activation_hook(__FILE__, 'nias_create_all_tables');
register_deactivation_hook(__FILE__, 'nias_drop_all_tables');

add_action('plugins_loaded', function() {
    $current_version = get_option('nias_plugin_version', '1.0');
    $new_version = NIAS_LOGIN_VERSION;
    
    if (version_compare($current_version, $new_version, '<')) {
        nias_upgrade_tables($current_version, $new_version);
        update_option('nias_plugin_version', $new_version);
    }
});


// Schedule a daily cron job
//این کرون جدول مربوط به پلاگین را بصورت ساعتی پاکسازی میکند
//میتوانید , daily, hourly را به weekly تغییر دهید
if (!wp_next_scheduled('nias_login_plugin_event')) {
 wp_schedule_event(time(), 'daily', 'nias_login_plugin_event');

}
/*
if (!wp_next_scheduled('nias_login_plugin_event')) {
    error_log('Event scheduling failed!');
} else {
    error_log('Event scheduled successfully!');
}
*/

function nias_login_plugin_function() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'nias_sms_login';

    // Cleanup query for the 'nias_sms_login' table
    $wpdb->query("DELETE FROM $table_name;");
}

// Add cleanup function to the daily cron job
add_action('nias_login_plugin_event', 'nias_login_plugin_function');

//update checker

require (plugin_dir_path(__FILE__) .'plugin-update-checker/plugin-update-checker.php');
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://update.nias.ir/nias-login-signup/nias-login-signup.json',
	__FILE__, //Full path to the main plugin file or functions.php.
	'nias-login-signup'
);


// تابع فلش
function nias_flush_rewrite_rules() {
    flush_rewrite_rules();
}

// اجرا هنگام فعال‌سازی پلاگین
register_activation_hook(__FILE__, function() {
    update_option('nias_needs_flush', true);
});

// اجرا هنگام بروزرسانی پلاگین
add_action('upgrader_process_complete', function($upgrader_object, $options) {
    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        $plugin_basename = plugin_basename(__FILE__);
        if (isset($options['plugins']) && in_array($plugin_basename, $options['plugins'])) {
            update_option('nias_needs_flush', true);
        }
    }
}, 10, 2);

// اجرا هنگام غیرفعال‌سازی پلاگین
register_deactivation_hook(__FILE__, 'nias_flush_rewrite_rules');

// اجرا هنگام حذف پلاگین
register_uninstall_hook(__FILE__, 'nias_flush_rewrite_rules');

// اجرای فلش در اولین بار بعد از فعال‌سازی/بروزرسانی
add_action('init', function() {
    if (get_option('nias_needs_flush')) {
        nias_flush_rewrite_rules();
        delete_option('nias_needs_flush');
    }
});

// ───────────────────────────────
// دکمه فلش در صفحه پلاگین‌ها
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'nias_add_settings_link');
function nias_add_settings_link($links) {
    $flush_link = '<a href="' . wp_nonce_url(admin_url('admin-post.php?action=nias_flush_rewrite'), 'nias_flush') . '">فلش لینک‌ها</a>';
    $settings_link = '<a href="' . admin_url('admin.php?page=niasloginsignup') . '">تنظیمات</a>';
    array_unshift($links, $flush_link, $settings_link);
    return $links;
}

// اجرای فلش از دکمه
add_action('admin_post_nias_flush_rewrite', 'nias_handle_flush_rewrite_request');
function nias_handle_flush_rewrite_request() {
    if (!current_user_can('manage_options') || !check_admin_referer('nias_flush')) {
        wp_die('دسترسی غیرمجاز');
    }
    nias_flush_rewrite_rules();
    wp_safe_redirect(admin_url('plugins.php?flushed=true'));
    exit;
}

// پیام موفقیت
add_action('admin_notices', function() {
    if (isset($_GET['flushed'])) {
        echo '<div class="notice notice-success is-dismissible"><p>فلش با موفقیت انجام شد و لینک‌ها بازسازی شد.</p></div>';
    }
});


//Thanks to https://github.com/hamedmoody for providing the tutorial on creating the main source of the plugin
