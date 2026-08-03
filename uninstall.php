<?php
/**
 * Eklenti silinirken temizlik.
 * ⚠ Tablo BILINCLI olarak silinmiyor: islem kayitlari teshis kaynagidir ve
 * eklentiyi yanlislikla silmek gecmis kayitlari yok etmemeli. Tabloyu
 * kaldirmak isterseniz veritabanindan elle silin.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}
delete_option('caparv_settings');
delete_option('caparv_db_version');
wp_clear_scheduled_hook('caparv_gunluk_temizlik');
