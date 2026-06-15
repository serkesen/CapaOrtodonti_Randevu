<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap dentsoft-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-calendar-alt"></span>
        DentSoft Randevu Sistemi
    </h1>
    
    <hr class="wp-header-end">
    
    <div class="dentsoft-admin-container">
        <div class="dentsoft-filters">
            <div class="filter-group">
                <input type="text" id="dentsoft-search" class="dentsoft-search-input" placeholder="PNR, hasta adı veya telefon ile ara...">
            </div>
            <div class="filter-group">
                <select id="dentsoft-status-filter" class="dentsoft-select">
                    <option value="">Tüm Durumlar</option>
                    <option value="pending">Bekliyor</option>
                    <option value="confirmed">Onaylandı</option>
                    <option value="completed">Tamamlandı</option>
                    <option value="cancelled">İptal Edildi</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="button" id="dentsoft-filter-btn" class="button button-primary">Filtrele</button>
                <button type="button" id="dentsoft-refresh-btn" class="button">Yenile</button>
            </div>
        </div>
        
        <div class="dentsoft-stats-row">
            <div class="dentsoft-stat-card">
                <div class="stat-icon pending">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <h3 id="stat-pending">0</h3>
                    <p>Bekleyen</p>
                </div>
            </div>
            <div class="dentsoft-stat-card">
                <div class="stat-icon confirmed">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="stat-content">
                    <h3 id="stat-confirmed">0</h3>
                    <p>Onaylanan</p>
                </div>
            </div>
            <div class="dentsoft-stat-card">
                <div class="stat-icon completed">
                    <span class="dashicons dashicons-saved"></span>
                </div>
                <div class="stat-content">
                    <h3 id="stat-completed">0</h3>
                    <p>Tamamlanan</p>
                </div>
            </div>
            <div class="dentsoft-stat-card">
                <div class="stat-icon cancelled">
                    <span class="dashicons dashicons-dismiss"></span>
                </div>
                <div class="stat-content">
                    <h3 id="stat-cancelled">0</h3>
                    <p>İptal Edilen</p>
                </div>
            </div>
        </div>
        
        <div class="dentsoft-table-wrapper">
            <table class="wp-list-table widefat fixed striped dentsoft-appointments-table">
                <thead>
                    <tr>
                        <th class="column-id">ID</th>
                        <th class="column-patient">Hasta</th>
                        <th class="column-doctor">Hekim</th>
                        <th class="column-clinic">Klinik</th>
                        <th class="column-datetime">Randevu Tarihi</th>
                        <th class="column-pnr">PNR No</th>
                        <th class="column-status">Durum</th>
                        <th class="column-actions">İşlemler</th>
                    </tr>
                </thead>
                <tbody id="dentsoft-appointments-list">
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="dentsoft-loading">
                                <span class="spinner is-active"></span>
                                <p>Randevular yükleniyor...</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="dentsoft-pagination">
            <div class="pagination-info">
                <span id="dentsoft-showing-info">0 randevu gösteriliyor</span>
            </div>
            <div class="pagination-controls">
                <button type="button" id="dentsoft-prev-page" class="button" disabled>
                    <span class="dashicons dashicons-arrow-left-alt2"></span> Önceki
                </button>
                <span id="dentsoft-page-info" class="page-info">Sayfa 1 / 1</span>
                <button type="button" id="dentsoft-next-page" class="button" disabled>
                    Sonraki <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const DentsoftAdmin = {
        currentPage: 1,
        totalPages: 1,
        perPage: 20,
        
        init() {
            this.bindEvents();
            this.loadAppointments();
            this.loadStats();
        },
        
        bindEvents() {
            $('#dentsoft-filter-btn').on('click', () => this.filterAppointments());
            $('#dentsoft-refresh-btn').on('click', () => this.loadAppointments());
            $('#dentsoft-search').on('keypress', (e) => {
                if (e.which === 13) this.filterAppointments();
            });
            $('#dentsoft-prev-page').on('click', () => this.prevPage());
            $('#dentsoft-next-page').on('click', () => this.nextPage());
            
            $(document).on('click', '.dentsoft-status-btn', (e) => this.updateStatus(e));
            $(document).on('click', '.dentsoft-delete-btn', (e) => this.deleteAppointment(e));
        },
        
        loadAppointments(page = 1) {
            const search = $('#dentsoft-search').val();
            const status = $('#dentsoft-status-filter').val();
            
            $.ajax({
                url: dentsoftAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dentsoft_get_appointments',
                    nonce: dentsoftAdmin.nonce,
                    page: page,
                    per_page: this.perPage,
                    search: search,
                    status: status
                },
                beforeSend: () => {
                    $('#dentsoft-appointments-list').html('<tr><td colspan="8" class="text-center"><span class="spinner is-active"></span></td></tr>');
                },
                success: (response) => {
                    if (response.success) {
                        this.renderAppointments(response.data);
                        this.updatePagination(response.data);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: () => {
                    this.showError('Randevular yüklenirken bir hata oluştu.');
                }
            });
        },
        
        renderAppointments(data) {
            const tbody = $('#dentsoft-appointments-list');
            
            if (!data.appointments || data.appointments.length === 0) {
                tbody.html('<tr><td colspan="8" class="text-center">Randevu bulunamadı.</td></tr>');
                return;
            }
            
            let html = '';
            data.appointments.forEach(appointment => {
                const date = new Date(appointment.appointment_date);
                const formattedDate = date.toLocaleDateString('tr-TR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
                const formattedTime = date.toLocaleTimeString('tr-TR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                html += `<tr>
                    <td>${appointment.id}</td>
                    <td>
                        <strong>${appointment.patient_name} ${appointment.patient_surname}</strong><br>
                        <small>${appointment.patient_phone}</small>
                    </td>
                    <td>${appointment.doctor_name}</td>
                    <td>
                        <strong>${appointment.clinic_name}</strong><br>
                        <small>${appointment.clinic_phone || ''}</small>
                    </td>
                    <td>
                        <strong>${formattedDate}</strong><br>
                        <small>${formattedTime}</small>
                    </td>
                    <td><code>${appointment.pnr_no}</code></td>
                    <td>${this.getStatusBadge(appointment.appointment_status)}</td>
                    <td>${this.getActionButtons(appointment)}</td>
                </tr>`;
            });
            
            tbody.html(html);
        },
        
        getStatusBadge(status) {
            const badges = {
                'pending': '<span class="dentsoft-badge badge-warning">Bekliyor</span>',
                'confirmed': '<span class="dentsoft-badge badge-success">Onaylandı</span>',
                'cancelled': '<span class="dentsoft-badge badge-danger">İptal</span>',
                'completed': '<span class="dentsoft-badge badge-info">Tamamlandı</span>'
            };
            return badges[status] || badges['pending'];
        },
        
        getActionButtons(appointment) {
            let buttons = '';
            
            switch (appointment.appointment_status) {
                case 'pending':
                    buttons = `
                        <button class="button button-small dentsoft-status-btn" data-id="${appointment.id}" data-status="confirmed" title="Onayla">
                            <span class="dashicons dashicons-yes"></span>
                        </button>
                        <button class="button button-small dentsoft-status-btn" data-id="${appointment.id}" data-status="cancelled" title="İptal Et">
                            <span class="dashicons dashicons-no"></span>
                        </button>`;
                    break;
                case 'confirmed':
                    buttons = `
                        <button class="button button-small dentsoft-status-btn" data-id="${appointment.id}" data-status="completed" title="Tamamla">
                            <span class="dashicons dashicons-saved"></span>
                        </button>
                        <button class="button button-small dentsoft-status-btn" data-id="${appointment.id}" data-status="cancelled" title="İptal Et">
                            <span class="dashicons dashicons-no"></span>
                        </button>`;
                    break;
                case 'cancelled':
                    buttons = `
                        <button class="button button-small dentsoft-status-btn" data-id="${appointment.id}" data-status="pending" title="Tekrar Aktif Et">
                            <span class="dashicons dashicons-update"></span>
                        </button>`;
                    break;
            }
            
            buttons += `
                <button class="button button-small dentsoft-delete-btn" data-id="${appointment.id}" title="Sil">
                    <span class="dashicons dashicons-trash"></span>
                </button>`;
            
            return `<div class="dentsoft-action-buttons">${buttons}</div>`;
        },
        
        updateStatus(e) {
            const btn = $(e.currentTarget);
            const id = btn.data('id');
            const status = btn.data('status');
            
            if (!confirm('Randevu durumunu güncellemek istediğinize emin misiniz?')) {
                return;
            }
            
            $.ajax({
                url: dentsoftAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dentsoft_update_appointment_status',
                    nonce: dentsoftAdmin.nonce,
                    appointment_id: id,
                    status: status
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        this.loadAppointments(this.currentPage);
                        this.loadStats();
                    } else {
                        this.showError(response.data.message);
                    }
                }
            });
        },
        
        deleteAppointment(e) {
            const btn = $(e.currentTarget);
            const id = btn.data('id');
            
            if (!confirm('Bu randevuyu silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) {
                return;
            }
            
            $.ajax({
                url: dentsoftAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dentsoft_delete_appointment',
                    nonce: dentsoftAdmin.nonce,
                    appointment_id: id
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(response.data.message);
                        this.loadAppointments(this.currentPage);
                        this.loadStats();
                    } else {
                        this.showError(response.data.message);
                    }
                }
            });
        },
        
        loadStats() {
            $.ajax({
                url: dentsoftAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dentsoft_get_appointments',
                    nonce: dentsoftAdmin.nonce,
                    per_page: 1000
                },
                success: (response) => {
                    if (response.success && response.data.appointments) {
                        const stats = {
                            pending: 0,
                            confirmed: 0,
                            completed: 0,
                            cancelled: 0
                        };
                        
                        response.data.appointments.forEach(apt => {
                            if (stats[apt.appointment_status] !== undefined) {
                                stats[apt.appointment_status]++;
                            }
                        });
                        
                        $('#stat-pending').text(stats.pending);
                        $('#stat-confirmed').text(stats.confirmed);
                        $('#stat-completed').text(stats.completed);
                        $('#stat-cancelled').text(stats.cancelled);
                    }
                }
            });
        },
        
        filterAppointments() {
            this.currentPage = 1;
            this.loadAppointments(1);
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadAppointments(this.currentPage);
            }
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadAppointments(this.currentPage);
            }
        },
        
        updatePagination(data) {
            this.currentPage = data.page;
            this.totalPages = data.total_pages;
            
            $('#dentsoft-page-info').text(`Sayfa ${data.page} / ${data.total_pages}`);
            $('#dentsoft-showing-info').text(`${data.total} randevu bulundu`);
            
            $('#dentsoft-prev-page').prop('disabled', data.page <= 1);
            $('#dentsoft-next-page').prop('disabled', data.page >= data.total_pages);
        },
        
        showSuccess(message) {
            this.showNotice(message, 'success');
        },
        
        showError(message) {
            this.showNotice(message, 'error');
        },
        
        showNotice(message, type) {
            const notice = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                </div>
            `);
            
            $('.dentsoft-admin-wrap h1').after(notice);
            
            setTimeout(() => {
                notice.fadeOut(() => notice.remove());
            }, 3000);
        }
    };
    
    DentsoftAdmin.init();
});
</script>