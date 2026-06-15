<?php
if (!defined('ABSPATH')) {
    exit;
}

$default_settings = array(
    'vkn' => '',
    'api_url' => 'https://clinic.dentsoft.com.tr/Api/v1',
    'bearer_token' => '',
    'enable_email_notifications' => '1',
    'primary_color' => '#00cc61',
    'success_message' => 'Randevunuz başarıyla oluşturuldu!'
);

$settings = wp_parse_args(get_option('dentsoft_settings', array()), $default_settings);

if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
    add_settings_error('dentsoft_messages', 'dentsoft_message', 'Ayarlar başarıyla kaydedildi!', 'success');
}
?>

<div class="wrap dentsoft-settings-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        DentSoft Online Randevu Ayarları
    </h1>
    
    <hr class="wp-header-end">
    
    <?php settings_errors('dentsoft_messages'); ?>
    
    <div class="dentsoft-settings-container">
        <form method="post" action="options.php" class="dentsoft-settings-form">
            <?php
            settings_fields('dentsoft_settings');
            ?>
            
            <div class="dentsoft-settings-sections">
                <div class="dentsoft-settings-section active" data-section="api">
                    <div class="section-header">
                        <span class="dashicons dashicons-cloud"></span>
                        <h2>API Ayarları</h2>
                    </div>
                    <div class="section-content">
                        <div class="dentsoft-field-group">
                            <label for="dentsoft_vkn" class="dentsoft-label">
                                Vergi Kimlik Numarası (VKN)
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text"
                                id="dentsoft_vkn"
                                name="dentsoft_settings[vkn]"
                                value="<?php echo esc_attr($settings['vkn']); ?>"
                                class="dentsoft-input"
                                placeholder="1234567890"
                                required>
                            <p class="dentsoft-help-text">
                                <span class="dashicons dashicons-info"></span>
                                DentSoft API'si için klinik VKN numaranızı girin. Bu alan zorunludur.
                            </p>
                        </div>
                        
                        <div class="dentsoft-field-group">
                            <label for="dentsoft_api_url" class="dentsoft-label">
                                API URL
                            </label>
                            <input 
                                type="url"
                                readonly
                                id="dentsoft_api_url"
                                name="dentsoft_settings[api_url]"
                                value="<?php echo esc_url($settings['api_url']); ?>"
                                class="dentsoft-input"
                                placeholder="https://clinic.dentsoft.com.tr/Api/v1">
                            <p class="dentsoft-help-text">
                                <span class="dashicons dashicons-warning"></span>
                                DentSoft API URL adresi. Varsayılan değeri değiştirmeniz önerilmez.
                            </p>
                        </div>
                        
                        <div class="dentsoft-field-group">
                            <label for="dentsoft_bearer_token" class="dentsoft-label">
                                Bearer Token
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text"
                                id="dentsoft_bearer_token"
                                name="dentsoft_settings[bearer_token]"
                                value="<?php echo esc_attr($settings['bearer_token']); ?>"
                                class="dentsoft-input"
                                placeholder="Token giriniz"
                                required>
                            <p class="dentsoft-help-text">
                                <span class="dashicons dashicons-lock"></span>
                                API istekleri için Bearer Token. Bu alan zorunludur.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="dentsoft-settings-section" data-section="notifications">
                    <div class="section-header">
                        <span class="dashicons dashicons-email"></span>
                        <h2>Bildirim Ayarları</h2>
                    </div>
                    <div class="section-content">
                        <div class="dentsoft-field-group">
                            <label class="dentsoft-toggle-label">
                                <input 
                                    type="checkbox"
                                    name="dentsoft_settings[enable_email_notifications]"
                                    value="1"
                                    <?php checked($settings['enable_email_notifications'], '1'); ?>
                                    class="dentsoft-toggle">
                                <span class="dentsoft-toggle-slider"></span>
                                <span class="dentsoft-toggle-text">E-posta Bildirimlerini Etkinleştir</span>
                            </label>
                            <p class="dentsoft-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Randevu oluşturulduğunda hem hastaya hem de yöneticiye e-posta gönderilir.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="dentsoft-settings-section" data-section="appearance">
                    <div class="section-header">
                        <span class="dashicons dashicons-art"></span>
                        <h2>Görünüm Ayarları</h2>
                    </div>
                    <div class="section-content">
                        <div class="dentsoft-field-group">
                            <label for="dentsoft_primary_color" class="dentsoft-label">
                                Ana Renk
                            </label>
                            <div class="dentsoft-color-picker">
                                <input 
                                    type="color"
                                    id="dentsoft_primary_color"
                                    name="dentsoft_settings[primary_color]"
                                    value="<?php echo esc_attr($settings['primary_color']); ?>"
                                    class="dentsoft-color-input">
                                <input 
                                    type="text"
                                    value="<?php echo esc_attr($settings['primary_color']); ?>"
                                    class="dentsoft-color-text"
                                    readonly>
                            </div>
                            <p class="dentsoft-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Randevu formunda kullanılacak ana renk.
                            </p>
                        </div>
                        
                        <div class="dentsoft-field-group">
                            <label for="dentsoft_success_message" class="dentsoft-label">
                                Başarı Mesajı
                            </label>
                            <input 
                                type="text"
                                id="dentsoft_success_message"
                                name="dentsoft_settings[success_message]"
                                value="<?php echo esc_attr($settings['success_message']); ?>"
                                class="dentsoft-input"
                                placeholder="Randevunuz başarıyla oluşturuldu!">
                            <p class="dentsoft-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Randevu başarıyla oluşturulduğunda gösterilecek mesaj.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="dentsoft-settings-section" data-section="shortcode">
                    <div class="section-header">
                        <span class="dashicons dashicons-editor-code"></span>
                        <h2>Shortcode Kullanımı</h2>
                    </div>
                    <div class="section-content">
                        <div class="dentsoft-shortcode-box">
                            <h3>Randevu Formu Shortcode'u</h3>
                            <div class="dentsoft-shortcode-display">
                                <code>[dentsoft_appointment_form]</code>
                                <button type="button" class="dentsoft-copy-btn" onclick="navigator.clipboard.writeText('[dentsoft_appointment_form]')">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    Kopyala
                                </button>
                            </div>
                            <p class="dentsoft-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Bu shortcode'u randevu formunu göstermek istediğiniz sayfaya ekleyin.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dentsoft-settings-footer">
                <?php submit_button('Değişiklikleri Kaydet', 'primary large', 'submit', false); ?>
                <button type="button" class="button button-secondary dentsoft-reset-btn" onclick="return confirm('Tüm ayarları varsayılan değerlere döndürmek istediğinize emin misiniz?')">
                    <span class="dashicons dashicons-update"></span>
                    Varsayılana Sıfırla
                </button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const colorInput = $('#dentsoft_primary_color');
    const colorText = $('.dentsoft-color-text');
    
    colorInput.on('input change', function() {
        colorText.val($(this).val());
    });
    
    $('.dentsoft-copy-btn').on('click', function() {
        const btn = $(this);
        const originalText = btn.find('.dashicons').next().text();
        
        btn.find('.dashicons').next().text('Kopyalandı!');
        btn.addClass('copied');
        
        setTimeout(function() {
            btn.find('.dashicons').next().text('Kopyala');
            btn.removeClass('copied');
        }, 2000);
    });
    
    $('.dentsoft-reset-btn').on('click', function(e) {
        if (confirm('Tüm ayarları varsayılan değerlere döndürmek istediğinize emin misiniz?')) {
            $('#dentsoft_vkn').val('');
            $('#dentsoft_api_url').val('https://clinic.dentsoft.com.tr/Api/v1');
            $('#dentsoft_bearer_token').val('');
            $('input[name="dentsoft_settings[enable_email_notifications]"]').prop('checked', true);
            $('#dentsoft_primary_color').val('#00cc61');
            colorText.val('#00cc61');
            $('#dentsoft_success_message').val('Randevunuz başarıyla oluşturuldu!');
        }
    });
    
    const apiUrlInput = $('#dentsoft_api_url');
    const originalApiUrl = apiUrlInput.val();
    
    apiUrlInput.on('keydown paste cut', function(e) {
        e.preventDefault();
        return false;
    });
    
    $('form').on('submit', function() {
        apiUrlInput.val(originalApiUrl);
    });
});</script>