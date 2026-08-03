<?php
/**
 * Çapa Randevu — işlem kaydı tablosu
 *
 * ⚠ KVKK NOTU (28 Tem 2026 kararı)
 * Bu tablo hasta kimlik bilgisi TUTMAZ: ad, soyad, telefon, e-posta,
 * doğum tarihi, TC / hasta numarası ve IP adresi buraya YAZILMAZ.
 * Bunların tamamı klinik randevu sisteminde zaten mevcuttur; ikinci bir
 * kopya yalnızca sızıntı yüzeyi açar.
 *
 * Tutulan tek ilişkilendirilebilir alan `pnr_no`'dur. Tek başına kimseyi
 * tanımlamaz, ancak randevu sisteminde aratıldığında hastaya ulaşır —
 * bu nedenle tablo "anonim" değil, "asgari veri" kategorisindedir ve
 * aydınlatma metni ile 90 günlük saklama süresine tabidir.
 *
 * ⚠ Tablo adında bilerek 'randevu', 'appointment' veya 'dentsoft'
 * geçmiyor: CapaOrtodonti_Site/site-customizations.php içindeki
 * capa_randevu_ozet() fonksiyonu bu kelimeleri içeren tabloları otomatik
 * keşfedip veri kaynağı sanıyor. 'caparv_log' o keşfe takılmaz.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Caparv_DB {

    const SCHEMA_VERSION = 1;
    const OPTION_KEY     = 'caparv_db_version';
    const SAKLAMA_GUN    = 90;

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'caparv_log';
    }

    /**
     * Sürüm kontrollü kurulum. Aktivasyon kancasına GÜVENMİYORUZ —
     * Git deploy eklentiyi etkinleştirmez, kanca hiç çalışmaz.
     */
    public static function maybe_install() {
        if ((int) get_option(self::OPTION_KEY, 0) === self::SCHEMA_VERSION) {
            return;
        }
        self::install();
        update_option(self::OPTION_KEY, self::SCHEMA_VERSION, false);
    }

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $t       = self::table();
        $collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$t} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            islem_kodu CHAR(9) NOT NULL DEFAULT '',
            pnr_no VARCHAR(32) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            durum VARCHAR(24) NOT NULL DEFAULT '',
            hata_kodu VARCHAR(64) NOT NULL DEFAULT '',
            hata_ozeti VARCHAR(255) NOT NULL DEFAULT '',
            randevu_tipi VARCHAR(16) NOT NULL DEFAULT '',
            klinik_adi VARCHAR(128) NOT NULL DEFAULT '',
            hekim_adi VARCHAR(128) NOT NULL DEFAULT '',
            randevu_tarihi DATE NULL DEFAULT NULL,
            randevu_saati VARCHAR(8) NOT NULL DEFAULT '',
            adim TINYINT UNSIGNED NOT NULL DEFAULT 0,
            cihaz VARCHAR(16) NOT NULL DEFAULT '',
            test TINYINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY k_created (created_at),
            KEY k_durum (durum),
            KEY k_pnr (pnr_no),
            KEY k_test (test)
        ) {$collate};";

        dbDelta($sql);
    }

    /** 9 karakterlik, okunabilir işlem kodu: A7F3-2K9M */
    public static function islem_kodu() {
        $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // I, O, 0, 1 yok — telefonda karışıyor
        $out = '';
        for ($i = 0; $i < 8; $i++) {
            $out .= $abc[wp_rand(0, strlen($abc) - 1)];
            if ($i === 3) { $out .= '-'; }
        }
        return $out;
    }

    /**
     * Hata metnini süz. Ham API mesajı hasta adı veya telefon içerebilir;
     * onu olduğu gibi loglarsak kişisel veri tutmadığımızı sanırken tutarız.
     */
    public static function suz($mesaj) {
        $m = is_string($mesaj) ? $mesaj : '';
        $m = wp_strip_all_tags($m);
        // telefon benzeri diziler
        $m = preg_replace('/\+?\d[\d\s().-]{8,}\d/u', '[numara]', $m);
        // e-posta
        $m = preg_replace('/[^\s@]+@[^\s@]+\.[^\s@]+/u', '[eposta]', $m);
        // 11 haneli kimlik numarası
        $m = preg_replace('/\b\d{11}\b/u', '[kimlik]', $m);
        $m = trim(preg_replace('/\s+/u', ' ', (string) $m));
        return function_exists('mb_substr') ? mb_substr($m, 0, 255) : substr($m, 0, 255);
    }

    private static function cihaz() {
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        if ($ua === '') { return 'bilinmiyor'; }
        return preg_match('/Mobi|Android|iPhone|iPad/i', $ua) ? 'mobil' : 'masaustu';
    }

    /**
     * Tek satır yaz. Kimlik alanı kabul etmez — çağıran ne gönderirse
     * göndersin, yalnız buradaki beyaz liste kaydedilir.
     */
    public static function kaydet($veri) {
        global $wpdb;

        $kod = !empty($veri['islem_kodu']) ? $veri['islem_kodu'] : self::islem_kodu();

        $satir = array(
            'islem_kodu'     => substr((string) $kod, 0, 9),
            'pnr_no'         => substr(sanitize_text_field((string) self::al($veri, 'pnr_no')), 0, 32),
            'created_at'     => current_time('mysql'),
            'durum'          => substr(sanitize_key((string) self::al($veri, 'durum')), 0, 24),
            'hata_kodu'      => substr(sanitize_text_field((string) self::al($veri, 'hata_kodu')), 0, 64),
            'hata_ozeti'     => self::suz(self::al($veri, 'hata_ozeti')),
            'randevu_tipi'   => substr(sanitize_text_field((string) self::al($veri, 'randevu_tipi')), 0, 16),
            'klinik_adi'     => substr(sanitize_text_field((string) self::al($veri, 'klinik_adi')), 0, 128),
            'hekim_adi'      => substr(sanitize_text_field((string) self::al($veri, 'hekim_adi')), 0, 128),
            'randevu_tarihi' => self::tarih(self::al($veri, 'randevu_tarihi')),
            'randevu_saati'  => substr(sanitize_text_field((string) self::al($veri, 'randevu_saati')), 0, 8),
            'adim'           => max(0, min(255, (int) self::al($veri, 'adim'))),
            'cihaz'          => self::cihaz(),
            'test'           => !empty($veri['test']) ? 1 : 0,
        );

        // ⚠ Yazma BASARISIZ olsa bile cagiran akis DURMAZ. Kullanicinin
        // gordugu sonuc, yerel kayit adimina asla bagli olmamali
        // (28 Tem 2026 arizasinin dersi).
        $ok = $wpdb->insert(self::table(), $satir);
        if ($ok === false && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[caparv] kayit yazilamadi: ' . $wpdb->last_error);
        }
        return $kod;
    }

    private static function al($a, $k) {
        return isset($a[$k]) ? $a[$k] : '';
    }

    private static function tarih($v) {
        $v = is_string($v) ? trim($v) : '';
        if ($v === '') { return null; }
        $ts = strtotime($v);
        return $ts ? gmdate('Y-m-d', $ts) : null;
    }

    /** 90 günden eski kayıtları sil. */
    public static function purge_old() {
        global $wpdb;
        $t     = self::table();
        $sinir = gmdate('Y-m-d H:i:s', time() - (self::SAKLAMA_GUN * DAY_IN_SECONDS));
        $wpdb->query($wpdb->prepare("DELETE FROM {$t} WHERE created_at < %s", $sinir));
    }

    /** Test kayıtlarını tek seferde temizle (admin ekranından). */
    public static function purge_test() {
        global $wpdb;
        $t = self::table();
        return (int) $wpdb->query("DELETE FROM {$t} WHERE test = 1");
    }
}
