<?php
// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// drop a custom database table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nias_sms_login" );
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nias_blockedip_sms");

// Unschedule the event
wp_unschedule_event( wp_next_scheduled( 'nias_login_plugin_event' ), 'nias_login_plugin_event' );
