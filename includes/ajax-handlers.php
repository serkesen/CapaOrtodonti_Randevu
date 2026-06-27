<?php
if (!defined('ABSPATH')) {
    exit;
}

class DentSoft_Ajax_Handlers {
    
    public function __construct() {
        add_action('wp_ajax_dentsoft_save_appointment', array($this, 'save_appointment'));
        add_action('wp_ajax_nopriv_dentsoft_save_appointment', array($this, 'save_appointment'));
        
        add_action('wp_ajax_dentsoft_get_appointments', array($this, 'get_appointments'));
        
        add_action('wp_ajax_dentsoft_update_appointment_status', array($this, 'update_appointment_status'));
        
        add_action('wp_ajax_dentsoft_delete_appointment', array($this, 'delete_appointment'));

        add_action('wp_ajax_dentsoft_genel_randevu', array($this, 'genel_randevu_notify'));
        add_action('wp_ajax_nopriv_dentsoft_genel_randevu', array($this, 'genel_randevu_notify'));
        add_action('wp_ajax_dentsoft_cancel_appointment', array($this, 'cancel_appointment_notify'));
        add_action('wp_ajax_nopriv_dentsoft_cancel_appointment', array($this, 'cancel_appointment_notify'));
    }
    
    private function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'dentsoft-nonce')) {
            wp_send_json_error(array(
                'message' => 'Güvenlik doğrulaması başarısız.'
            ));
            wp_die();
        }
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
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'dentsoft_appointments',
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => 'Randevu kaydedilirken bir hata oluştu.',
                'db_error' => $wpdb->last_error
            ));
            return;
        }
        
        $appointment_id = $wpdb->insert_id;
        
        $patient_link = isset($_POST['appointment_link']) ? esc_url_raw(wp_unslash($_POST['appointment_link'])) : '';
        $staff_link = isset($_POST['appointment_staff_link']) ? esc_url_raw(wp_unslash($_POST['appointment_staff_link'])) : '';
        $this->send_email_notifications($data, $patient_link, $staff_link);
        
        do_action('dentsoft_appointment_created', $appointment_id, $data);
        
        wp_send_json_success(array(
            'message' => 'Randevunuz başarıyla kaydedildi!',
            'appointment_id' => $appointment_id,
            'data' => $data
        ));
    }
    
    public function get_appointments() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Bu işlem için yetkiniz yok.'
            ));
            return;
        }
        
        global $wpdb;
        
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $offset = ($page - 1) * $per_page;
        
        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $status_filter = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        
        $where = array('1=1');
        $where_values = array();
        
        if (!empty($search)) {
            $where[] = "(patient_name LIKE %s OR patient_surname LIKE %s OR patient_phone LIKE %s OR pnr_no LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values = array_merge($where_values, array($search_term, $search_term, $search_term, $search_term));
        }
        
        if (!empty($status_filter)) {
            $where[] = "appointment_status = %s";
            $where_values[] = $status_filter;
        }
        
        $where_clause = implode(' AND ', $where);
        
        if (!empty($where_values)) {
            $where_clause = $wpdb->prepare($where_clause, $where_values);
        }
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}dentsoft_appointments WHERE {$where_clause}");
        
        $query = "SELECT * FROM {$wpdb->prefix}dentsoft_appointments WHERE {$where_clause} ORDER BY appointment_date DESC LIMIT %d OFFSET %d";
        $appointments = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));
        
        wp_send_json_success(array(
            'appointments' => $appointments,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    public function update_appointment_status() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Bu işlem için yetkiniz yok.'
            ));
            return;
        }
        
        if (empty($_POST['appointment_id']) || empty($_POST['status'])) {
            wp_send_json_error(array(
                'message' => 'Gerekli parametreler eksik.'
            ));
            return;
        }
        
        $appointment_id = absint($_POST['appointment_id']);
        $status = sanitize_text_field(wp_unslash($_POST['status']));
        
        $allowed_statuses = array('pending', 'confirmed', 'cancelled', 'completed');
        if (!in_array($status, $allowed_statuses)) {
            wp_send_json_error(array(
                'message' => 'Geçersiz durum değeri.'
            ));
            return;
        }
        
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'dentsoft_appointments',
            array('appointment_status' => $status),
            array('id' => $appointment_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => 'Durum güncellenirken bir hata oluştu.'
            ));
            return;
        }
        
        do_action('dentsoft_appointment_status_changed', $appointment_id, $status);
        
        wp_send_json_success(array(
            'message' => 'Randevu durumu başarıyla güncellendi.',
            'appointment_id' => $appointment_id,
            'status' => $status
        ));
    }
    
    public function delete_appointment() {
        $this->verify_nonce();
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Bu işlem için yetkiniz yok.'
            ));
            return;
        }
        
        if (empty($_POST['appointment_id'])) {
            wp_send_json_error(array(
                'message' => 'Randevu ID gerekli.'
            ));
            return;
        }
        
        $appointment_id = absint($_POST['appointment_id']);
        
        global $wpdb;
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'dentsoft_appointments',
            array('id' => $appointment_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array(
                'message' => 'Randevu silinirken bir hata oluştu.'
            ));
            return;
        }
        
        do_action('dentsoft_appointment_deleted', $appointment_id);
        
        wp_send_json_success(array(
            'message' => 'Randevu başarıyla silindi.',
            'appointment_id' => $appointment_id
        ));
    }
    
    public function cancel_appointment_notify() {
        $this->verify_nonce();

        $pnr = isset($_POST['pnr_no']) ? sanitize_text_field(wp_unslash($_POST['pnr_no'])) : '';
        if (empty($pnr)) {
            wp_send_json_error(array('message' => 'PNR gerekli.'));
            return;
        }

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dentsoft_appointments WHERE pnr_no = %s ORDER BY id DESC LIMIT 1",
            $pnr
        ), ARRAY_A);

        if ($row) {
            $wpdb->update(
                $wpdb->prefix . 'dentsoft_appointments',
                array('appointment_status' => 'cancelled'),
                array('pnr_no' => $pnr),
                array('%s'),
                array('%s')
            );
        }

        $this->send_cancellation_notifications($pnr, $row);

        wp_send_json_success(array('message' => 'Iptal bildirimi islendi.'));
    }

    private function send_cancellation_notifications($pnr, $row) {
        $settings = get_option('dentsoft_settings', array());
        if (empty($settings['enable_email_notifications'])) {
            return;
        }

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $clinic_name = (!empty($row['clinic_name'])) ? $row['clinic_name'] : 'Çapa Ortodonti';
        $appt_date = (!empty($row['appointment_date'])) ? date('d.m.Y H:i', strtotime($row['appointment_date'])) : '-';
        $doctor = (!empty($row['doctor_name'])) ? $row['doctor_name'] : '-';

        if ($row && !empty($row['patient_email'])) {
            $prows  = $this->dentsoft_email_row('Klinik', esc_html($clinic_name));
            $prows .= $this->dentsoft_email_row('Hekim', esc_html($doctor));
            $prows .= $this->dentsoft_email_row('Tarih & Saat', esc_html($appt_date));
            $prows .= $this->dentsoft_email_row('PNR No', esc_html($pnr));

            $pinner  = '<p style="margin:0 0 14px;">Sayın <strong>' . esc_html(trim($row['patient_name'] . ' ' . $row['patient_surname'])) . '</strong>,</p>';
            $pinner .= '<p style="margin:0 0 18px;">Aşağıdaki randevunuz iptal edilmiştir:</p>';
            $pinner .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">' . $prows . '</table>';
            $pinner .= '<p style="margin:18px 0 0;color:#666666;font-size:13px;line-height:1.6;">Yeni randevu için web sitemizden tekrar randevu alabilirsiniz.</p>';

            $phtml = $this->dentsoft_email_shell('Randevunuz İptal Edildi', $pinner);
            wp_mail($row['patient_email'], 'Randevunuz İptal Edildi - ' . $clinic_name, $phtml, $headers);
        }

        $staff_email = array('serkesen@gmail.com', 'info@capaortodonti.com');

        $srows  = $this->dentsoft_email_row('Ad Soyad', esc_html($row ? trim($row['patient_name'] . ' ' . $row['patient_surname']) : '-'));
        $srows .= $this->dentsoft_email_row('Telefon', esc_html(($row && !empty($row['patient_phone'])) ? $row['patient_phone'] : '-'));
        $srows .= $this->dentsoft_email_row('Hekim', esc_html($doctor));
        $srows .= $this->dentsoft_email_row('Tarih & Saat', esc_html($appt_date));
        $srows .= $this->dentsoft_email_row('PNR No', esc_html($pnr));

        $sinner  = '<p style="margin:0 0 18px;">Bir online randevu iptal edildi.</p>';
        $sinner .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">' . $srows . '</table>';
        if (!$row) {
            $sinner .= '<p style="margin:18px 0 0;color:#999999;font-size:12px;">(Not: Bu PNR için sistemde kayıt bulunamadı; randevu online dışı alınmış olabilir.)</p>';
        }

        $html = $this->dentsoft_email_shell('Randevu İptali', $sinner);
        wp_mail($staff_email, 'Randevu İptali - ' . $pnr, $html, $headers);
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
        );

        $this->send_genel_randevu_notifications($data);

        wp_send_json_success(array(
            'message' => 'Talebiniz alındı.'
        ));
    }

    private function send_genel_randevu_notifications($data) {
        $settings = get_option('dentsoft_settings', array());
        if (empty($settings['enable_email_notifications'])) {
            return;
        }

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $clinic_name = !empty($data['clinic_name']) ? $data['clinic_name'] : 'Çapa Ortodonti';
        $full_name = trim($data['patient_name'] . ' ' . $data['patient_surname']);
        $tercih = !empty($data['appointment_date']) ? trim($data['appointment_date']) : '-';

        if (!empty($data['patient_email'])) {
            $prows  = $this->dentsoft_email_row('Klinik', esc_html($clinic_name));
            $prows .= $this->dentsoft_email_row('Talep Türü', esc_html('Genel Randevu (Muayene)'));
            $prows .= $this->dentsoft_email_row('Tercih Edilen Tarih & Saat', esc_html($tercih));

            $pinner  = '<p style="margin:0 0 14px;">Sayın <strong>' . esc_html($full_name) . '</strong>,</p>';
            $pinner .= '<p style="margin:0 0 18px;">Genel randevu (muayene) talebiniz tarafımıza ulaşmıştır. Tercih ettiğiniz tarih ve saat, hekimlerimizin uygunluk durumuna göre değişiklik gösterebilir; en kısa sürede sizinle iletişime geçeceğiz.</p>';
            $pinner .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">' . $prows . '</table>';
            $pinner .= '<p style="margin:18px 0 0;color:#666666;font-size:13px;line-height:1.6;">' . esc_html($clinic_name) . '</p>';

            $phtml = $this->dentsoft_email_shell('Talebiniz Alındı', $pinner);
            wp_mail($data['patient_email'], 'Genel Randevu Talebiniz Alındı - ' . $clinic_name, $phtml, $headers);
        }

        $staff_email = array('serkesen@gmail.com', 'info@capaortodonti.com');

        $srows  = $this->dentsoft_email_row('Ad Soyad', esc_html($full_name));
        $srows .= $this->dentsoft_email_row('Telefon', esc_html($data['patient_phone']));
        $srows .= $this->dentsoft_email_row('E-posta', esc_html(!empty($data['patient_email']) ? $data['patient_email'] : '-'));
        $srows .= $this->dentsoft_email_row('TC Kimlik No', esc_html($data['patient_number']));
        if (!empty($data['patient_birthday'])) {
            $srows .= $this->dentsoft_email_row('Doğum Tarihi', esc_html($data['patient_birthday']));
        }
        $srows .= $this->dentsoft_email_row('Tercih Edilen Tarih & Saat', esc_html($tercih));

        $sinner  = '<p style="margin:0 0 18px;">Yeni bir <strong>Genel Randevu (Muayene)</strong> talebi geldi. Bu talep DentSoft sistemine kaydedilmemiştir; lütfen hastayı arayıp uygun saati teyit edin.</p>';
        $sinner .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">' . $srows . '</table>';

        $shtml = $this->dentsoft_email_shell('Genel Randevu Talebi', $sinner);
        wp_mail($staff_email, 'Genel Randevu Talebi - ' . $full_name, $shtml, $headers);
    }

    private function send_email_notifications($data, $patient_link = '', $staff_link = '') {
        $settings = get_option('dentsoft_settings', array());

        if (empty($settings['enable_email_notifications'])) {
            return;
        }

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $clinic_name = !empty($data['clinic_name']) ? $data['clinic_name'] : 'Çapa Ortodonti';
        $appt_date = date('d.m.Y H:i', strtotime($data['appointment_date']));

        if (!empty($data['patient_email'])) {
            $rows  = $this->dentsoft_email_row('Klinik', esc_html($data['clinic_name']));
            $rows .= $this->dentsoft_email_row('Hekim', esc_html($data['doctor_name']));
            $rows .= $this->dentsoft_email_row('Tarih & Saat', esc_html($appt_date));
            $rows .= $this->dentsoft_email_row('PNR No', esc_html($data['pnr_no']));

            $inner  = '<p style="margin:0 0 14px;">Sayın <strong>' . esc_html($data['patient_name'] . ' ' . $data['patient_surname']) . '</strong>,</p>';
            $inner .= '<p style="margin:0 0 18px;">Randevunuz başarıyla oluşturulmuştur. Detaylarınız aşağıdadır:</p>';
            $inner .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">' . $rows . '</table>';
            if (!empty($patient_link)) {
                $inner .= $this->dentsoft_email_button('Randevu Bilgilerim', $patient_link);
            }
            $inner .= '<p style="margin:18px 0 0;color:#666666;font-size:13px;line-height:1.6;">' . esc_html($clinic_name) . '<br>' . esc_html($data['clinic_address']) . '<br>' . esc_html($data['clinic_phone']) . '</p>';

            $html = $this->dentsoft_email_shell('Randevunuz Oluşturuldu', $inner);
            wp_mail($data['patient_email'], 'Randevunuz Oluşturuldu - ' . $clinic_name, $html, $headers);
        }

        $staff_email = array('serkesen@gmail.com', 'info@capaortodonti.com');

        $srows  = $this->dentsoft_email_row('Ad Soyad', esc_html($data['patient_name'] . ' ' . $data['patient_surname']));
        $srows .= $this->dentsoft_email_row('Telefon', esc_html($data['patient_phone']));
        $srows .= $this->dentsoft_email_row('E-posta', esc_html(!empty($data['patient_email']) ? $data['patient_email'] : '-'));
        $srows .= $this->dentsoft_email_row('TC Kimlik No', esc_html($data['patient_number']));
        $srows .= $this->dentsoft_email_row('Hekim', esc_html($data['doctor_name']));
        $srows .= $this->dentsoft_email_row('Tarih & Saat', esc_html($appt_date));
        $srows .= $this->dentsoft_email_row('PNR No', esc_html($data['pnr_no']));

        $sinner  = '<p style="margin:0 0 18px;">Yeni bir online randevu oluşturuldu.</p>';
        $sinner .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 8px;">' . $srows . '</table>';
        if (!empty($staff_link)) {
            $sinner .= $this->dentsoft_email_button("Dentsoft'ta Aç", $staff_link);
        }

        $shtml = $this->dentsoft_email_shell('Online Randevu Oluşturuldu!', $sinner);
        wp_mail($staff_email, 'Online Randevu Oluşturuldu!', $shtml, $headers);
    }

    private function dentsoft_email_row($label, $value) {
        return '<tr><td style="padding:10px 0;border-bottom:1px solid #eef0f2;color:#8a9099;font-size:13px;width:42%;vertical-align:top;">' . $label . '</td>'
            . '<td style="padding:10px 0;border-bottom:1px solid #eef0f2;color:#222222;font-size:14px;font-weight:bold;">' . $value . '</td></tr>';
    }

    private function dentsoft_email_button($label, $url) {
        return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px 0 6px;"><tr>'
            . '<td align="center" style="border-radius:8px;background:#1a6bc4;">'
            . '<a href="' . esc_url($url) . '" target="_blank" style="display:inline-block;padding:13px 30px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;border-radius:8px;">' . $label . '</a>'
            . '</td></tr></table>';
    }

    private function dentsoft_email_shell($title, $inner) {
        $logo = 'https://capaortodonti.com/wp-content/uploads/logocapa.png';
        return '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 12px;"><tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e6e6e6;border-radius:10px;overflow:hidden;">'
            . '<tr><td align="center" style="padding:26px 24px;border-bottom:3px solid #1a6bc4;background:#ffffff;"><img src="' . esc_url($logo) . '" alt="Çapa Ortodonti" width="190" style="display:block;width:190px;max-width:65%;height:auto;border:0;"></td></tr>'
            . '<tr><td style="padding:28px 28px 4px;"><div style="font-size:20px;font-weight:bold;color:#1a6bc4;">' . $title . '</div></td></tr>'
            . '<tr><td style="padding:4px 28px 30px;color:#333333;font-size:15px;line-height:1.7;">' . $inner . '</td></tr>'
            . '</table>'
            . '<div style="color:#9aa0a6;font-size:12px;padding:16px 8px;max-width:600px;">Bu e-posta Çapa Ortodonti randevu sistemi tarafından otomatik gönderilmiştir.</div>'
            . '</td></tr></table></body></html>';
    }
}

new DentSoft_Ajax_Handlers();
