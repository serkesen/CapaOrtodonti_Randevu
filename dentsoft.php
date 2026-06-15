<?php
/*
Plugin Name: DentSoft Online Randevu Sistemi
Plugin URI: https://wordpress.org/plugins/dentsoft-online-randevu-sistemi
Description: DentSoft kullanan klinikler ve diş hekimleri için özel olarak tasarlanmış, kullanımı kolay bir randevu yönetim sistemi.
Version: 2.0.0
Requires at least: 5.8
Requires PHP: 7.4
Author: DentSoft
Author URI: https://dentsoft.com.tr/
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: dentsoft-online-randevu-sistemi
Domain Path: /languages/
*/

if (!defined('ABSPATH')) {
    exit;
}

define('DENTSOFT_VERSION', '2.0.0');
define('DENTSOFT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DENTSOFT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DENTSOFT_PLUGIN_FILE', __FILE__);

final class DentSoft_Plugin {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        $this->includes();
    }
    
    private function init_hooks() {
        register_activation_hook(DENTSOFT_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(DENTSOFT_PLUGIN_FILE, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        add_shortcode('dentsoft_appointment_form', array($this, 'appointment_form_shortcode'));
    }
    
    private function includes() {
        require_once DENTSOFT_PLUGIN_DIR . 'includes/ajax-handlers.php';
    }
    
    public function init() {
        load_plugin_textdomain('dentsoft-online-randevu-sistemi', false, dirname(plugin_basename(DENTSOFT_PLUGIN_FILE)) . '/languages/');
    }
    
    public function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dentsoft_appointments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            patient_number varchar(100) DEFAULT NULL,
            patient_name varchar(100) DEFAULT NULL,
            patient_surname varchar(100) DEFAULT NULL,
            patient_phone varchar(50) DEFAULT NULL,
            patient_birthday date DEFAULT NULL,
            patient_email varchar(100) DEFAULT NULL,
            clinic_name varchar(255) DEFAULT NULL,
            clinic_address text DEFAULT NULL,
            clinic_phone varchar(50) DEFAULT NULL,
            doctor_name varchar(100) DEFAULT NULL,
            pnr_no varchar(50) DEFAULT NULL,
            appointment_date datetime DEFAULT NULL,
            appointment_status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pnr_no (pnr_no),
            KEY patient_number (patient_number),
            KEY appointment_date (appointment_date),
            KEY appointment_status (appointment_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $default_settings = array(
            'vkn' => '',
            'api_url' => 'https://clinic.dentsoft.com.tr/Api/v1',
            'bearer_token' => '',
            'enable_email_notifications' => '1',
            'primary_color' => '#00cc61',
            'success_message' => 'Randevunuz başarıyla oluşturuldu!'
        );
        
        if (!get_option('dentsoft_settings')) {
            add_option('dentsoft_settings', $default_settings);
        }
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('dentsoft_cleanup_old_appointments');
    }
    
    public function admin_menu() {
        add_menu_page(
            'DentSoft Randevu',
            'DentSoft',
            'manage_options',
            'dentsoft',
            array($this, 'admin_page'),
            plugin_dir_url(__FILE__) . 'assets/img/DS-Logo.png',
            30
        );
        
        add_submenu_page(
            'dentsoft',
            'Randevular',
            'Randevular',
            'manage_options',
            'dentsoft',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'dentsoft',
            'Ayarlar',
            'Ayarlar',
            'manage_options',
            'dentsoft-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_page() {
        include DENTSOFT_PLUGIN_DIR . 'admin/admin-page.php';
    }
    
    public function settings_page() {
        include DENTSOFT_PLUGIN_DIR . 'admin/settings-page.php';
    }
    
    public function register_settings() {
        register_setting(
            'dentsoft_settings',
            'dentsoft_settings',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => array(
                    'vkn' => '',
                    'api_url' => 'https://clinic.dentsoft.com.tr/Api/v1',
                    'bearer_token' => ''
                )
            )
        );
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        $current_settings = get_option('dentsoft_settings', array());
        
        if (isset($input['vkn'])) {
            $sanitized['vkn'] = sanitize_text_field($input['vkn']);
        }
        
        $sanitized['api_url'] = isset($current_settings['api_url']) 
            ? $current_settings['api_url'] 
            : 'https://clinic.dentsoft.com.tr/Api/v1';
        
        if (isset($input['bearer_token'])) {
            $sanitized['bearer_token'] = sanitize_text_field($input['bearer_token']);
        }
        
        if (isset($input['enable_email_notifications'])) {
            $sanitized['enable_email_notifications'] = absint($input['enable_email_notifications']);
        }
        
        if (isset($input['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        }
        
        if (isset($input['success_message'])) {
            $sanitized['success_message'] = sanitize_text_field($input['success_message']);
        }
        
        return $sanitized;
    }
    
    public function appointment_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default'
        ), $atts);
        
        ob_start();
        include DENTSOFT_PLUGIN_DIR . 'public/form-template.php';
        return ob_get_clean();
    }
    
    public function enqueue_scripts() {
        if (!$this->should_enqueue_scripts()) {
            return;
        }
        
        wp_enqueue_style('dentsoft-fontawesome', DENTSOFT_PLUGIN_URL . 'assets/fontawesome/css/all.min.css', array(), DENTSOFT_VERSION);
        wp_enqueue_style('dentsoft-select2', DENTSOFT_PLUGIN_URL . 'assets/plugins/select2-3.5.3/select2.css', array(), DENTSOFT_VERSION);
        wp_enqueue_style('dentsoft-select2', DENTSOFT_PLUGIN_URL . 'assets/plugins/select2-3.5.3/select2span.css', array(), DENTSOFT_VERSION);
        wp_enqueue_style('dentsoft-sweetalert2', DENTSOFT_PLUGIN_URL . 'assets/plugins/sweet-alert2/sweetalert2.min.css', array(), DENTSOFT_VERSION);
        wp_enqueue_style('dentsoft-main-styles', DENTSOFT_PLUGIN_URL . 'assets/css/main-styles.css', array(), DENTSOFT_VERSION);
        
        $settings = get_option('dentsoft_settings', array());
        $primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#00cc61';
        
        $primary_rgb = sscanf($primary_color, "#%02x%02x%02x");
        $primary_dark = sprintf("#%02x%02x%02x", 
            max(0, $primary_rgb[0] - 30), 
            max(0, $primary_rgb[1] - 30), 
            max(0, $primary_rgb[2] - 30)
        );
        $primary_light_r = min(255, $primary_rgb[0] + (255 - $primary_rgb[0]) * 0.9);
        $primary_light_g = min(255, $primary_rgb[1] + (255 - $primary_rgb[1]) * 0.9);
        $primary_light_b = min(255, $primary_rgb[2] + (255 - $primary_rgb[2]) * 0.9);
        
        $custom_css = ":root {
            --dentsoft-primary: {$primary_color};
            --dentsoft-primary-dark: {$primary_dark};
            --dentsoft-primary-light: rgba({$primary_light_r}, {$primary_light_g}, {$primary_light_b}, 0.2);
        }";
        
        wp_add_inline_style('dentsoft-main-styles', $custom_css);
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('dentsoft-blockui', DENTSOFT_PLUGIN_URL . 'assets/plugins/jquery.blockUI.js', array('jquery'), DENTSOFT_VERSION, true);
        wp_enqueue_script('dentsoft-select2', DENTSOFT_PLUGIN_URL . 'assets/plugins/select2-3.5.3/select2.3.4.1.js', array('jquery'), DENTSOFT_VERSION, true);
        wp_enqueue_script('dentsoft-select2', DENTSOFT_PLUGIN_URL . 'assets/plugins/select2-3.5.3/select2_locale_tr.js', array('jquery'), DENTSOFT_VERSION, true);
        wp_enqueue_script('dentsoft-select2', DENTSOFT_PLUGIN_URL . 'assets/plugins/select2-3.5.3/select2span.js', array('jquery'), DENTSOFT_VERSION, true);
        wp_enqueue_script('dentsoft-sweetalert2', DENTSOFT_PLUGIN_URL . 'assets/plugins/sweet-alert2/sweetalert2.min.js', array(), DENTSOFT_VERSION, true);
        wp_enqueue_script('dentsoft-app', DENTSOFT_PLUGIN_URL . 'assets/js/app.js', array('jquery', 'dentsoft-blockui', 'dentsoft-select2', 'dentsoft-sweetalert2'), DENTSOFT_VERSION, true);
        
        wp_localize_script('dentsoft-app', 'dentsoftConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dentsoft-nonce'),
            'vkn' => isset($settings['vkn']) ? $settings['vkn'] : '',
            'apiUrl' => isset($settings['api_url']) ? $settings['api_url'] : 'https://clinic.dentsoft.com.tr/Api/v1',
            'bearerToken' => isset($settings['bearer_token']) ? $settings['bearer_token'] : '',
            'pluginUrl' => DENTSOFT_PLUGIN_URL,
            'strings' => array(
                'loading' => 'Yükleniyor...',
                'error' => 'Bir hata oluştu',
                'success' => isset($settings['success_message']) ? $settings['success_message'] : 'İşlem başarılı!',
                'selectClinic' => 'Klinik Seçiniz',
                'selectDoctor' => 'Hekim Seçiniz',
                'confirm' => 'Onaylıyor musunuz?'
            )
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'dentsoft') === false) {
            return;
        }
        
        wp_enqueue_style('dentsoft-admin-bootstrap', DENTSOFT_PLUGIN_URL . 'assets/bootstrap/dist/css/bootstrap.min.css', array(), DENTSOFT_VERSION);
        wp_enqueue_style('dentsoft-admin-custom', DENTSOFT_PLUGIN_URL . 'assets/css/admin.css', array(), DENTSOFT_VERSION);
        
        wp_enqueue_script('dentsoft-admin-bootstrap', DENTSOFT_PLUGIN_URL . 'assets/bootstrap/dist/js/bootstrap.bundle.min.js', array('jquery'), DENTSOFT_VERSION, true);
        wp_enqueue_script('dentsoft-admin-sweetalert', DENTSOFT_PLUGIN_URL . 'assets/plugins/sweet-alert2/sweetalert2.min.js', array('jquery'), DENTSOFT_VERSION, true);
        
        wp_localize_script('jquery', 'dentsoftAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dentsoft-nonce')
        ));
    }
    
    private function should_enqueue_scripts() {
        global $post;
        
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dentsoft_appointment_form')) {
            return true;
        }
        
        return false;
    }
}

function dentsoft_plugin() {
    return DentSoft_Plugin::instance();
}

add_action('admin_head', function() {
    echo '
    <style>
        /* Menü ikonu (DentSoft ana menü) */
        #toplevel_page_dentsoft .wp-menu-image img {
            width: 25px;
            height: 25px;
            object-fit: contain;
        }
    </style>
    ';
});


dentsoft_plugin();
