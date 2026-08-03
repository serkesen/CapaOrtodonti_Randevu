<?php
/**
 * Çapa Randevu — İşlem Kayıtları (operasyon izleme)
 *
 * ⚠ Bu ekran bir HASTA LİSTESİ DEĞİLDİR. Klinik randevu defteri kendi
 * panelindedir; aynı listeyi burada ikinci kez tutmak iki ayrı kişisel
 * veri deposu demek olurdu. Burada yalnız işlem izi görünür.
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$t = Caparv_DB::table();

$var = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t);

$ozet = array('bugun' => 0, 'hafta' => 0, 'hata' => 0, 'test' => 0, 'toplam' => 0);
if ($var) {
    $ozet['bugun']  = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE test=0 AND DATE(created_at)=%s", current_time('Y-m-d')));
    $ozet['hafta']  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE test=0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $ozet['hata']   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE test=0 AND durum <> 'basarili' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $ozet['test']   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t} WHERE test=1");
    $ozet['toplam'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t}");
}
?>
<div class="wrap caparv-wrap">
    <h1>Çapa Randevu — İşlem Kayıtları</h1>

    <?php if (!$var) : ?>
        <div class="notice notice-error"><p>
            İşlem kaydı tablosu bulunamadı. Sayfayı yenileyin — tablo sürüm kontrolüyle
            kendiliğinden kurulur. Sorun sürerse veritabanı kullanıcısının
            <code>CREATE TABLE</code> yetkisini kontrol edin.
        </p></div>
    <?php endif; ?>

    <?php if (Caparv_Plugin::test_modu()) : ?>
        <div class="notice notice-warning"><p>
            <strong>TEST MODU AÇIK.</strong> Hastalara e-posta gitmiyor, tüm bildirimler
            personel adresine yönleniyor. Ölçüm olayları <code>test</code> etiketiyle
            gönderiliyor. Canlıya almadan önce Ayarlar'dan kapatın.
        </p></div>
    <?php endif; ?>

    <div class="caparv-ozet">
        <div class="caparv-kutu"><span class="caparv-sayi"><?php echo esc_html($ozet['bugun']); ?></span><span class="caparv-etiket">Bugün</span></div>
        <div class="caparv-kutu"><span class="caparv-sayi"><?php echo esc_html($ozet['hafta']); ?></span><span class="caparv-etiket">Son 7 gün</span></div>
        <div class="caparv-kutu <?php echo $ozet['hata'] > 0 ? 'caparv-uyari' : ''; ?>"><span class="caparv-sayi"><?php echo esc_html($ozet['hata']); ?></span><span class="caparv-etiket">Son 7 günde hata</span></div>
        <div class="caparv-kutu"><span class="caparv-sayi"><?php echo esc_html($ozet['test']); ?></span><span class="caparv-etiket">Test kaydı</span></div>
    </div>

    <?php
    // Anonim sayac tablosunun tip dagilimi. Sayac bu eklentinin degil,
    // CapaOrtodonti_Site/site-customizations.php'nin tablosudur; buradan
    // yalniz OKUNUR. Amac: olcum hattinin calistigini ve test kayitlarinin
    // 'test' etiketiyle ayrildigini canliya almadan gorebilmek.
    $say_t = $wpdb->prefix . 'capa_randevu_sayac';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $say_t)) === $say_t) :
        $say = $wpdb->get_results(
            "SELECT tip, SUM(adet) AS n FROM {$say_t}
             WHERE gun >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY tip ORDER BY n DESC", ARRAY_A);
    ?>
        <p class="caparv-arac">
            <strong>Sayaç (son 7 gün):</strong>
            <?php if (empty($say)) : ?>
                <span class="caparv-not">kayıt yok</span>
            <?php else : foreach ($say as $sr) : ?>
                <span class="caparv-rozet <?php echo $sr['tip'] === 'test' ? 'caparv-notr' : 'caparv-ok'; ?>">
                    <?php echo esc_html($sr['tip']); ?>: <?php echo (int) $sr['n']; ?>
                </span>
            <?php endforeach; endif; ?>
            <span class="caparv-not">
                <code>test</code> satırları dashboard'a gönderilmez.
                Buradaki dağılım ölçüm hattının doğru etiketlediğini gösterir.
            </span>
        </p>
    <?php endif; ?>

    <p class="caparv-arac">
        <label>Durum:
            <select id="caparv-f-durum">
                <option value="hepsi">Hepsi</option>
                <option value="basarili">Başarılı</option>
                <option value="api_hatasi">API hatası</option>
                <option value="dogrulama_hatasi">Doğrulama hatası</option>
                <option value="iptal">İptal</option>
            </select>
        </label>
        <label>Kayıt:
            <select id="caparv-f-test">
                <option value="0">Yalnız gerçek</option>
                <option value="1">Yalnız test</option>
                <option value="hepsi">Hepsi</option>
            </select>
        </label>
        <button class="button" id="caparv-yenile">Yenile</button>
        <button class="button button-link-delete" id="caparv-test-sil">Test kayıtlarını sil (<?php echo esc_html($ozet['test']); ?>)</button>
        <span class="caparv-not">Kayıtlar <?php echo esc_html(Caparv_DB::SAKLAMA_GUN); ?> gün sonra otomatik silinir.</span>
    </p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:140px">Zaman</th>
                <th style="width:100px">İşlem Kodu</th>
                <th style="width:110px">PNR</th>
                <th style="width:120px">Durum</th>
                <th>Hekim</th>
                <th style="width:150px">Randevu</th>
                <th style="width:60px">Adım</th>
                <th style="width:90px">Cihaz</th>
                <th>Hata</th>
            </tr>
        </thead>
        <tbody id="caparv-govde">
            <tr><td colspan="9">Yükleniyor…</td></tr>
        </tbody>
    </table>

    <p id="caparv-sayfalama"></p>
</div>

<script>
jQuery(function ($) {
    var sayfa = 1;

    function esc(v) {
        return $('<div>').text(v === null || typeof v === 'undefined' ? '' : String(v)).html();
    }

    function rozet(d) {
        var ad = { basarili: 'Başarılı', api_hatasi: 'API hatası', dogrulama_hatasi: 'Doğrulama', iptal: 'İptal' }[d] || d || '-';
        var cls = (d === 'basarili') ? 'caparv-ok' : (d === 'iptal' ? 'caparv-notr' : 'caparv-kotu');
        return '<span class="caparv-rozet ' + cls + '">' + esc(ad) + '</span>';
    }

    function yukle() {
        $.post(caparvAdmin.ajaxUrl, {
            action: 'caparv_get_appointments',
            nonce: caparvAdmin.nonce,
            sayfa: sayfa,
            durum: $('#caparv-f-durum').val(),
            test: $('#caparv-f-test').val()
        }, function (r) {
            if (!r || !r.success) {
                $('#caparv-govde').html('<tr><td colspan="9">Kayıtlar okunamadı.</td></tr>');
                return;
            }
            var k = r.data.kayitlar || [];
            if (!k.length) {
                $('#caparv-govde').html('<tr><td colspan="9">Kayıt yok.</td></tr>');
                $('#caparv-sayfalama').text('');
                return;
            }
            var h = '';
            k.forEach(function (s) {
                h += '<tr' + (String(s.test) === '1' ? ' class="caparv-test-satir"' : '') + '>'
                  + '<td>' + esc(s.created_at) + '</td>'
                  + '<td><code>' + esc(s.islem_kodu) + '</code></td>'
                  + '<td>' + esc(s.pnr_no || '-') + '</td>'
                  + '<td>' + rozet(s.durum) + '</td>'
                  + '<td>' + esc(s.hekim_adi || '-') + '</td>'
                  + '<td>' + esc((s.randevu_tarihi || '-') + ' ' + (s.randevu_saati || '')) + '</td>'
                  + '<td>' + esc(s.adim) + '</td>'
                  + '<td>' + esc(s.cihaz) + '</td>'
                  + '<td>' + esc(s.hata_ozeti || '') + '</td>'
                  + '</tr>';
            });
            $('#caparv-govde').html(h);
            $('#caparv-sayfalama').text('Sayfa ' + r.data.sayfa + ' / ' + Math.max(1, r.data.sayfalar) + ' — toplam ' + r.data.toplam + ' kayıt');
        });
    }

    $('#caparv-yenile').on('click', function (e) { e.preventDefault(); sayfa = 1; yukle(); });
    $('#caparv-f-durum, #caparv-f-test').on('change', function () { sayfa = 1; yukle(); });

    $('#caparv-test-sil').on('click', function (e) {
        e.preventDefault();
        if (!window.confirm('Tüm test kayıtları silinecek. Onaylıyor musunuz?')) { return; }
        $.post(caparvAdmin.ajaxUrl, { action: 'caparv_purge_test', nonce: caparvAdmin.nonce }, function () {
            location.reload();
        });
    });

    yukle();
});
</script>
