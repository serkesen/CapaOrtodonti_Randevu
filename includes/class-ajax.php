<?php
if (!defined('ABSPATH')) {
    exit;
}

class Caparv_Ajax_Handlers {
    
    public function __construct() {
        add_action('wp_ajax_caparv_save_appointment', array($this, 'save_appointment'));
        add_action('wp_ajax_nopriv_caparv_save_appointment', array($this, 'save_appointment'));
        
        add_action('wp_ajax_caparv_get_appointments', array($this, 'get_appointments'));
        add_action('wp_ajax_caparv_purge_test', array($this, 'purge_test'));
        
        add_action('wp_ajax_caparv_genel_randevu', array($this, 'genel_randevu_notify'));
        add_action('wp_ajax_nopriv_caparv_genel_randevu', array($this, 'genel_randevu_notify'));
        add_action('wp_ajax_caparv_cancel_appointment', array($this, 'cancel_appointment_notify'));
        add_action('wp_ajax_nopriv_caparv_cancel_appointment', array($this, 'cancel_appointment_notify'));
    }
    
    private function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'caparv-nonce')) {
            wp_send_json_error(array(
                'message' => 'Güvenlik doğrulaması başarısız.'
            ));
            wp_die();
        }
    }

    /**
     * Mail alicilari. TEST MODUNDA hasta adresine e-posta GITMEZ; her sey
     * personel adresine yonlenir. Boylece test randevulari gercek bir
     * hastanin kutusuna dusemez.
     */
    private function hasta_alici($hasta_email) {
        if (Caparv_Plugin::test_modu()) {
            $l = Caparv_Plugin::bildirim_adresleri();
            return $l[0];
        }
        return $hasta_email;
    }

    /**
     * Personel bildirim alicilari. Ayarlardaki listeden gelir.
     * TEST MODUNDA yalniz ILK adrese gonderilir — test bildirimleri
     * klinik kutusuna dusmesin diye.
     */
    private function personel_alici() {
        $l = Caparv_Plugin::bildirim_adresleri();
        return Caparv_Plugin::test_modu() ? array($l[0]) : $l;
    }

    /** Şablondan e-posta üretir ve gönderir. */
    private function sablonla_gonder($anahtar, $alicilar, $degerler) {
        if (empty($alicilar)) { return; }
        $t = Caparv_Plugin::sablon($anahtar);
        $konu  = Caparv_Plugin::doldur($t['konu'], $degerler);
        $govde = Caparv_Plugin::doldur($t['govde'], $degerler);
        $html  = $this->caparv_email_shell($govde);
        wp_mail($alicilar, $konu, $html, array('Content-Type: text/html; charset=UTF-8'));
    }

    /** Detay tablosunu satır dizisinden kurar. */
    private function detay_tablosu($satirlar) {
        $r = '';
        foreach ($satirlar as $etiket => $deger) {
            if ($deger === '' || $deger === null) { continue; }
            $r .= $this->caparv_email_row($etiket, esc_html($deger));
        }
        if ($r === '') { return ''; }
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">' . $r . '</table>';
    }

    private function test_mi() {
        return Caparv_Plugin::test_modu() ? 1 : 0;
    }
    
    public function save_appointment() {
        $this->verify_nonce();
        
        global $wpdb;
        
        $required_fields = array('patient_number', 'pnr_no', 'patient_name', 'patient_surname', 'patient_phone');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' alanı zorunludur.',
                    'field' => $field
                ));
                return;
            }
        }
        
        $patient_birthday = null;
        if (!empty($_POST['patient_birthday'])) {
            $birthday = sanitize_text_field(wp_unslash($_POST['patient_birthday']));
            $patient_birthday = date('Y-m-d', strtotime($birthday));
        }
        
        $data = array(
            'patient_number' => sanitize_text_field(wp_unslash($_POST['patient_number'])),
            'patient_name' => sanitize_text_field(wp_unslash($_POST['patient_name'])),
            'patient_surname' => sanitize_text_field(wp_unslash($_POST['patient_surname'])),
            'patient_phone' => sanitize_text_field(wp_unslash($_POST['patient_phone'])),
            'patient_birthday' => $patient_birthday,
            'patient_email' => !empty($_POST['patient_email']) ? sanitize_email(wp_unslash($_POST['patient_email'])) : null,
            'clinic_name' => sanitize_text_field(wp_unslash($_POST['clinic_name'])),
            'clinic_address' => sanitize_textarea_field(wp_unslash($_POST['clinic_address'])),
            'clinic_phone' => sanitize_text_field(wp_unslash($_POST['clinic_phone'])),
            'doctor_name' => sanitize_text_field(wp_unslash($_POST['doctor_name'])),
            'pnr_no' => sanitize_text_field(wp_unslash($_POST['pnr_no'])),
            'appointment_date' => sanitize_text_field(wp_unslash($_POST['appointment_date'])),
            'appointment_status' => !empty($_POST['appointment_status']) ? sanitize_text_field(wp_unslash($_POST['appointment_status'])) : 'pending'
        );
        
        // KVKK: hasta kimlik verisi buraya YAZILMAZ. Yalniz islem izi tutulur.
        // Bu noktaya gelindiyse randevu klinik sisteminde ZATEN olusmustur;
        // yerel kayit adiminin basarisiz olmasi akisi DURDURMAMALIDIR.
        $islem_kodu = Caparv_DB::kaydet(array(
            'pnr_no'         => $data['pnr_no'],
            'durum'          => 'basarili',
            'randevu_tipi'   => 'dentsoft',
            'klinik_adi'     => $data['clinic_name'],
            'hekim_adi'      => $data['doctor_name'],
            'randevu_tarihi' => $data['appointment_date'],
            'randevu_saati'  => date('H:i', strtotime($data['appointment_date'])),
            'adim'           => 5,
            'test'           => $this->test_mi(),
        ));

        $appointment_id = 0;

        $patient_link = isset($_POST['appointment_link']) ? esc_url_raw(wp_unslash($_POST['appointment_link'])) : '';
        $staff_link = isset($_POST['appointment_staff_link']) ? esc_url_raw(wp_unslash($_POST['appointment_staff_link'])) : '';
        $this->send_email_notifications($data, $patient_link, $staff_link);
        
        do_action('caparv_randevu_olusturuldu', $islem_kodu, $data);

        wp_send_json_success(array(
            'message'     => 'Randevunuz başarıyla kaydedildi!',
            'islem_kodu'  => $islem_kodu,
            'data'        => $data
        ));
    }
    
    /**
     * Islem kayitlari (operasyon izleme). Hasta listesi DEGILDIR —
     * kimlik alani tutulmaz. Klinik randevu defteri kendi panelindedir.
     */
    public function get_appointments() {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Bu işlem için yetkiniz yok.'));
            return;
        }

        global $wpdb;
        $t = Caparv_DB::table();

        $sayfa  = isset($_POST['sayfa']) ? max(1, (int) $_POST['sayfa']) : 1;
        $adet   = 25;
        $offset = ($sayfa - 1) * $adet;

        $where  = '1=1';
        $params = array();

        $durum = isset($_POST['durum']) ? sanitize_key(wp_unslash($_POST['durum'])) : '';
        if ($durum !== '' && $durum !== 'hepsi') {
            $where   .= ' AND durum = %s';
            $params[] = $durum;
        }
        if (isset($_POST['test']) && $_POST['test'] !== 'hepsi') {
            $where   .= ' AND test = %d';
            $params[] = (int) $_POST['test'];
        }

        $sayim = "SELECT COUNT(*) FROM {$t} WHERE {$where}";
        $total = (int) ($params ? $wpdb->get_var($wpdb->prepare($sayim, $params)) : $wpdb->get_var($sayim));

        $sorgu = "SELECT * FROM {$t} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        $rows  = $wpdb->get_results($wpdb->prepare($sorgu, array_merge($params, array($adet, $offset))), ARRAY_A);

        wp_send_json_success(array(
            'kayitlar' => is_array($rows) ? $rows : array(),
            'toplam'   => $total,
            'sayfa'    => $sayfa,
            'sayfalar' => (int) ceil($total / $adet),
        ));
    }

    /** Test kayitlarini toplu sil. */
    public function purge_test() {
        $this->verify_nonce();
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Bu işlem için yetkiniz yok.'));
            return;
        }
        $n = Caparv_DB::purge_test();
        wp_send_json_success(array('silinen' => $n));
    }

    public function cancel_appointment_notify() {
        $this->verify_nonce();

        $pnr = isset($_POST['pnr_no']) ? sanitize_text_field(wp_unslash($_POST['pnr_no'])) : '';
        if (empty($pnr)) {
            wp_send_json_error(array('message' => 'PNR gerekli.'));
            return;
        }

        // Hasta bilgisi yerelde tutulmadigi icin iptal maili yalniz
        // personele gider; hastaya bildirimi klinik sistemi yapar.
        // ⚠ patient_email ve patient_name YALNIZ bildirim gondermek icin alinir.
        // Caparv_DB::kaydet() bunlari kabul etmez — beyaz liste disinda kaldiklari
        // icin tabloya yazilmalari sema geregi imkansizdir.
        $row = array(
            'clinic_name'      => isset($_POST['clinic_name']) ? sanitize_text_field(wp_unslash($_POST['clinic_name'])) : '',
            'doctor_name'      => isset($_POST['doctor_name']) ? sanitize_text_field(wp_unslash($_POST['doctor_name'])) : '',
            'appointment_date' => isset($_POST['appointment_date']) ? sanitize_text_field(wp_unslash($_POST['appointment_date'])) : '',
            'appointment_time' => isset($_POST['appointment_time']) ? sanitize_text_field(wp_unslash($_POST['appointment_time'])) : '',
            'patient_email'    => isset($_POST['patient_email']) ? sanitize_email(wp_unslash($_POST['patient_email'])) : '',
            'patient_name'     => isset($_POST['patient_name']) ? sanitize_text_field(wp_unslash($_POST['patient_name'])) : '',
        );

        Caparv_DB::kaydet(array(
            'pnr_no'         => $pnr,
            'durum'          => 'iptal',
            'randevu_tipi'   => 'dentsoft',
            'klinik_adi'     => $row['clinic_name'],
            'hekim_adi'      => $row['doctor_name'],
            'randevu_tarihi' => $row['appointment_date'],
            'randevu_saati'  => $row['appointment_time'],
            'test'           => $this->test_mi(),
        ));

        $this->send_cancellation_notifications($pnr, $row);

        wp_send_json_success(array('message' => 'Iptal bildirimi islendi.'));
    }

    private function send_cancellation_notifications($pnr, $row) {
        $s = Caparv_Plugin::settings();
        if (empty($s['enable_email_notifications'])) { return; }

        $clinic = !empty($row['clinic_name']) ? $row['clinic_name'] : 'Çapa Ortodonti';
        $doctor = !empty($row['doctor_name']) ? $row['doctor_name'] : '-';
        $tarih  = !empty($row['appointment_date']) ? date('d.m.Y H:i', strtotime($row['appointment_date'])) : '-';

        $tablo = $this->detay_tablosu(array(
            'Klinik'       => $clinic,
            'Hekim'        => $doctor,
            'Tarih & Saat' => $tarih,
            'PNR No'       => $pnr,
        ));

        $ortak = array(
            'klinik' => esc_html($clinic), 'hekim' => esc_html($doctor),
            'tarih' => esc_html($tarih),   'pnr' => esc_html($pnr),
            'hasta_adi' => esc_html(!empty($row['patient_name']) ? $row['patient_name'] : ''),
            'detay_tablosu' => $tablo, 'buton' => '',
        );

        $hasta_mail = !empty($row['patient_email']) ? $row['patient_email'] : '';
        if ($hasta_mail && is_email($hasta_mail)) {
            $this->sablonla_gonder('iptal_hasta', $this->hasta_alici($hasta_mail), $ortak);
        }

        $not = $hasta_mail ? '' : '<p style="margin:0 0 14px;color:#8a2424;font-size:13px;">Not: Hastanın e-posta adresi randevu sorgusundan alınamadığı için hastaya bildirim gönderilemedi.</p>';
        $this->sablonla_gonder('iptal_personel', $this->personel_alici(), array_merge($ortak, array('not' => $not)));
    }

    public function genel_randevu_notify() {
        $this->verify_nonce();

        $required_fields = array('patient_number', 'patient_name', 'patient_surname', 'patient_phone');
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => ucfirst(str_replace('_', ' ', $field)) . ' alanı zorunludur.',
                    'field' => $field
                ));
                return;
            }
        }

        $data = array(
            'patient_number'  => sanitize_text_field(wp_unslash($_POST['patient_number'])),
            'patient_name'    => sanitize_text_field(wp_unslash($_POST['patient_name'])),
            'patient_surname' => sanitize_text_field(wp_unslash($_POST['patient_surname'])),
            'patient_phone'   => sanitize_text_field(wp_unslash($_POST['patient_phone'])),
            'patient_email'   => !empty($_POST['patient_email']) ? sanitize_email(wp_unslash($_POST['patient_email'])) : '',
            'patient_birthday'=> !empty($_POST['patient_birthday']) ? sanitize_text_field(wp_unslash($_POST['patient_birthday'])) : '',
            'clinic_name'     => !empty($_POST['clinic_name']) ? sanitize_text_field(wp_unslash($_POST['clinic_name'])) : 'Çapa Ortodonti',
            'appointment_date'=> !empty($_POST['appointment_date']) ? sanitize_text_field(wp_unslash($_POST['appointment_date'])) : '',
            'appointment_time'=> !empty($_POST['appointment_time']) ? sanitize_text_field(wp_unslash($_POST['appointment_time'])) : '',
        );

        $islem_kodu = Caparv_DB::kaydet(array(
            'durum'          => 'basarili',
            'randevu_tipi'   => 'genel',
            'klinik_adi'     => $data['clinic_name'],
            'hekim_adi'      => 'Genel Randevu',
            'randevu_tarihi' => $data['appointment_date'],
            'randevu_saati'  => $data['appointment_time'],
            'adim'           => 5,
            'test'           => $this->test_mi(),
        ));

        $this->send_genel_randevu_notifications($data);

        wp_send_json_success(array(
            'message'    => 'Talebiniz alındı.',
            'islem_kodu' => $islem_kodu
        ));
    }

    private function send_genel_randevu_notifications($data) {
        $s = Caparv_Plugin::settings();
        if (empty($s['enable_email_notifications'])) { return; }

        $clinic = !empty($data['clinic_name']) ? $data['clinic_name'] : 'Çapa Ortodonti';
        $ad     = trim($data['patient_name'] . ' ' . $data['patient_surname']);
        $tercih = !empty($data['appointment_date']) ? trim($data['appointment_date']) : '-';

        $ortak = array(
            'klinik' => esc_html($clinic), 'hasta_adi' => esc_html($ad),
            'tarih' => esc_html($tercih), 'hekim' => 'Genel Randevu',
            'telefon' => esc_html($data['patient_phone']),
            'eposta' => esc_html($data['patient_email'] ?: '-'),
            'tc' => esc_html($data['patient_number']),
            'dogum' => esc_html($data['patient_birthday']),
            'buton' => '', 'pnr' => '-',
        );

        if (!empty($data['patient_email'])) {
            $this->sablonla_gonder('genel_hasta', $this->hasta_alici($data['patient_email']),
                array_merge($ortak, array('detay_tablosu' => $this->detay_tablosu(array(
                    'Klinik'                     => $clinic,
                    'Talep Türü'                 => 'Genel Randevu',
                    'Tercih Edilen Tarih & Saat' => $tercih,
                )))));
        }

        $this->sablonla_gonder('genel_personel', $this->personel_alici(),
            array_merge($ortak, array('detay_tablosu' => $this->detay_tablosu(array(
                'Ad Soyad'                   => $ad,
                'Telefon'                    => $data['patient_phone'],
                'E-posta'                    => $data['patient_email'] ?: '-',
                'TC Kimlik No'               => $data['patient_number'],
                'Doğum Tarihi'               => $data['patient_birthday'],
                'Tercih Edilen Tarih & Saat' => $tercih,
            )))));
    }

    private function send_email_notifications($data, $patient_link = '', $staff_link = '') {
        $s = Caparv_Plugin::settings();
        if (empty($s['enable_email_notifications'])) { return; }

        $clinic = !empty($data['clinic_name']) ? $data['clinic_name'] : 'Çapa Ortodonti';
        $ad     = trim($data['patient_name'] . ' ' . $data['patient_surname']);
        $tarih  = date('d.m.Y H:i', strtotime($data['appointment_date']));

        $ortak = array(
            'klinik' => esc_html($clinic), 'hasta_adi' => esc_html($ad),
            'hekim' => esc_html($data['doctor_name']), 'tarih' => esc_html($tarih),
            'pnr' => esc_html($data['pnr_no']),
            'telefon' => esc_html($data['patient_phone']),
            'eposta' => esc_html($data['patient_email'] ?: '-'),
            'tc' => esc_html($data['patient_number']),
            'klinik_adres' => esc_html($data['clinic_address']),
            'klinik_telefon' => esc_html($data['clinic_phone']),
        );

        if (!empty($data['patient_email'])) {
            $this->sablonla_gonder('randevu_hasta', $this->hasta_alici($data['patient_email']),
                array_merge($ortak, array(
                    'detay_tablosu' => $this->detay_tablosu(array(
                        'Klinik'       => $data['clinic_name'],
                        'Hekim'        => $data['doctor_name'],
                        'Tarih & Saat' => $tarih,
                        'PNR No'       => $data['pnr_no'],
                    )),
                    'buton' => $patient_link ? $this->caparv_email_button('Randevu Bilgilerim', $patient_link) : '',
                )));
        }

        $this->sablonla_gonder('randevu_personel', $this->personel_alici(),
            array_merge($ortak, array(
                'detay_tablosu' => $this->detay_tablosu(array(
                    'Ad Soyad'     => $ad,
                    'Telefon'      => $data['patient_phone'],
                    'E-posta'      => $data['patient_email'] ?: '-',
                    'TC Kimlik No' => $data['patient_number'],
                    'Hekim'        => $data['doctor_name'],
                    'Tarih & Saat' => $tarih,
                    'PNR No'       => $data['pnr_no'],
                )),
                'buton' => $staff_link ? $this->caparv_email_button("Klinik Panelinde Aç", $staff_link) : '',
            )));
    }

    private function caparv_email_row($label, $value) {
        return '<tr><td style="padding:10px 0;border-bottom:1px solid #eef0f2;color:#8a9099;font-size:13px;width:42%;vertical-align:top;">' . $label . '</td>'
            . '<td style="padding:10px 0;border-bottom:1px solid #eef0f2;color:#222222;font-size:14px;font-weight:bold;">' . $value . '</td></tr>';
    }

    private function caparv_email_button($label, $url) {
        return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 6px;"><tr>'
            . '<td align="center" style="border-radius:8px;background:#1a6bc4;">'
            . '<a href="' . esc_url($url) . '" target="_blank" style="display:inline-block;padding:13px 30px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;border-radius:8px;">' . $label . '</a>'
            . '</td></tr></table>';
    }

    private function caparv_email_shell($inner) {
        $logo = 'https://capaortodonti.com/wp-content/uploads/capa-logo-email-380-v2.png';
        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 12px;"><tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e6e6e6;border-radius:10px;overflow:hidden;">'
            . '<tr><td align="center" style="padding:26px 24px;border-bottom:3px solid #1a6bc4;background:#ffffff;"><img src="' . esc_url($logo) . '" alt="Çapa Ortodonti" width="190" style="display:block;width:190px;max-width:65%;height:auto;border:0;"></td></tr>'
            . '<tr><td style="padding:28px 28px 30px;color:#333333;font-size:15px;line-height:1.7;">' . $inner . '</td></tr>'
            . '</table>'
            . '<div style="color:#9aa0a6;font-size:12px;padding:16px 8px;max-width:600px;">Bu e-posta Çapa Ortodonti randevu sistemi tarafından otomatik gönderilmiştir.</div>'
            . '</td></tr></table></body></html>';
    }
}

new Caparv_Ajax_Handlers();
