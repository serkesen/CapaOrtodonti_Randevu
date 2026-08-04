<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('caparv_settings', array());
$primary_color = isset($settings['primary_color']) ? $settings['primary_color'] : '#00cc61';

if (!function_exists('adjustBrightness')) {
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
}

if (!function_exists('hexToRgba')) {
function hexToRgba($hex, $alpha = 1) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r, $g, $b, $alpha)";
}
}

$primary_dark = adjustBrightness($primary_color, -20);
$primary_light = hexToRgba($primary_color, 0.1);
?>

<style id="caparv-dynamic-colors">
    :root {
        --caparv-primary: <?php echo esc_attr($primary_color); ?>;
        --caparv-primary-dark: <?php echo esc_attr($primary_dark); ?>;
        --caparv-primary-light: <?php echo esc_attr($primary_light); ?>;
    }
</style>

<div class="caparv-appointment-wrapper">
    <div class="caparv-container">
        <div class="caparv-appointment-header">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="caparv-title" style="margin-bottom: 0;"></h2>
                <button type="button" id="caparv-header-query-btn" class="caparv-btn caparv-btn-secondary caparv-btn-sm">
                    <i class="fa fa-search"></i> Randevu Sorgula
                </button>
            </div>
            <div class="caparv-progress-bar">
                <div class="caparv-step active" data-step="1">
                    <div class="step-icon">
                        <i class="fa fa-hospital"></i>
                    </div>
                    <div class="step-label">Klinik</div>
                </div>
                <div class="caparv-step" data-step="2">
                    <div class="step-icon">
                        <i class="fa fa-user-doctor"></i>
                    </div>
                    <div class="step-label">Hekim</div>
                </div>
                <div class="caparv-step" data-step="3">
                    <div class="step-icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                    <div class="step-label">Tarih</div>
                </div>
                <div class="caparv-step" data-step="4">
                    <div class="step-icon">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="step-label">Bilgiler</div>
                </div>
                <div class="caparv-step" data-step="5">
                    <div class="step-icon">
                        <i class="fa fa-check"></i>
                    </div>
                    <div class="step-label">Onay</div>
                </div>
            </div>
            
            <div id="caparv-selection-summary" class="caparv-selection-summary" style="display:none;">
                <div class="caparv-summary-card">
                    <div class="summary-icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <div class="summary-content">
                        <div id="caparv-selected-clinic" class="summary-item" style="display:none;">
                            <!-- 4 Agu 2026: jenerik hastane ikonu yerine Capa ikonu. -->
                            <img class="summary-avatar" src="https://capaortodonti.com/wp-content/uploads/capa-ikon-128.png" alt="" loading="lazy">
                            <span class="summary-text"></span>
                        </div>
                        <div id="caparv-selected-doctor" class="summary-item" style="display:none;">
                            <!-- src app.js tarafindan doldurulur: secili hekimin mini fotografi. -->
                            <img class="summary-avatar" src="" alt="" loading="lazy" style="display:none;">
                            <span class="summary-text"></span>
                        </div>
                        <!-- 4 Agu 2026: tarih/saat satiri. Secim yapiliyordu ama ozete hic yazilmiyordu. -->
                        <div id="caparv-selected-datetime" class="summary-item" style="display:none;">
                            <span class="summary-badge"><i class="fa fa-calendar"></i></span>
                            <span class="summary-text"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="caparv-appointment-content">
            <div id="caparv-query-section" class="caparv-query-section" style="display: none;">
                <div class="caparv-card">
                    <div class="caparv-card-header">
                        <h3>Randevu Sorgula</h3>
                        <p>Randevunuzu sorgulamak için PNR numaranızı ve TC Kimlik numaranızın son 4 hanesini giriniz</p>
                    </div>
                    <div class="caparv-card-body">
                        <div class="caparv-form-row">
                            <div class="caparv-form-group">
                                <label for="caparv-query-pnr" class="caparv-label">
                                    PNR Numarası
                                    <span class="required">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="caparv-query-pnr"
                                    class="caparv-input"
                                    placeholder="PNR numaranızı giriniz"
                                    maxlength="20">
                            </div>
                            <div class="caparv-form-group">
                                <label for="caparv-query-patient-number" class="caparv-label">
                                    TC Kimlik No (Son 4 Hane)
                                    <span class="required">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="caparv-query-patient-number"
                                    class="caparv-input"
                                    placeholder="TC No son 4 hane"
                                    maxlength="4"
                                    pattern="[0-9]{4}">
                            </div>
                        </div>
                        <div id="caparv-query-error" class="caparv-error-message" style="display:none;"></div>
                    </div>
                    <div class="caparv-card-footer">
                        <button type="button" id="caparv-query-submit-btn" class="caparv-btn caparv-btn-primary">
                            <i class="fa fa-search"></i> Sorgula
                        </button>
                        <button type="button" id="caparv-query-close-btn" class="caparv-btn caparv-btn-secondary">
                            <i class="fa fa-times"></i> Kapat
                        </button>
                    </div>
                </div>
                
                <div id="caparv-query-result" class="caparv-card" style="display:none; margin-top: 20px;">
                    <div class="caparv-card-header">
                        <h3>Randevu Detayları</h3>
                    </div>
                    <div class="caparv-card-body">
                        <div class="caparv-appointment-summary">
                            <div class="caparv-summary-item">
                                <div class="summary-label">Hasta Adı</div>
                                <div class="summary-value" id="caparv-query-patient-name"></div>
                            </div>
                            <div class="caparv-summary-item">
                                <div class="summary-label">Klinik</div>
                                <div class="summary-value" id="caparv-query-clinic"></div>
                            </div>
                            <div class="caparv-summary-item">
                                <div class="summary-label">Hekim</div>
                                <div class="summary-value" id="caparv-query-doctor"></div>
                            </div>
                            <div class="caparv-summary-item">
                                <div class="summary-label">Tarih & Saat</div>
                                <div class="summary-value" id="caparv-query-datetime"></div>
                            </div>
                            <div class="caparv-summary-item">
                                <div class="summary-label">PNR No</div>
                                <div class="summary-value" id="caparv-query-pnr-display"></div>
                            </div>
                        </div>
                    </div>
                    <div class="caparv-card-footer">
                        <button type="button" id="caparv-cancel-appointment-btn" class="caparv-btn caparv-btn-danger">
                            <i class="fa fa-trash"></i> Randevuyu İptal Et
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="caparv-step-content active" data-step="1">
                <div class="caparv-card">
                    <div class="caparv-card-header">
                        <h3>Klinik Seçimi</h3>
                        <p>Lütfen randevu almak istediğiniz kliniği seçiniz</p>
                    </div>
                    <div class="caparv-card-body">
                        <div id="caparv-clinic-error" class="caparv-error-message" style="display:none;"></div>
                        <select id="caparv-clinic-select" class="caparv-select2">
                            <option value="">Klinik Seçiniz...</option>
                        </select>
                    </div>
                    <div class="caparv-card-footer">
                        <button type="button" class="caparv-btn caparv-btn-primary caparv-btn-next" data-step="1" disabled>
                            Devam Et <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="caparv-step-content" data-step="2">
                <div class="caparv-card">
                    <div class="caparv-card-header">
                        <h3>Hekim Seçimi</h3>
                        <p>Randevu almak istediğiniz hekimi seçiniz</p>
                    </div>
                    <div class="caparv-card-body">
                        <div id="caparv-doctor-error" class="caparv-error-message" style="display:none;"></div>
                        <select id="caparv-doctor-select" class="caparv-select2">
                            <option value="">Hekim Seçiniz...</option>
                        </select>
                    </div>
                    <div class="caparv-card-footer">
                        <button type="button" class="caparv-btn caparv-btn-secondary caparv-btn-prev">
                            <i class="fa fa-arrow-left"></i> Geri
                        </button>
                        <button type="button" class="caparv-btn caparv-btn-primary caparv-btn-next" data-step="2" disabled>
                            Devam Et <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="caparv-step-content" data-step="3">
                <div class="caparv-card">
                    <div class="caparv-card-header">
                        <h3>Tarih ve Saat Seçimi</h3>
                        <p>Uygun tarih ve saati seçiniz</p>
                    </div>
                    <div class="caparv-card-body">
                        <div id="caparv-calendar-loading" class="caparv-loading" style="display:none;">
                            <div class="caparv-spinner"></div>
                            <p>Müsait saatler yükleniyor...</p>
                        </div>
                        
                        <div id="caparv-calendar-controls" class="caparv-calendar-controls" style="display:none;">
                            <button type="button" class="caparv-btn caparv-btn-sm caparv-btn-prev-week">
                                <i class="fa fa-chevron-left"></i>
                            </button>
                            <button type="button" class="caparv-btn caparv-btn-sm caparv-btn-next-week">
                                <i class="fa fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <div id="caparv-calendar-container" class="caparv-calendar-container" style="display:none;"></div>
                        
                        <div id="caparv-no-appointments" class="caparv-info-box" style="display:none;">
                            <div class="caparv-offline-doctor">
                                <h4>Bu hekim için online randevu müsait değil</h4>
                                <p>Randevu almak için lütfen klinikle iletişime geçin.</p>
                                <div id="caparv-clinic-contact-info"></div>
                            </div>
                        </div>
                    </div>
                    <div class="caparv-card-footer">
                        <button type="button" class="caparv-btn caparv-btn-secondary caparv-btn-prev">
                            <i class="fa fa-arrow-left"></i> Geri
                        </button>
                        <button type="button" class="caparv-btn caparv-btn-primary caparv-btn-next" disabled>
                            Devam Et <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="caparv-step-content" data-step="4">
                <div class="caparv-card">
                    <div class="caparv-card-header">
                        <h3>Hasta Bilgileri</h3>
                        <p>Lütfen bilgilerinizi eksiksiz doldurunuz</p>
                    </div>
                    <div class="caparv-card-body">
                        <form id="caparv-patient-form">
                            <input type="hidden" name="clinic_id" id="caparv-form-clinic-id">
                            <input type="hidden" name="doctor_id" id="caparv-form-doctor-id">
                            <input type="hidden" name="appointment_date" id="caparv-form-date">
                            <input type="hidden" name="appointment_time" id="caparv-form-time">
                            
                            <div class="caparv-form-row">
                                <div class="caparv-form-group">
                                    <label for="caparv-patient-number" class="caparv-label">
                                        TC Kimlik No <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="caparv-patient-number" 
                                        name="patient_number" 
                                        class="caparv-input" 
                                        required
                                        maxlength="11"
                                        placeholder="12345678901">
                                </div>
                                
                                <div class="caparv-form-group">
                                    <label for="caparv-patient-birthday" class="caparv-label">
                                        Doğum Tarihi
                                    </label>
                                    <input 
                                        type="date" 
                                        id="caparv-patient-birthday" 
                                        name="patient_birthday" 
                                        class="caparv-input">
                                </div>
                            </div>
                            
                            <div class="caparv-form-row">
                                <div class="caparv-form-group">
                                    <label for="caparv-patient-name" class="caparv-label">
                                        Ad <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="caparv-patient-name" 
                                        name="patient_name" 
                                        class="caparv-input" 
                                        required
                                        placeholder="Adınız">
                                </div>
                                
                                <div class="caparv-form-group">
                                    <label for="caparv-patient-surname" class="caparv-label">
                                        Soyad <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="caparv-patient-surname" 
                                        name="patient_surname" 
                                        class="caparv-input" 
                                        required
                                        placeholder="Soyadınız">
                                </div>
                            </div>
                            
                            <div class="caparv-form-row">
                                <div class="caparv-form-group">
                                    <label for="caparv-patient-phone" class="caparv-label">
                                        Telefon <span class="required">*</span>
                                    </label>
                                    <input 
                                        type="tel" 
                                        id="caparv-patient-phone" 
                                        name="patient_phone" 
                                        class="caparv-input" 
                                        required
                                        placeholder="5XX XXX XX XX">
                                </div>
                                
                                <div class="caparv-form-group">
                                    <label for="caparv-patient-email" class="caparv-label">
                                        E-posta
                                    </label>
                                    <input 
                                        type="email" 
                                        id="caparv-patient-email" 
                                        name="patient_email" 
                                        class="caparv-input"
                                        placeholder="ornek@email.com">
                                </div>
                            </div>
                            
                            <div class="caparv-form-group">
                                <label class="caparv-checkbox-label">
                                    <input 
                                        type="checkbox" 
                                        id="caparv-kvkk-checkbox" 
                                        name="kvkk_approval" 
                                        required>
                                    <span>
                                        <a href="#" id="caparv-kvkk-link">KVKK Aydınlatma Metnini</a> okudum ve kabul ediyorum <span class="required">*</span>
                                    </span>
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="caparv-card-footer">
                        <button type="button" class="caparv-btn caparv-btn-secondary caparv-btn-prev">
                            <i class="fa fa-arrow-left"></i> Geri
                        </button>
                        <button type="button" id="caparv-submit-btn" class="caparv-btn caparv-btn-success">
                            <i class="fa fa-check"></i> Randevu Oluştur
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="caparv-step-content" data-step="5">
                <div class="caparv-card caparv-success-card">
                    <div class="caparv-success-icon">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <h3 class="caparv-success-title">Randevunuz Oluşturuldu!</h3>
                    <p class="caparv-success-message">Randevu bilgileriniz aşağıdadır</p>
                    
                    <div class="caparv-appointment-summary">
                        <div class="caparv-summary-item">
                            <div class="summary-label">Hasta</div>
                            <div class="summary-value" id="caparv-summary-patient"></div>
                        </div>
                        <div class="caparv-summary-item">
                            <div class="summary-label">Klinik</div>
                            <div class="summary-value" id="caparv-summary-clinic"></div>
                        </div>
                        <div class="caparv-summary-item">
                            <div class="summary-label">Hekim</div>
                            <div class="summary-value" id="caparv-summary-doctor"></div>
                        </div>
                        <div class="caparv-summary-item">
                            <div class="summary-label">Tarih & Saat</div>
                            <div class="summary-value" id="caparv-summary-datetime"></div>
                        </div>
                        <div class="caparv-summary-item">
                            <div class="summary-label">PNR No</div>
                            <div class="summary-value" id="caparv-summary-pnr"></div>
                        </div>
                        <div class="caparv-summary-item" id="caparv-islem-kodu" style="display:none;">
                            <div class="summary-label">İşlem Kodu</div>
                            <div class="summary-value"><code id="caparv-islem-kodu-deger"></code></div>
                        </div>
                    </div>
                    
                    <div class="caparv-success-footer">
                        <button type="button" id="caparv-new-appointment-btn" class="caparv-btn caparv-btn-primary">
                            <i class="fa fa-plus"></i> Yeni Randevu
                        </button>
                        <button type="button" id="caparv-query-appointment-btn" class="caparv-btn caparv-btn-secondary">
                            <i class="fa fa-search"></i> Randevu Sorgula
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="caparv-kvkk-modal" class="caparv-modal" style="display:none;">
    <div class="caparv-modal-overlay"></div>
    <div class="caparv-modal-content">
        <div class="caparv-modal-header">
            <h3>KVKK Aydınlatma Metni</h3>
            <button type="button" class="caparv-modal-close">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="caparv-modal-body" id="caparv-kvkk-content">
            <div class="caparv-loading">
                <div class="caparv-spinner"></div>
                <p>Yükleniyor...</p>
            </div>
        </div>
        <div class="caparv-modal-footer">
            <button type="button" class="caparv-btn caparv-btn-secondary caparv-modal-close">
                Kapat
            </button>
        </div>
    </div>
</div>
