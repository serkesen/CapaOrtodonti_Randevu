<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('dentsoft_settings', array());
$primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#00cc61';

function adjustBrightness($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function hexToRgba($hex, $alpha = 1) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r, $g, $b, $alpha)";
}

$primary_dark = adjustBrightness($primary_color, -20);
$primary_light = hexToRgba($primary_color, 0.1);
?>

<style id="dentsoft-dynamic-colors">
    :root {
        --dentsoft-primary: <?php echo esc_attr($primary_color); ?>;
        --dentsoft-primary-dark: <?php echo esc_attr($primary_dark); ?>;
        --dentsoft-primary-light: <?php echo esc_attr($primary_light); ?>;
    }
</style>

<div class="dentsoft-appointment-wrapper">
    <div class="dentsoft-container">
        <div class="dentsoft-appointment-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="dentsoft-title" style="margin-bottom: 0;"></h2>
                <button type="button" id="dentsoft-header-query-btn" class="dentsoft-btn dentsoft-btn-secondary dentsoft-btn-sm">
                    <i class="fa fa-search"></i> Randevu Sorgula
                </button>
            </div>
            <div class="dentsoft-progress-bar">
                <div class="dentsoft-step active" data-step="1">
                    <div class="step-icon">
                        <i class="fa fa-hospital"></i>
                    </div>
                    <div class="step-label">Klinik</div>
                </div>
                <div class="dentsoft-step" data-step="2">
                    <div class="step-icon">
                        <i class="fa fa-user-doctor"></i>
                    </div>
                    <div class="step-label">Hekim</div>
                </div>
                <div class="dentsoft-step" data-step="3">
                    <div class="step-icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <div class="step-label">Tarih</div>
                </div>
                <div class="dentsoft-step" data-step="4">
                    <div class="step-icon">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="step-label">Bilgiler</div>
                </div>
                <div class="dentsoft-step" data-step="5">
                    <div class="step-icon">
                        <i class="fa fa-check"></i>
                    </div>
                    <div class="step-label">Tamamlandı</div>
                </div>
            </div>
            
            <div id="dentsoft-selection-summary" class="dentsoft-selection-summary" style="display:none;">
                <div class="dentsoft-summary-card">
                    <div class="summary-icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <div class="summary-content">
                        <div id="dentsoft-selected-clinic" class="summary-item" style="display:none;">
                            <i class="fa fa-hospital"></i>
                            <span class="summary-text"></span>
                        </div>
                        <div id="dentsoft-selected-doctor" class="summary-item" style="display:none;">
                            <i class="fa fa-user-doctor"></i>
                            <span class="summary-text"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dentsoft-appointment-content">
            <div id="dentsoft-query-section" class="dentsoft-query-section" style="display: none;">
                <div class="dentsoft-card">
                    <div class="dentsoft-card-header">
                        <h3>Randevu Sorgula</h3>
                        <p>Randevunuzu sorgulamak için PNR numaranızı ve TC Kimlik numaranızın son 4 hanesini giriniz</p>
                    </div>
                    <div class="dentsoft-card-body">
                        <div class="dentsoft-form-row">
                            <div class="dentsoft-form-group">
                                <label for="dentsoft-query-pnr" class="dentsoft-label">
                                    PNR Numarası
                                    <span class="required">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="dentsoft-query-pnr"
                                    class="dentsoft-input"
                                    placeholder="PNR numaranızı giriniz"
                                    maxlength="20">
                            </div>
                            <div class="dentsoft-form-group">
                                <label for="dentsoft-query-patient-number" class="dentsoft-label">
                                    TC Kimlik No (Son 4 Hane)
                                    <span class="required">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="dentsoft-query-patient-number"
                                    class="dentsoft-input"
                                    placeholder="TC No son 4 hane"
                                    maxlength="4"
                                    pattern="[0-9]{4}">
                            </div>
                        </div>
                        <div id="dentsoft-query-error" class="dentsoft-error-message" style="display:none;"></div>
                    </div>
                    <div class="dentsoft-card-footer">
                        <button type="button" id="dentsoft-query-submit-btn" class="dentsoft-btn dentsoft-btn-primary">
                            <i class="fa fa-search"></i> Sorgula
                        </button>
                        <button type="button" id="dentsoft-query-close-btn" class="dentsoft-btn dentsoft-btn-secondary">
                            <i class="fa fa-times"></i> Kapat
                        </button>
                    </div>
                </div>
                
                <div id="dentsoft-query-result" class="dentsoft-card" style="display:none; margin-top: 20px;">
                    <div class="dentsoft-card-header">
                        <h3>Randevu Detayları</h3>
                    </div>
                    <div class="dentsoft-card-body">
                        <div class="dentsoft-appointment-summary">
                            <div class="dentsoft-summary-item">
                                <div class="summary-label">Hasta Adı</div>
                                <div class="summary-value" id="dentsoft-query-patient-name"></div>
                            </div>
                            <div class="dentsoft-summary-item">
                                <div class="summary-label">Klinik</div>
                                <div class="summary-value" id="dentsoft-query-clinic"></div>
                            </div>
                            <div class="dentsoft-summary-item">
                                <div class="summary-label">Hekim</div>
                                <div class="summary-value" id="dentsoft-query-doctor"></div>
                            </div>
                            <div class="dentsoft-summary-item">
                                <div class="summary-label">Tarih & Saat</div>
                                <div class="summary-value" id="dentsoft-query-datetime"></div>
                            </div>
                            <div class="dentsoft-summary-item">
                                <div class="summary-label">PNR No</div>
                                <div class="summary-value" id="dentsoft-query-pnr-display"></div>
                            </div>
                        </div>
                    </div>
                    <div class="dentsoft-card-footer">
                        <button type="button" id="dentsoft-cancel-appointment-btn" class="dentsoft-btn dentsoft-btn-danger">
                            <i class="fa fa-trash"></i> Randevuyu İptal Et
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="dentsoft-step-content active" data-step="1">
                <div class="dentsoft-card">
                    <div class="dentsoft-card-header">
                        <h3>Klinik Seçimi</h3>
                        <p>Lütfen randevu almak istediğiniz kliniği seçiniz</p>
                    </div>
                    <div class="dentsoft-card-body">
                        <div id="dentsoft-clinic-error" class="dentsoft-error-message" style="display:none;"></div>
                        <select id="dentsoft-clinic-select" class="dentsoft-select2">
                            <option value="">Klinik Seçiniz...</option>
                        </select>
                    </div>
                    <div class="dentsoft-card-footer">
                        <button type="button" class="dentsoft-btn dentsoft-btn-primary dentsoft-btn-next" data-step="1" disabled>
                            Devam Et <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="dentsoft-step-content" data-step="2">
                <div class="dentsoft-card">
                    <div class="dentsoft-card-header">
                        <h3>Hekim Seçimi</h3>
                        <p>Randevu almak istediğiniz hekimi seçiniz</p>
                    </div>
                    <div class="dentsoft-card-body">
                        <div id="dentsoft-doctor-error" class="dentsoft-error-message" style="display:none;"></div>
                        <select id="dentsoft-doctor-select" class="dentsoft-select2">
                            <option value="">Hekim Seçiniz...</option>
                        </select>
                    </div>
                    <div class="dentsoft-card-footer">
                        <button type="button" class="dentsoft-btn dentsoft-btn-secondary dentsoft-btn-prev">
                            <i class="fa fa-arrow-left"></i> Geri
                        </button>
                        <button type="button" class="dentsoft-btn dentsoft-btn-primary dentsoft-btn-next" data-step="2" disabled>
                            Devam Et <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="dentsoft-step-content" data-step="3">
                <div class="dentsoft-card">
                    <div class="dentsoft-card-header">
                        <h3>Tarih ve Saat Seçimi</h3>
                        <p>Uygun tarih ve saati seçiniz</p>
                    </div>
                    <div class="dentsoft-card-body">
                        <div id="dentsoft-calendar-loading" class="dentsoft-loading" style="display:none;">
                            <div class="dentsoft-spinner"></div>
                            <p>Müsait saatler yükleniyor...</p>
                        </div>
                        
                        <div id="dentsoft-calendar-controls" class="dentsoft-calendar-controls" style="display:none;">
                            <button type="button" class="dentsoft-btn dentsoft-btn-sm dentsoft-btn-prev-week">
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <button type="button" class="dentsoft-btn dentsoft-btn-sm dentsoft-btn-next-week">
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <div id="dentsoft-calendar-container" class="dentsoft-calendar-container" style="display:none;"></div>
                        
                        <div id="dentsoft-no-appointments" class="dentsoft-info-box" style="display:none;">
                            <div class="dentsoft-offline-doctor">
                                <h4>Bu hekim için online randevu müsait değil</h4>
                                <p>Randevu almak için lütfen klinikle iletişime geçin.</p>
                                <div id="dentsoft-clinic-contact-info"></div>
                            </div>
                        </div>
                    </div>
                    <div class="dentsoft-card-footer">
                        <button type="button" class="dentsoft-btn dentsoft-btn-secondary dentsoft-btn-prev">
                            <i class="fa fa-arrow-left"></i> Geri
                        </button>
                        <button type="button" class="dentsoft-btn dentsoft-btn-primary dentsoft-btn-next" disabled>
                            Devam Et <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="dentsoft-step-content" data-step="4">
                <div class="dentsoft-card">
                    <div class="dentsoft-card-header">
                        <h3>Hasta Bilgileri</h3>
                        <p>Lütfen bilgilerinizi eksiksiz doldurunuz</p>
                    </div>
                    <div class="dentsoft-card-body">
                        <form id="dentsoft-patient-form">
                            <input type="hidden" name="clinic_id" id="dentsoft-form-clinic-id">
                            <input type="hidden" name="doctor_id" id="dentsoft-form-doctor-id">
                            <input type="hidden" name="appointment_date" id="dentsoft-form-date">
                            <input type="hidden" name="appointment_time" id="dentsoft-form-time">
                            
                            <div class="dentsoft-form-row">
                                <div class="dentsoft-form-group">
                                    <label for="dentsoft-patient-number" class="dentsoft-label">
                                        TC Kimlik / Pasaport No <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="dentsoft-patient-number" 
                                        name="patient_number" 
                                        class="dentsoft-input" 
                                        required
                                        maxlength="11"
                                        placeholder="12345678901">
                                </div>
                                
                                <div class="dentsoft-form-group">
                                    <label for="dentsoft-patient-birthday" class="dentsoft-label">
                                        Doğum Tarihi
                                    </label>
                                    <input 
                                        type="date" 
                                        id="dentsoft-patient-birthday" 
                                        name="patient_birthday" 
                                        class="dentsoft-input">
                                </div>
                            </div>
                            
                            <div class="dentsoft-form-row">
                                <div class="dentsoft-form-group">
                                    <label for="dentsoft-patient-name" class="dentsoft-label">
                                        Ad <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="dentsoft-patient-name" 
                                        name="patient_name" 
                                        class="dentsoft-input" 
                                        required
                                        placeholder="Adınız">
                                </div>
                                
                                <div class="dentsoft-form-group">
                                    <label for="dentsoft-patient-surname" class="dentsoft-label">
                                        Soyad <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="dentsoft-patient-surname" 
                                        name="patient_surname" 
                                        class="dentsoft-input" 
                                        required
                                        placeholder="Soyadınız">
                                </div>
                            </div>
                            
                            <div class="dentsoft-form-row">
                                <div class="dentsoft-form-group">
                                    <label for="dentsoft-patient-phone" class="dentsoft-label">
                                        Telefon <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="dentsoft-patient-phone" 
                                        name="patient_phone" 
                                        class="dentsoft-input" 
                                        required
                                        placeholder="5XX XXX XX XX">
                                </div>
                                
                                <div class="dentsoft-form-group">
                                    <label for="dentsoft-patient-email" class="dentsoft-label">
                                        E-posta
                                    </label>
                                    <input 
                                        type="email" 
                                        id="dentsoft-patient-email" 
                                        name="patient_email" 
                                        class="dentsoft-input"
                                        placeholder="ornek@email.com">
                                </div>
                            </div>
                            
                            <div class="dentsoft-form-group">
                                <label class="dentsoft-checkbox-label">
                                    <input 
                                        type="checkbox" 
                                        id="dentsoft-kvkk-checkbox" 
                                        name="kvkk_approval" 
                                        required>
                                    <span>
                                        <a href="#" id="dentsoft-kvkk-link">KVKK Aydınlatma Metnini</a> okudum ve kabul ediyorum <span class="required">*</span>
                                    </span>
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="dentsoft-card-footer">
                        <button type="button" class="dentsoft-btn dentsoft-btn-secondary dentsoft-btn-prev">
                            <i class="fa fa-arrow-left"></i> Geri
                        </button>
                        <button type="button" id="dentsoft-submit-btn" class="dentsoft-btn dentsoft-btn-success">
                            <i class="fa fa-check"></i> Randevu Oluştur
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="dentsoft-step-content" data-step="5">
                <div class="dentsoft-card dentsoft-success-card">
                    <div class="dentsoft-success-icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <h3 class="dentsoft-success-title">Randevunuz Oluşturuldu!</h3>
                    <p class="dentsoft-success-message">Randevu bilgileriniz aşağıdadır</p>
                    
                    <div class="dentsoft-appointment-summary">
                        <div class="dentsoft-summary-item">
                            <div class="summary-label">Hasta</div>
                            <div class="summary-value" id="dentsoft-summary-patient"></div>
                        </div>
                        <div class="dentsoft-summary-item">
                            <div class="summary-label">Klinik</div>
                            <div class="summary-value" id="dentsoft-summary-clinic"></div>
                        </div>
                        <div class="dentsoft-summary-item">
                            <div class="summary-label">Hekim</div>
                            <div class="summary-value" id="dentsoft-summary-doctor"></div>
                        </div>
                        <div class="dentsoft-summary-item">
                            <div class="summary-label">Tarih & Saat</div>
                            <div class="summary-value" id="dentsoft-summary-datetime"></div>
                        </div>
                        <div class="dentsoft-summary-item">
                            <div class="summary-label">PNR No</div>
                            <div class="summary-value" id="dentsoft-summary-pnr"></div>
                        </div>
                    </div>
                    
                    <div class="dentsoft-success-footer">
                        <button type="button" id="dentsoft-new-appointment-btn" class="dentsoft-btn dentsoft-btn-primary">
                            <i class="fa fa-plus"></i> Yeni Randevu
                        </button>
                        <button type="button" id="dentsoft-query-appointment-btn" class="dentsoft-btn dentsoft-btn-secondary">
                            <i class="fa fa-search"></i> Randevu Sorgula
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="dentsoft-kvkk-modal" class="dentsoft-modal" style="display:none;">
    <div class="dentsoft-modal-overlay"></div>
    <div class="dentsoft-modal-content">
        <div class="dentsoft-modal-header">
            <h3>KVKK Aydınlatma Metni</h3>
            <button type="button" class="dentsoft-modal-close">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="dentsoft-modal-body" id="dentsoft-kvkk-content">
            <div class="dentsoft-loading">
                <div class="dentsoft-spinner"></div>
                <p>Yükleniyor...</p>
            </div>
        </div>
        <div class="dentsoft-modal-footer">
            <button type="button" class="dentsoft-btn dentsoft-btn-secondary dentsoft-modal-close">
                Kapat
            </button>
        </div>
    </div>
</div>
