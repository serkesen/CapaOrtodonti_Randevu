# DentSoft Online Randevu Sistemi v2.0.0

DentSoft kullanan klinikler ve diş hekimleri için özel olarak tasarlanmış, modern ve kullanımı kolay bir online randevu sistemi WordPress eklentisidir.

## 🚀 Özellikler

### Kullanıcı Özellikleri

-   ✅ Modern ve responsive tasarım
-   ✅ 5 adımlı kullanıcı dostu randevu süreci
-   ✅ Klinik, hekim, tarih ve saat seçimi
-   ✅ KVKK uyumlu veri toplama
-   ✅ Anlık randevu onayı ve PNR no ile takip
-   ✅ E-posta bildirimleri

### Admin Özellikleri

-   📊 Gelişmiş randevu yönetim paneli
-   🔍 Arama ve filtreleme özellikleri
-   📈 İstatistiksel raporlama
-   ⚙️ Kolay ayar yönetimi
-   🎨 Özelleştirilebilir renk teması
-   📧 E-posta bildirim ayarları

### Teknik Özellikler

-   🔒 Güvenli veri işleme ve sanitizasyon
-   🎯 Optimize edilmiş performans
-   📱 Tam responsive tasarım
-   🌍 Çoklu dil desteği hazır
-   🔌 DentSoft API entegrasyonu
-   💾 Veritabanı optimizasyonu

## 📋 Gereksinimler

-   WordPress 5.8 veya üzeri
-   PHP 7.4 veya üzeri
-   MySQL 5.6 veya üzeri
-   DentSoft hesabı ve API erişimi

## 📦 Kurulum

### 1. Eklenti Kurulumu

```bash
1. Eklentiyi /wp-content/plugins/ dizinine yükleyin
2. WordPress admin panelinden Eklentiler > Yüklü Eklentiler sayfasına gidin
3. "DentSoft Online Randevu Sistemi" eklentisini etkinleştirin
```

### 2. Yapılandırma

1. WordPress admin panelinde **DentSoft > Ayarlar** sayfasına gidin
2. **VKN (Vergi Kimlik Numarası)** alanına DentSoft hesabınızın VKN numarasını girin
3. API URL varsayılan olarak doğru şekilde ayarlanmıştır (değiştirmeyin)
4. E-posta bildirimlerini aktif/pasif yapın
5. Ana rengi özelleştirin (opsiyonel)
6. Değişiklikleri kaydedin

### 3. Kullanım

Randevu formunu sayfaya eklemek için shortcode kullanın:

```
[dentsoft_appointment_form]
```

## 🎨 Özelleştirme

### Renk Teması

Ayarlar sayfasından ana rengi değiştirebilirsiniz. Seçtiğiniz renk otomatik olarak tüm form bileşenlerine uygulanır.

### E-posta Bildirimleri

-   Hasta e-postası: Randevu oluşturulduğunda hastaya otomatik bildirim
-   Admin e-postası: Yöneticiye yeni randevu bildirimi

### CSS Özelleştirmeleri

Tema dosyanıza özel CSS ekleyerek görünümü özelleştirebilirsiniz:

```css
.dentsoft-appointment-wrapper {
    --dentsoft-primary: #yourcolor;
}
```

## 🔧 Teknik Detaylar

### Dosya Yapısı

```
online.dentsoft.com.tr_playground/
├── dentsoft.php                 # Ana plugin dosyası
├── README.md                    # Dokümantasyon
├── uninstall.php               # Kaldırma scripti
├── admin/                      # Admin panel dosyaları
│   ├── admin-page.php         # Randevu listesi
│   └── settings-page.php      # Ayarlar sayfası
├── assets/                     # Statik dosyalar
│   ├── css/
│   │   ├── admin.css          # Admin stilleri
│   │   └── main-styles.css    # Frontend stilleri
│   ├── js/
│   │   └── app.js             # Ana JavaScript
│   └── plugins/               # Harici kütüphaneler
├── includes/                   # PHP sınıfları
│   └── ajax-handlers.php      # AJAX işleyicileri
└── public/                     # Frontend dosyaları
    └── form-template.php      # Randevu form şablonu
```

### Veritabanı Tablosu

Plugin, `wp_dentsoft_appointments` tablosunu oluşturur:

| Sütun              | Tip          | Açıklama                                         |
| ------------------ | ------------ | ------------------------------------------------ |
| id                 | bigint(20)   | Otomatik artan ID                                |
| patient_number     | varchar(100) | TC/Pasaport no                                   |
| patient_name       | varchar(100) | Hasta adı                                        |
| patient_surname    | varchar(100) | Hasta soyadı                                     |
| patient_phone      | varchar(50)  | Telefon numarası                                 |
| patient_birthday   | date         | Doğum tarihi                                     |
| patient_email      | varchar(100) | E-posta adresi                                   |
| clinic_name        | varchar(255) | Klinik adı                                       |
| clinic_address     | text         | Klinik adresi                                    |
| clinic_phone       | varchar(50)  | Klinik telefonu                                  |
| doctor_name        | varchar(100) | Hekim adı                                        |
| pnr_no             | varchar(50)  | PNR numarası                                     |
| appointment_date   | datetime     | Randevu tarihi                                   |
| appointment_status | varchar(20)  | Durum (pending, confirmed, completed, cancelled) |
| created_at         | datetime     | Oluşturulma tarihi                               |
| updated_at         | datetime     | Güncellenme tarihi                               |

### AJAX Endpoint'leri

-   `dentsoft_save_appointment`: Randevu kaydetme
-   `dentsoft_get_appointments`: Randevuları listeleme
-   `dentsoft_update_appointment_status`: Durum güncelleme
-   `dentsoft_delete_appointment`: Randevu silme

## 🔒 Güvenlik

-   ✅ Nonce doğrulaması
-   ✅ Capability kontrolleri
-   ✅ Input sanitizasyonu
-   ✅ Output escaping
-   ✅ Prepared statements
-   ✅ CSRF koruması

## 🌐 API Entegrasyonu

Plugin, DentSoft API v1 ile entegre çalışır:

### Kullanılan Endpoint'ler

-   `GET /Clinic/List/{vkn}` - Klinik listesi
-   `GET /Clinic/DoctorList/{vkn}/{clinicId}` - Hekim listesi
-   `GET /Appointment/Doctor/{clinicId}/{doctorId}/{date}/{range}` - Müsait saatler
-   `POST /Appointment/New/{clinicId}/{doctorId}` - Yeni randevu
-   `POST /ApprovalDataShare` - KVKK metni

## 📱 Responsive Tasarım

Plugin, tüm cihazlarda mükemmel çalışır:

-   📱 Mobil cihazlar (320px+)
-   💻 Tablet'ler (768px+)
-   🖥️ Masaüstü (1024px+)
-   📺 Geniş ekranlar (1200px+)

## 🐛 Sorun Giderme

### VKN Hatası

**Sorun:** "VKN yapılandırması eksik" hatası
**Çözüm:** DentSoft > Ayarlar sayfasından VKN numarasını girin

### API Bağlantı Hatası

**Sorun:** Klinik veya hekim listesi yüklenmiyor
**Çözüm:**

1. VKN numarasının doğru olduğunu kontrol edin
2. API URL'nin değiştirilmediğinden emin olun
3. Sunucu firewall ayarlarını kontrol edin

### E-posta Gönderilmiyor

**Sorun:** Bildirim e-postaları gönderilmiyor
**Çözüm:**

1. Ayarlardan e-posta bildirimlerinin aktif olduğunu kontrol edin
2. WordPress e-posta ayarlarını kontrol edin
3. SMTP plugin kullanmayı düşünün

## 📝 Changelog

### Version 2.0.0 (2024-10-14)

-   ♻️ Tamamen yeniden yazıldı
-   🎨 Modern ve responsive tasarım
-   🚀 Performans iyileştirmeleri
-   🔒 Gelişmiş güvenlik
-   📊 Yeni admin paneli
-   🎯 Daha iyi kullanıcı deneyimi
-   📧 E-posta bildirim sistemi
-   🎨 Özelleştirilebilir renk teması

### Version 1.0.0 (2024-01-01)

-   🎉 İlk sürüm

## 👨‍💻 Geliştirici Notları

### Hook'lar

```php
do_action('dentsoft_appointment_created', $appointment_id, $data);
do_action('dentsoft_appointment_status_changed', $appointment_id, $status);
do_action('dentsoft_appointment_deleted', $appointment_id);
```

### Filter'lar

```php
$settings = apply_filters('dentsoft_settings', $settings);
```

## 📞 Destek

Teknik destek ve sorularınız için:

-   📧 E-posta: info@dentsoft.com.tr
-   🌐 Website: https://dentsoft.com.tr
-   📱 Telefon: Klinik iletişim bilgileri

## 📄 Lisans

GPL v2 veya üzeri

## 🙏 Teşekkürler

Bu eklentiyi kullandığınız için teşekkür ederiz!
