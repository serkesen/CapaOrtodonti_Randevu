<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Veritabanı tablolarını ve ayarları temizle
global $wpdb;

// Önbellekteki tüm dentsoft verilerini temizle
wp_cache_delete('dentsoft_settings', 'options');
$appointments = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}dentsoft_appointments");
if ($appointments) {
    foreach ($appointments as $appointment) {
        wp_cache_delete('dentsoft_appointment_' . $appointment->id);
        wp_cache_delete('dentsoft_appointment_status_' . $appointment->id);
    }
}

// Tabloyu kaldır
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}dentsoft_appointments");

// Ayarları temizle
delete_option('dentsoft_settings');
