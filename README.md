# CapaOrtodonti Randevu

capaortodonti.com üzerinde çalışan **`capa-randevu` v1.0.0** online randevu eklentisinin kaynağı.

## Durum (30 Temmuz 2026)

Bu depo başlangıçta eski `DentSoftOnlineRandevu` eklentisi için kurulmuştu. 28 Temmuz 2026'da
eklenti markasızlaştırılıp sadeleştirildi (369 dosya / 86 MB → 18 dosya / 1,3 MB) ve
`capa-randevu` adıyla yeniden kuruldu, ancak **bu sürüm depoya hiç alınmamıştı**. 30 Temmuz'da
canlı sunucudan alınıp buraya eklendi.

- `.cpanel.yml` hedefi eski `DentSoftOnlineRandevu` klasörünü gösteriyordu → **`capa-randevu`
  olarak düzeltildi.**
- `.cpanel.yml` bir **beyaz listedir**: burada listelenmeyen hiçbir dosya sunucuya kopyalanmaz.
  Yeni dosya eklenirse buraya da eklenmelidir.
- `assets/` altındaki eski DentSoft dönemi dosyaları (355 adet) hâlâ depoda duruyor ama
  **beyaz listede olmadıkları için sunucuya gitmezler**. Temizlenmeleri ayrı bir iş.

## Yapı (canlıda çalışan 18 dosya)

```
capa-randevu.php                       eklenti girişi
uninstall.php
includes/class-ajax.php                AJAX + e-posta şablonu (dentsoft_email_shell)
includes/class-db.php
admin/admin-page.php
admin/settings-page.php
public/form-template.php               5 adımlı randevu formu
assets/css/{main-styles,admin,icons}.css
assets/js/app.js                       adım akışı + dataLayer + anonim sayaç pingi
assets/fonts/fa-solid-900.woff2
assets/vendor/select2/{select2.full.min.js,select2.min.css,i18n/tr.js}
assets/vendor/sweetalert2/{sweetalert2.min.js,sweetalert2.min.css}
assets/vendor/jquery.blockUI.js
```

## Dikkat

- **`GENEL_MUAYENE` sentineline dokunma.**
- Randevu bilgileri KVKK gereği siteye kaydedilmiyor; yalnız `(gün, tip, hekim)` sayacı artıyor.
- `app.js` içinde `assets/img/default-avatar.png` referansı var ama **bu dosya eklentide yok** —
  hekim fotoğrafı bulunamazsa 404 döner. Düzeltilmesi gereken küçük bir açık.
- Deploy: cPanel → Git Version Control → Update from Remote → Deploy HEAD → LiteSpeed Purge All.
