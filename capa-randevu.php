<?php
/*
Plugin Name: CapaOrtodonti - Online Randevu
Plugin URI: https://capaortodonti.com/
Description: Çapa Ortodonti online randevu sistemi. Randevu talepleri klinik randevu sistemine iletilir; hasta kimlik bilgileri WordPress'te saklanmaz.
Version: 1.0.0
Requires at least: 6.0
Requires PHP: 7.4
Author: Serdar Erkesen
Author URI: https://capaortodonti.com/
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: capa-randevu
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CAPARV_VERSION', '1.0.0');
define('CAPARV_DIR', plugin_dir_path(__FILE__));
define('CAPARV_URL', plugin_dir_url(__FILE__));
define('CAPARV_FILE', __FILE__);

final class Caparv_Plugin {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once CAPARV_DIR . 'includes/class-db.php';
        require_once CAPARV_DIR . 'includes/class-ajax.php';
    }

    private function init_hooks() {
        // ⚠ Tablo kurulumu BILEREK register_activation_hook'a BAGLANMADI.
        // cPanel Git deploy dosyalari kopyalar ama eklentiyi "etkinlestirmez";
        // aktivasyon kancasi calismaz ve tablo sessizce olusmaz.
        // (28 Tem 2026 arizasinin kok nedeni buydu.) Bunun yerine her yuklemede
        // surum kontrollu kurulum yapiyoruz — maliyeti bir option okumasi.
        add_action('plugins_loaded', array('Caparv_DB', 'maybe_install'), 5);

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        add_shortcode('caparv_randevu_formu', array($this, 'form_shortcode'));

        // Saklama suresi: kayitlar 90 gunden eski olunca silinir (KVKK
        // "gerektigi kadar" ilkesi). Tablonun sonsuza kadar sismesini de onler.
        add_action('caparv_gunluk_temizlik', array('Caparv_DB', 'purge_old'));
    }

    public function init() {
        if (!wp_next_scheduled('caparv_gunluk_temizlik')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'caparv_gunluk_temizlik');
        }
    }

    /* ---------------------------------------------------------------- ayarlar */

    public static function settings() {
        $d = array(
            'vkn'                        => '',
            'api_url'                    => 'https://clinic.dentsoft.com.tr/Api/v1',
            'bearer_token'               => '',
            'staff_email'                => get_option('admin_email'),
            'bildirim_adresleri'         => '',
            'enable_email_notifications' => 1,
            'primary_color'              => '#00cc61',
            'success_message'            => 'İşlem başarılı!',
            'test_modu'                  => 0,
            'sablonlar'                  => array(),
        );
        $s = get_option('caparv_settings', array());
        return wp_parse_args(is_array($s) ? $s : array(), $d);
    }

    /**
     * Bildirim gonderilecek adresler. Ayar bos ise geriye donuk uyumluluk
     * icin eski tek-adres ayari + klinik adresi kullanilir; boylece
     * guncelleme aninda davranis degismez.
     */
    public static function bildirim_adresleri() {
        $s   = self::settings();
        $ham = trim((string) $s['bildirim_adresleri']);
        if ($ham === '') {
            $ham = ($s['staff_email'] ?: get_option('admin_email')) . ', info@capaortodonti.com';
        }
        $out = array();
        foreach (preg_split('/[,;\s]+/', $ham) as $a) {
            $a = sanitize_email(trim($a));
            if ($a && is_email($a) && !in_array($a, $out, true)) { $out[] = $a; }
        }
        if (empty($out)) { $out[] = get_option('admin_email'); }
        return $out;
    }


    /* ------------------------------------------------------- e-posta şablonları */

    /**
     * Varsayılan e-posta şablonları. Ayarlarda karşılığı boş bırakılırsa
     * bu metinler kullanılır — yani bir alanı temizlemek "fabrika ayarına
     * dön" demektir.
     *
     * Gövdede kullanılabilen yer tutucular:
     *   {detay_tablosu} {buton} {klinik} {klinik_adres} {klinik_telefon}
     *   {hasta_adi} {hekim} {tarih} {pnr} {islem_kodu}
     *   {telefon} {eposta} {tc} {dogum} {not}
     * Karşılığı olmayan yer tutucular gönderimden önce silinir.
     */
    public static function varsayilan_sablonlar() {
        $kucuk = 'margin:18px 0 0;color:#666666;font-size:13px;line-height:1.6;';
        return array(
            'genel_hasta' => array(
                'ad'      => 'Genel Randevu talebi — hastaya',
                'konu'    => 'Genel Randevu Talebiniz Alındı',
                'govde'   => '<p style="margin:0 0 14px;">Sayın <strong>{hasta_adi}</strong>,</p>'
                           . '<p style="margin:0 0 18px;">Genel randevu talebiniz tarafımıza ulaşmıştır. Tercih ettiğiniz tarih ve saat, hekimlerimizin uygunluk durumuna göre değişiklik gösterebilir. İhtiyacınıza en uygun hekimimize ve kesin randevu zamanına birlikte karar verebilmek için, en kısa sürede sizinle iletişime geçeceğiz.</p>'
                           . '{detay_tablosu}'
                           . '<p style="' . $kucuk . '">{klinik}</p>',
            ),
            'genel_personel' => array(
                'ad'      => 'Genel Randevu talebi — personele',
                'konu'    => 'Genel Randevu Talebi - {hasta_adi}',
                'govde'   => '<p style="margin:0 0 18px;">Yeni bir <strong>Genel Randevu</strong> talebi geldi. Bu talep klinik randevu sistemine kaydedilmemiştir; lütfen hastayı arayıp uygun saati teyit edin.</p>'
                           . '{detay_tablosu}',
            ),
            'randevu_hasta' => array(
                'ad'      => 'Randevu oluşturuldu — hastaya',
                'konu'    => 'Randevunuz Oluşturuldu',
                'govde'   => '<p style="margin:0 0 14px;">Sayın <strong>{hasta_adi}</strong>,</p>'
                           . '<p style="margin:0 0 18px;">Randevunuz başarıyla oluşturulmuştur. Detaylarınız aşağıdadır:</p>'
                           . '{detay_tablosu}{buton}'
                           . '<p style="' . $kucuk . '">{klinik}<br>{klinik_adres}<br>{klinik_telefon}</p>',
            ),
            'randevu_personel' => array(
                'ad'      => 'Randevu oluşturuldu — personele',
                'konu'    => 'Online Randevu Oluşturuldu!',
                'govde'   => '<p style="margin:0 0 18px;">Yeni bir online randevu oluşturuldu.</p>'
                           . '{detay_tablosu}{buton}',
            ),
            'iptal_hasta' => array(
                'ad'      => 'Randevu iptali — hastaya',
                'konu'    => 'Randevunuz İptal Edildi',
                'govde'   => '<p style="margin:0 0 14px;">Sayın <strong>{hasta_adi}</strong>,</p>'
                           . '<p style="margin:0 0 18px;">Aşağıdaki randevunuz iptal edilmiştir:</p>'
                           . '{detay_tablosu}'
                           . '<p style="' . $kucuk . '">Dilediğiniz zaman web sitemizden yeni randevu alabilirsiniz.</p>',
            ),
            'iptal_personel' => array(
                'ad'      => 'Randevu iptali — personele',
                'konu'    => 'Randevu İptali - {pnr}',
                'govde'   => '<p style="margin:0 0 18px;">Bir randevu iptal edildi.</p>{not}{detay_tablosu}',
            ),
        );
    }

    /** Bir şablonun geçerli değerlerini döndürür (ayar boşsa varsayılan). */
    public static function sablon($anahtar) {
        $var = self::varsayilan_sablonlar();
        if (!isset($var[$anahtar])) { return array('konu' => '', 'govde' => ''); }
        $s   = self::settings();
        $kay = isset($s['sablonlar'][$anahtar]) && is_array($s['sablonlar'][$anahtar]) ? $s['sablonlar'][$anahtar] : array();
        $out = array();
        foreach (array('konu', 'govde') as $alan) {
            $v = isset($kay[$alan]) ? trim((string) $kay[$alan]) : '';
            $out[$alan] = ($v !== '') ? $v : $var[$anahtar][$alan];
        }
        return $out;
    }

    /** Yer tutucuları doldurur, karşılığı olmayanları siler. */
    public static function doldur($metin, $degerler) {
        foreach ($degerler as $k => $v) {
            $metin = str_replace('{' . $k . '}', (string) $v, $metin);
        }
        return preg_replace('/\{[a-z_]+\}/u', '', (string) $metin);
    }

    public static function test_modu() {
        $s = self::settings();
        return !empty($s['test_modu']);
    }

    public function register_settings() {
        register_setting('caparv_settings_group', 'caparv_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    public function sanitize_settings($input) {
        $out = array();
        if (isset($input['vkn']))          $out['vkn'] = sanitize_text_field($input['vkn']);
        if (isset($input['api_url']))      $out['api_url'] = esc_url_raw($input['api_url']);
        if (isset($input['bearer_token'])) $out['bearer_token'] = sanitize_text_field($input['bearer_token']);
        if (isset($input['staff_email']))  $out['staff_email'] = sanitize_email($input['staff_email']);
        if (isset($input['bildirim_adresleri'])) {
            $temiz = array();
            foreach (preg_split('/[,;\s]+/', (string) $input['bildirim_adresleri']) as $a) {
                $a = sanitize_email(trim($a));
                if ($a && is_email($a) && !in_array($a, $temiz, true)) { $temiz[] = $a; }
            }
            $out['bildirim_adresleri'] = implode(', ', $temiz);
        }
        if (isset($input['primary_color']))$out['primary_color'] = sanitize_hex_color($input['primary_color']);
        if (isset($input['success_message'])) $out['success_message'] = sanitize_text_field($input['success_message']);
        $out['enable_email_notifications'] = !empty($input['enable_email_notifications']) ? 1 : 0;
        $out['test_modu'] = !empty($input['test_modu']) ? 1 : 0;

        // E-posta şablonları. Gövde HTML kabul eder (wp_kses_post ile süzülür);
        // bir alan boş bırakılırsa varsayılana geri döner.
        $out['sablonlar'] = array();
        if (isset($input['sablonlar']) && is_array($input['sablonlar'])) {
            foreach (self::varsayilan_sablonlar() as $k => $v) {
                if (!isset($input['sablonlar'][$k])) { continue; }
                $g = $input['sablonlar'][$k];
                $out['sablonlar'][$k] = array(
                    'konu'  => sanitize_text_field(isset($g['konu']) ? $g['konu'] : ''),
                    'govde' => wp_kses_post(isset($g['govde']) ? $g['govde'] : ''),
                );
            }
        }
        return $out;
    }

    /* ------------------------------------------------------------------ menu */

    public function admin_menu() {
        add_menu_page('Çapa Randevu', 'Çapa Randevu', 'manage_options', 'caparv',
            array($this, 'render_admin_page'), 'dashicons-calendar-alt', 26);
        add_submenu_page('caparv', 'İşlem Kayıtları', 'İşlem Kayıtları', 'manage_options', 'caparv',
            array($this, 'render_admin_page'));
        add_submenu_page('caparv', 'Ayarlar', 'Ayarlar', 'manage_options', 'caparv-ayarlar',
            array($this, 'render_settings_page'));
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Yetkiniz yok.'); }
        include CAPARV_DIR . 'admin/admin-page.php';
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) { wp_die('Yetkiniz yok.'); }
        include CAPARV_DIR . 'admin/settings-page.php';
    }

    /* -------------------------------------------------------------- shortcode */

    public function form_shortcode($atts) {
        ob_start();
        include CAPARV_DIR . 'public/form-template.php';
        return ob_get_clean();
    }

    /* --------------------------------------------------------------- enqueue */

    private function should_enqueue() {
        global $post;
        if (!is_a($post, 'WP_Post')) { return false; }
        if (has_shortcode($post->post_content, 'caparv_randevu_formu')) { return true; }
        // Elementor: shortcode widget'i post_content'te degil _elementor_data'da durur.
        $ed = get_post_meta($post->ID, '_elementor_data', true);
        if (is_string($ed) && $ed !== '' && strpos($ed, 'caparv_randevu_formu') !== false) {
            return true;
        }
        return false;
    }

    public function enqueue_scripts() {
        if (!$this->should_enqueue()) { return; }

        $s = self::settings();
        $mt = function ($rel) {
            $p = CAPARV_DIR . $rel;
            return @filemtime($p) ?: CAPARV_VERSION;
        };

        wp_enqueue_style('caparv-icons',   CAPARV_URL . 'assets/css/icons.css', array(), $mt('assets/css/icons.css'));
        wp_enqueue_style('caparv-select2', CAPARV_URL . 'assets/vendor/select2/select2.min.css', array(), CAPARV_VERSION);
        wp_enqueue_style('caparv-swal',    CAPARV_URL . 'assets/vendor/sweetalert2/sweetalert2.min.css', array(), CAPARV_VERSION);
        wp_enqueue_style('caparv-main',    CAPARV_URL . 'assets/css/main-styles.css', array(), $mt('assets/css/main-styles.css'));

        $c  = $s['primary_color'] ?: '#00cc61';
        $rgb = sscanf($c, "#%02x%02x%02x");
        if (!is_array($rgb) || count($rgb) < 3) { $rgb = array(0, 204, 97); }
        $dark = sprintf("#%02x%02x%02x", max(0, $rgb[0] - 30), max(0, $rgb[1] - 30), max(0, $rgb[2] - 30));
        $lr = min(255, $rgb[0] + (255 - $rgb[0]) * 0.9);
        $lg = min(255, $rgb[1] + (255 - $rgb[1]) * 0.9);
        $lb = min(255, $rgb[2] + (255 - $rgb[2]) * 0.9);
        wp_add_inline_style('caparv-main', ":root{--caparv-primary:{$c};--caparv-primary-dark:{$dark};--caparv-primary-light:rgba({$lr},{$lg},{$lb},0.2);}");

        wp_enqueue_script('jquery');
        wp_enqueue_script('caparv-blockui',    CAPARV_URL . 'assets/vendor/jquery.blockUI.js', array('jquery'), CAPARV_VERSION, true);
        wp_enqueue_script('caparv-select2',    CAPARV_URL . 'assets/vendor/select2/select2.full.min.js', array('jquery'), CAPARV_VERSION, true);
        wp_enqueue_script('caparv-select2-tr', CAPARV_URL . 'assets/vendor/select2/i18n/tr.js', array('caparv-select2'), CAPARV_VERSION, true);
        wp_enqueue_script('caparv-swal',       CAPARV_URL . 'assets/vendor/sweetalert2/sweetalert2.min.js', array(), CAPARV_VERSION, true);
        wp_enqueue_script('caparv-app',        CAPARV_URL . 'assets/js/app.js',
            array('jquery', 'caparv-blockui', 'caparv-select2', 'caparv-select2-tr', 'caparv-swal'),
            $mt('assets/js/app.js'), true);

        wp_localize_script('caparv-app', 'caparvConfig', array(
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('caparv-nonce'),
            // Anonim sayac ucu — hasta verisi TASIMAZ. Uc CapaOrtodonti_Site/
            // site-customizations.php icinde tanimli, bu eklentide degil.
            'sayUrl'      => esc_url_raw(rest_url('capa/v1/randevu-say')),
            'sayNonce'    => wp_create_nonce('wp_rest'),
            'vkn'         => $s['vkn'],
            'apiUrl'      => $s['api_url'],
            'bearerToken' => $s['bearer_token'],
            'pluginUrl'   => CAPARV_URL,
            // Test modu: olcum ATESLENIR ama 'test' etiketiyle gider, boylece
            // hattin calistigini canliya almadan gorur, veriyi de kirletmeyiz.
            'testModu'    => self::test_modu() ? 1 : 0,
            'strings'     => array(
                'loading'      => 'Yükleniyor...',
                'error'        => 'Bir hata oluştu',
                'success'      => $s['success_message'],
                'selectClinic' => 'Klinik Seçiniz',
                'selectDoctor' => 'Hekim Seçiniz',
                'confirm'      => 'Onaylıyor musunuz?',
            ),
        ));
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'caparv') === false) { return; }
        wp_enqueue_style('caparv-admin', CAPARV_URL . 'assets/css/admin.css', array(), @filemtime(CAPARV_DIR . 'assets/css/admin.css') ?: CAPARV_VERSION);
        wp_enqueue_style('caparv-icons', CAPARV_URL . 'assets/css/icons.css', array(), CAPARV_VERSION);
        wp_localize_script('jquery', 'caparvAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('caparv-nonce'),
        ));
    }
}

function caparv_plugin() {
    return Caparv_Plugin::instance();
}

caparv_plugin();
