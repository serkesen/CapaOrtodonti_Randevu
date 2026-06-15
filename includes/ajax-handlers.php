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
        
        $this->send_email_notifications($data);
        
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
    
    private function send_email_notifications($data) {
        $settings = get_option('dentsoft_settings', array());
        
        if (empty($settings['enable_email_notifications'])) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        
        if (!empty($data['patient_email'])) {
            $to = $data['patient_email'];
            $subject = 'Randevu Onayı - ' . $data['clinic_name'];
            
            $message = sprintf(
                "Sayın %s %s,\n\n" .
                "Randevunuz başarıyla oluşturulmuştur.\n\n" .
                "Randevu Detayları:\n" .
                "Klinik: %s\n" .
                "Doktor: %s\n" .
                "Tarih: %s\n" .
                "PNR No: %s\n\n" .
                "Randevunuz klinik tarafından onaylandığında size bilgi verilecektir.\n\n" .
                "Saygılarımızla,\n" .
                "%s",
                $data['patient_name'],
                $data['patient_surname'],
                $data['clinic_name'],
                $data['doctor_name'],
                date('d.m.Y H:i', strtotime($data['appointment_date'])),
                $data['pnr_no'],
                $data['clinic_name']
            );
            
            wp_mail($to, $subject, $message);
        }
        
        $admin_subject = 'Yeni Randevu Talebi - ' . $data['patient_name'] . ' ' . $data['patient_surname'];
        
        $admin_message = sprintf(
            "Yeni bir randevu talebi oluşturuldu.\n\n" .
            "Hasta Bilgileri:\n" .
            "Ad Soyad: %s %s\n" .
            "Telefon: %s\n" .
            "E-posta: %s\n" .
            "TC/Pasaport: %s\n\n" .
            "Randevu Detayları:\n" .
            "Doktor: %s\n" .
            "Tarih: %s\n" .
            "PNR No: %s\n\n" .
            "Randevuyu onaylamak için admin paneline giriş yapınız.",
            $data['patient_name'],
            $data['patient_surname'],
            $data['patient_phone'],
            !empty($data['patient_email']) ? $data['patient_email'] : 'Belirtilmedi',
            $data['patient_number'],
            $data['doctor_name'],
            date('d.m.Y H:i', strtotime($data['appointment_date'])),
            $data['pnr_no']
        );
        
        wp_mail($admin_email, $admin_subject, $admin_message);
    }
}

new DentSoft_Ajax_Handlers();
