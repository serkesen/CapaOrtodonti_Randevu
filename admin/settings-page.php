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

$settings = Caparv_Plugin::settings();
$settings = wp_parse_args($settings, $default_settings);

if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
    add_settings_error('caparv_messages', 'caparv_message', 'Ayarlar başarıyla kaydedildi!', 'success');
}
?>

<div class="wrap caparv-settings-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        Çapa Randevu — Ayarlar
    </h1>
    
    <hr class="wp-header-end">
    
    <?php settings_errors('caparv_messages'); ?>
    
    <div class="caparv-settings-container">
        <form method="post" action="options.php" class="caparv-settings-form">
            <?php
            settings_fields('caparv_settings_group');
            ?>
            
            <div class="caparv-settings-sections">
                <div class="caparv-settings-section active" data-section="api">
                    <div class="section-header">
                        <span class="dashicons dashicons-cloud"></span>
                        <h2>API Ayarları</h2>
                    </div>
                    <div class="section-content">
                        <div class="caparv-field-group">
                            <label for="caparv_vkn" class="caparv-label">
                                Vergi Kimlik Numarası (VKN)
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text"
                                id="caparv_vkn"
                                name="caparv_settings[vkn]"
                                value="<?php echo esc_attr($settings['vkn']); ?>"
                                class="caparv-input"
                                placeholder="1234567890"
                                required>
                            <p class="caparv-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Randevu API'si için klinik VKN numaranızı girin. Bu alan zorunludur.
                            </p>
                        </div>
                        
                        <div class="caparv-field-group">
                            <label for="caparv_api_url" class="caparv-label">
                                API URL
                            </label>
                            <input 
                                type="url"
                                readonly
                                id="caparv_api_url"
                                name="caparv_settings[api_url]"
                                value="<?php echo esc_url($settings['api_url']); ?>"
                                class="caparv-input"
                                placeholder="https://clinic.dentsoft.com.tr/Api/v1">
                            <p class="caparv-help-text">
                                <span class="dashicons dashicons-warning"></span>
                                Randevu API URL adresi. Varsayılan değeri değiştirmeniz önerilmez.
                            </p>
                        </div>
                        
                        <div class="caparv-field-group">
                            <label for="caparv_bearer_token" class="caparv-label">
                                Bearer Token
                                <span class="required">*</span>
                            </label>
                            <input 
                                type="text"
                                id="caparv_bearer_token"
                                name="caparv_settings[bearer_token]"
                                value="<?php echo esc_attr($settings['bearer_token']); ?>"
                                class="caparv-input"
                                placeholder="Token giriniz"
                                required>
                            <p class="caparv-help-text">
                                <span class="dashicons dashicons-lock"></span>
                                API istekleri için Bearer Token. Bu alan zorunludur.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="caparv-settings-section" data-section="notifications">
                    <div class="section-header">
                        <span class="dashicons dashicons-email"></span>
                        <h2>Bildirim Ayarları</h2>
                    </div>
                    <div class="section-content">
                        <div class="caparv-field-group">
                            <label class="caparv-label" for="caparv_bildirim_adresleri">Bildirim E-posta Adresleri</label>
                            <input type="text" class="caparv-input" id="caparv_bildirim_adresleri"
                                name="caparv_settings[bildirim_adresleri]"
                                value="<?php echo esc_attr(implode(', ', Caparv_Plugin::bildirim_adresleri())); ?>"
                                placeholder="ornek@site.com, ikinci@site.com">
                            <p class="caparv-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Randevu bildirimleri bu adreslerin <strong>tümüne</strong> gider.
                                Birden fazla adresi virgülle ayırın. Geçersiz adresler kaydederken atılır.
                                <br><strong>Test modu açıkken yalnızca listedeki ilk adrese</strong> gönderilir,
                                hastalara hiç e-posta gitmez.
                            </p>
                        </div>

                        <div class="caparv-field-group">
                            <label class="caparv-toggle-label">
                                <input type="checkbox" value="1"
                                    name="caparv_settings[test_modu]"
                                    <?php checked(!empty($settings['test_modu'])); ?>
                                    class="caparv-toggle">
                                <span class="caparv-toggle-slider"></span>
                                <span class="caparv-toggle-text">Test Modu</span>
                            </label>
                            <p class="caparv-help-text">
                                <span class="dashicons dashicons-warning"></span>
                                Açıkken hastalara e-posta gitmez, işlem kayıtları <code>test</code> olarak
                                işaretlenir, ölçüm olayları <code>test</code> etiketiyle gönderilir.
                                <strong>Randevular yine gerçek klinik sistemine iletilir</strong> —
                                test randevularını klinik panelinden elle silin. Canlıya almadan önce kapatın.
                            </p>
                        </div>

                    <div class="caparv-field-group">
                            <label class="caparv-toggle-label">
                                <input 
                                    type="checkbox"
                                    name="caparv_settings[enable_email_notifications]"
                                    value="1"
                                    <?php checked($settings['enable_email_notifications'], '1'); ?>
                                    class="caparv-toggle">
                                <span class="caparv-toggle-slider"></span>
                                <span class="caparv-toggle-text">E-posta Bildirimlerini Etkinleştir</span>
                            </label>
                            <p class="caparv-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Randevu oluşturulduğunda hem hastaya hem de yöneticiye e-posta gönderilir.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="caparv-settings-section" data-section="appearance">
                    <div class="section-header">
                        <span class="dashicons dashicons-art"></span>
                        <h2>Görünüm Ayarları</h2>
                    </div>
                    <div class="section-content">
                        <div class="caparv-field-group">
                            <label for="caparv_primary_color" class="caparv-label">
                                Ana Renk
                            </label>
                            <div class="caparv-color-picker">
                                <input 
                                    type="color"
                                    id="caparv_primary_color"
                                    name="caparv_settings[primary_color]"
                                    value="<?php echo esc_attr($settings['primary_color']); ?>"
                                    class="caparv-color-input">
                                <input 
                                    type="text"
                                    value="<?php echo esc_attr($settings['primary_color']); ?>"
                                    class="caparv-color-text"
                                    readonly>
                            </div>
                            <p class="caparv-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Randevu formunda kullanılacak ana renk.
                            </p>
                        </div>
                        
                        <div class="caparv-field-group">
                            <label for="caparv_success_message" class="caparv-label">
                                Başarı Mesajı
                            </label>
                            <input 
                                type="text"
                                id="caparv_success_message"
                                name="caparv_settings[success_message]"
                                value="<?php echo esc_attr($settings['success_message']); ?>"
                                class="caparv-input"
                                placeholder="Randevunuz başarıyla oluşturuldu!">
                            <p class="caparv-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Randevu başarıyla oluşturulduğunda gösterilecek mesaj.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="caparv-settings-section" data-section="mailler">
                    <div class="section-header">
                        <span class="dashicons dashicons-email-alt"></span>
                        <h2>E-posta Şablonları</h2>
                    </div>
                    <div class="section-content">

                        <p class="caparv-help-text" style="margin-bottom:18px;">
                            <span class="dashicons dashicons-info"></span>
                            Aşağıdaki alanlar şu an gönderilen metinlerle doludur.
                            <strong>Bir alanı tamamen boşaltırsanız varsayılan metne geri döner.</strong>
                            Gövdede HTML kullanabilirsiniz.
                        </p>

                        <div class="caparv-field-group" style="background:#f6f7f7;border-left:3px solid #2271b1;padding:12px 14px;">
                            <strong>Kullanılabilir yer tutucular</strong>
                            <p class="caparv-help-text" style="margin-top:6px;">
                                <code>{detay_tablosu}</code> randevu bilgileri tablosu ·
                                <code>{buton}</code> varsa bağlantı butonu ·
                                <code>{klinik}</code> ·
                                <code>{klinik_adres}</code> ·
                                <code>{klinik_telefon}</code> ·
                                <code>{hasta_adi}</code> ·
                                <code>{hekim}</code> ·
                                <code>{tarih}</code> ·
                                <code>{pnr}</code> ·
                                <code>{telefon}</code> ·
                                <code>{eposta}</code> ·
                                <code>{tc}</code> ·
                                <code>{dogum}</code>
                                <br>Karşılığı olmayan yer tutucular gönderim sırasında silinir.
                            </p>
                        </div>

                        <?php foreach (Caparv_Plugin::varsayilan_sablonlar() as $k => $v) :
                            $mevcut = Caparv_Plugin::sablon($k); ?>
                            <div class="caparv-field-group" style="border-top:1px solid #e0e0e0;padding-top:16px;margin-top:16px;">
                                <label class="caparv-label" style="font-size:14px;"><?php echo esc_html($v['ad']); ?></label>

                                <label class="caparv-label" for="sb-<?php echo esc_attr($k); ?>-konu" style="font-weight:400;">Konu</label>
                                <input type="text" class="caparv-input" id="sb-<?php echo esc_attr($k); ?>-konu"
                                    name="caparv_settings[sablonlar][<?php echo esc_attr($k); ?>][konu]"
                                    value="<?php echo esc_attr($mevcut['konu']); ?>">

                                <label class="caparv-label" for="sb-<?php echo esc_attr($k); ?>-govde" style="font-weight:400;margin-top:8px;">Gövde</label>
                                <textarea class="caparv-input" id="sb-<?php echo esc_attr($k); ?>-govde" rows="6"
                                    style="font-family:Menlo,Consolas,monospace;font-size:12px;line-height:1.6;"
                                    name="caparv_settings[sablonlar][<?php echo esc_attr($k); ?>][govde]"><?php echo esc_textarea($mevcut['govde']); ?></textarea>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>

                <div class="caparv-settings-section" data-section="shortcode">
                    <div class="section-header">
                        <span class="dashicons dashicons-editor-code"></span>
                        <h2>Shortcode Kullanımı</h2>
                    </div>
                    <div class="section-content">
                        <div class="caparv-shortcode-box">
                            <h3>Randevu Formu Shortcode'u</h3>
                            <div class="caparv-shortcode-display">
                                <code>[caparv_randevu_formu]</code>
                                <button type="button" class="caparv-copy-btn" onclick="navigator.clipboard.writeText('[caparv_randevu_formu]')">
                                    <span class="dashicons dashicons-clipboard"></span>
                                    Kopyala
                                </button>
                            </div>
                            <p class="caparv-help-text">
                                <span class="dashicons dashicons-info"></span>
                                Bu shortcode'u randevu formunu göstermek istediğiniz sayfaya ekleyin.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="caparv-settings-footer">
                <?php submit_button('Değişiklikleri Kaydet', 'primary large', 'submit', false); ?>
                <button type="button" class="button button-secondary caparv-reset-btn" onclick="return confirm('Tüm ayarları varsayılan değerlere döndürmek istediğinize emin misiniz?')">
                    <span class="dashicons dashicons-update"></span>
                    Varsayılana Sıfırla
                </button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const colorInput = $('#caparv_primary_color');
    const colorText = $('.caparv-color-text');
    
    colorInput.on('input change', function() {
        colorText.val($(this).val());
    });
    
    $('.caparv-copy-btn').on('click', function() {
        const btn = $(this);
        const originalText = btn.find('.dashicons').next().text();
        
        btn.find('.dashicons').next().text('Kopyalandı!');
        btn.addClass('copied');
        
        setTimeout(function() {
            btn.find('.dashicons').next().text('Kopyala');
            btn.removeClass('copied');
        }, 2000);
    });
    
    $('.caparv-reset-btn').on('click', function(e) {
        if (confirm('Tüm ayarları varsayılan değerlere döndürmek istediğinize emin misiniz?')) {
            $('#caparv_vkn').val('');
            $('#caparv_api_url').val('https://clinic.dentsoft.com.tr/Api/v1');
            $('#caparv_bearer_token').val('');
            $('input[name="caparv_settings[enable_email_notifications]"]').prop('checked', true);
            $('#caparv_primary_color').val('#00cc61');
            colorText.val('#00cc61');
            $('#caparv_success_message').val('Randevunuz başarıyla oluşturuldu!');
        }
    });
    
    const apiUrlInput = $('#caparv_api_url');
    const originalApiUrl = apiUrlInput.val();
    
    apiUrlInput.on('keydown paste cut', function(e) {
        e.preventDefault();
        return false;
    });
    
    $('form').on('submit', function() {
        apiUrlInput.val(originalApiUrl);
    });
});</script>