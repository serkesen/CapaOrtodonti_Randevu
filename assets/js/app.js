; (function ($) {
    'use strict';

    // Randevu sayfasi: acilista istemsiz scroll'u engelle
    if (document.body && document.body.classList.contains('page-id-8830')) {
        var __dsResetScroll = function () {
            if (window.scrollY > 0) { window.scrollTo(0, 0); }
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', __dsResetScroll);
        } else {
            __dsResetScroll();
        }
        window.addEventListener('load', __dsResetScroll);
    }

    const CaparvApp = {
        config: window.caparvConfig || {},
        currentStep: 1,
        selectedData: {
            clinic: null,
            doctor: null,
            date: null,
            time: null
        },
        currentDate: new Date(),
        dateRange: 8,

        init() {
            if (!this.validateConfig()) return;

            this.setupEventListeners();
            this.initializePlugins();
            this.loadClinics();
        },

        validateConfig() {
            if (!this.config.vkn) {
                this.showError('VKN yapılandırması eksik. Lütfen ayarlardan VKN bilgisini giriniz.');
                return false;
            }
            return true;
        },

        setupEventListeners() {
            $(document).on('click', '.caparv-btn-next', () => this.nextStep());
            $(document).on('click', '.caparv-btn-prev', () => this.prevStep());
            $(document).on('click', '.caparv-btn-prev-week', () => this.changeWeek(-1));
            $(document).on('click', '.caparv-btn-next-week', () => this.changeWeek(1));
            $(document).on('click', '.caparv-time-slot', (e) => this.selectTimeSlot(e));

            $('#caparv-submit-btn').on('click', () => this.submitAppointment());
            $('#caparv-new-appointment-btn').on('click', () => this.resetForm());
            $('#caparv-kvkk-link').on('click', (e) => this.showKVKK(e));
            $('.caparv-modal-close').on('click', () => this.closeModal());

            $('#caparv-query-appointment-btn, #caparv-header-query-btn').on('click', () => this.showQuerySection());
            $('#caparv-query-submit-btn').on('click', () => this.queryAppointment());
            $('#caparv-query-close-btn').on('click', () => this.hideQuerySection());
            $('#caparv-cancel-appointment-btn').on('click', () => this.confirmCancelAppointment());

            $('#caparv-patient-phone').on('input', function () {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 10) value = value.substring(0, 10);
                $(this).val(value);
            });

            $('#caparv-patient-number').on('input', function () {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 11) value = value.substring(0, 11);
                $(this).val(value);
            });
        },

        initializePlugins() {
            // Select2 init'i renderDoctors icinde (v4, avatar template'li) yapilir.
            // Klinik select duz birakildi; global .caparv-select2 init'i kaldirildi.
        },

        loadClinics() {
            this.showLoading();

            const ajaxSettings = {
                url: `${this.config.apiUrl}/Clinic/List/${this.config.vkn}`,
                method: 'GET',
                dataType: 'json',
                success: (response) => {
                    this.hideLoading();
                    if (response.Status && response.Status.Code === 100) {
                        this.renderClinics(response.Response.Clinic);
                    } else {
                        this.showError('Klinik listesi alınamadı.');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Bağlantı hatası oluştu.');
                }
            };

            if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                ajaxSettings.headers = {
                    'Authorization': `Bearer ${this.config.bearerToken}`
                };
            }

            $.ajax(ajaxSettings);
        },

        renderClinics(clinics) {
            const $select = $('#caparv-clinic-select');

            // Klinik adimi gizli; Select2 KULLANILMIYOR (eski select2 v3.4.1
            // gizli select'te 'query function not defined' hatasi verip
            // CaparvApp'i cokertiyordu). Sade <select> + change olayi.
            if ($select.data('select2')) {
                try { $select.select2('destroy'); } catch (e) {}
            }

            $select.off('change.caparv');
            $select.empty().append('<option value="">Klinik Seçiniz...</option>');

            if (clinics && clinics.length > 0) {
                clinics.forEach(clinic => {
                    const $option = $('<option>')
                        .val(clinic.ID)
                        .text(clinic.Name)
                        .data('clinic', clinic);
                    $select.append($option);
                });

                $select.on('change.caparv', () => {
                    this.onClinicChange();
                });

                if (clinics.length === 1) {
                    $select.val(clinics[0].ID).trigger('change.caparv');
                }
            }
        },

        onClinicChange() {
            const $select = $('#caparv-clinic-select');
            const clinicId = $select.val();

            this.selectedData.doctor = null;
            $('.caparv-btn-next[data-step="2"]').prop('disabled', true);

            if (clinicId) {
                const clinicData = $select.find('option:selected').data('clinic');
                if (clinicData) {
                    this.selectedData.clinic = clinicData;
                    $('.caparv-btn-next[data-step="1"]').prop('disabled', false);
                    this.updateSelectionSummary();
                    this.loadDoctors(clinicData.ID);
                    // Tek klinik: klinik adimi atlanir, dogrudan hekim adimina gec
                    if (this.currentStep === 1) { this.goToStep(2, false); }
                }
            } else {
                this.selectedData.clinic = null;
                $('.caparv-btn-next[data-step="1"]').prop('disabled', true);
                this.updateSelectionSummary();
            }
        },

        loadDoctors(clinicId) {
            const ajaxSettings = {
                url: `${this.config.apiUrl}/Clinic/DoctorList/${clinicId}`,
                method: 'GET',
                dataType: 'json',
                success: (response) => {
                    if (response.Response && response.Response.Users) {
                        this.renderDoctors(response.Response.Users);
                    } else {
                        this.showError('Hekim listesi alınamadı.');
                    }
                },
                error: () => {
                    this.showError('Hekim bilgileri yüklenirken hata oluştu.');
                }
            };

            if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                ajaxSettings.headers = {
                    'Authorization': `Bearer ${this.config.bearerToken}`
                };
            }

            $.ajax(ajaxSettings);
        },

        renderDoctors(doctors) {
            const $select = $('#caparv-doctor-select');

            if ($select.data('select2')) {
                $select.select2('destroy');
            }

            $select.empty().append('<option value="">Hekim Seçiniz...</option>');

            if (doctors && doctors.length > 0) {
                // Manuel hekim siralamasi (User.ID'ye gore). Listede olmayanlar sona, API sirasiyla.
                const doctorPhotos = {
                    'N0NyaEswbDR0dEQwL1h4Z2xKTzVydz09': 'https://capaortodonti.com/wp-content/uploads/semraerrkesen.png',
                    'b3dqbU9oM3VFUGg3SFZ1eG9IMjlLUT09': 'https://capaortodonti.com/wp-content/uploads/Ozgur-YILDIZ.png',
                    'L1lMdldJTjBmb3ZxOWNBNm5wNHlydz09': 'https://capaortodonti.com/wp-content/uploads/Adsiz-tasarim-2.png',
                    'eXFoNHN4eHVPeVBvSXRaNWpmRDkvZz09': 'https://capaortodonti.com/wp-content/uploads/Muhammet-Furkan-OZDEN.png',
                    'ZmFPYkFTemhjQWFXWnJmeTJwMm5lZz09': 'https://capaortodonti.com/wp-content/uploads/Adsiz-tasarim-3.png',
                };
                const doctorOrder = [
                    'N0NyaEswbDR0dEQwL1h4Z2xKTzVydz09', // Semra Can Erkesen
                    'b3dqbU9oM3VFUGg3SFZ1eG9IMjlLUT09', // Özgür Yıldız
                    'L1lMdldJTjBmb3ZxOWNBNm5wNHlydz09', // Kübra Bozacı
                    'eXFoNHN4eHVPeVBvSXRaNWpmRDkvZz09', // Muhammed Furkan Özden
                    'ZmFPYkFTemhjQWFXWnJmeTJwMm5lZz09', // Sahra Yıldırımer
                ];
                doctors = doctors.slice().sort((a, b) => {
                    const ia = doctorOrder.indexOf(a.User.ID);
                    const ib = doctorOrder.indexOf(b.User.ID);
                    if (ia === -1 && ib === -1) return 0;
                    if (ia === -1) return 1;
                    if (ib === -1) return -1;
                    return ia - ib;
                });

                doctors.forEach(doctor => {
                    const fullName = `${doctor.User.FirstName} ${doctor.User.LastName}`;
                    const nearestDay = doctor.NearestDay?.Date ?
                        this.formatNearestDay(doctor.NearestDay.Date, doctor.NearestDay.Time?.Begin) :
                        'Müsait tarih yok';

                    const $option = $('<option>')
                        .val(doctor.User.ID)
                        .text(fullName)
                        .data('doctor', doctor)
                        .data('avatar', doctorPhotos[doctor.User.ID] || doctor.User.Avatar || `${this.config.pluginUrl}assets/img/default-avatar.png`)
                        .data('role', doctor.User.Roles || 'Diş Hekimi')
                        .data('nearest', nearestDay);

                    $select.append($option);
                });

                // Genel Randevu (Muayene) - sahte hekim, en altta. DentSoft'a gitmez, mail tetikler.
                const $genel = $('<option>')
                    .val('GENEL_MUAYENE')
                    .text('Genel Randevu')
                    .data('role', 'Tüm Tedaviler')
                    .data('avatar', "data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHdpZHRoPSc5NicgaGVpZ2h0PSc5Nicgdmlld0JveD0nMCAwIDI0IDI0Jz48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9J2cnIHgxPScwJyB5MT0nMCcgeDI9JzEnIHkyPScxJz48c3RvcCBvZmZzZXQ9JzAnIHN0b3AtY29sb3I9JyMxNGE5OWEnLz48c3RvcCBvZmZzZXQ9Jy41NScgc3RvcC1jb2xvcj0nIzBlODU3YicvPjxzdG9wIG9mZnNldD0nMScgc3RvcC1jb2xvcj0nIzBiNjI1YycvPjwvbGluZWFyR3JhZGllbnQ+PC9kZWZzPjxyZWN0IHdpZHRoPScyNCcgaGVpZ2h0PScyNCcgZmlsbD0ndXJsKCNnKScvPjxjaXJjbGUgY3g9JzEyJyBjeT0nOC42JyByPSczLjQnIGZpbGw9JyNmZmYnLz48cGF0aCBkPSdNMTIgMTMuMmMtNC4yIDAtNi45IDIuOS02LjkgNi44aDEzLjhjMC0zLjktMi43LTYuOC02LjktNi44eicgZmlsbD0nI2ZmZicvPjwvc3ZnPg==")
                    .data('nearest', '');
                $select.append($genel);

                $select.select2({
                    placeholder: 'Hekim Seçiniz...',
                    minimumResultsForSearch: Infinity,
                    dropdownCssClass: 'caparv-doctor-dropdown',
                    templateResult: this.formatDoctorOption.bind(this),
                    templateSelection: this.formatDoctorSelection.bind(this),
                    escapeMarkup: function (m) { return m; }
                }).on('change', (e) => {
                    this.onDoctorChange();
                });
            } else {
                $('#caparv-doctor-error').text('Bu klinikde kayıtlı hekim bulunamadı.').show();
            }
        },

        formatDoctorOption(item) {
            if (!item.id) return item.text;

            const $option = $(item.element);
            const role = $option.data('role');
            const nearest = $option.data('nearest');
            const avatar = $option.data('avatar');

            return `
                <div class="caparv-doctor-item">
                    <img class="caparv-doctor-avatar" src="${avatar}" alt="" loading="lazy">
                    <div class="caparv-doctor-info">
                        <div class="caparv-doctor-name">${item.text}${role ? ' - ' + role : ''}</div>
                        <div class="caparv-doctor-nearest">${nearest}</div>
                    </div>
                </div>
            `;
        },

        formatDoctorSelection(item) {
            if (!item.id) return item.text;
            return item.text;
        },

        formatNearestDay(date, time) {
            const dateObj = new Date(date);
            const gunler = ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'];
            const aylar = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
            const dayShort = gunler[dateObj.getDay()];
            const dayNum = dateObj.getDate();
            const monthShort = aylar[dateObj.getMonth()];
            const timeStr = time ? ` - ${time}` : '';
            return `En erken: ${dayNum} ${monthShort} ${dayShort}${timeStr}`;
        },

        onDoctorChange() {
            const $select = $('#caparv-doctor-select');
            const doctorId = $select.val();

            if (doctorId === 'GENEL_MUAYENE') {
                // Genel Randevu (Muayene): sahte hekim nesnesi, DentSoft'a gitmez
                this.selectedData.doctor = { User: { ID: 'GENEL_MUAYENE', FirstName: 'Genel Randevu', LastName: '', Roles: 'Tüm Tedaviler' } };
                $('.caparv-btn-next[data-step="2"]').prop('disabled', false);
                this.updateSelectionSummary();
                return;
            }

            if (doctorId) {
                const doctorData = $select.find('option:selected').data('doctor');
                if (doctorData) {
                    this.selectedData.doctor = doctorData;
                    $('.caparv-btn-next[data-step="2"]').prop('disabled', false);
                    this.updateSelectionSummary();
                }
            } else {
                this.selectedData.doctor = null;
                $('.caparv-btn-next[data-step="2"]').prop('disabled', true);
                this.updateSelectionSummary();
            }
        },

        updateSelectionSummary() {
            const $summary = $('#caparv-selection-summary');
            const $clinicItem = $('#caparv-selected-clinic');
            const $doctorItem = $('#caparv-selected-doctor');

            if (this.selectedData.clinic) {
                $clinicItem.find('.summary-text').text(this.selectedData.clinic.Name);
                $clinicItem.fadeIn();
            } else {
                $clinicItem.hide();
            }

            if (this.selectedData.doctor) {
                const fullName = `${this.selectedData.doctor.User.FirstName} ${this.selectedData.doctor.User.LastName}`;
                const role = this.selectedData.doctor.User.Roles || 'Diş Hekimi';
                $doctorItem.find('.summary-text').text(`${fullName} - ${role}`);
                /* 4 Agu 2026: ozet cubugunda jenerik ikon yerine hekimin mini fotografi.
                   Kaynak, secili option'in data-avatar'i — boylece Genel Randevu da kendi
                   SVG avatarini kullanir, ayrik durum gerekmez. */
                const avatarSrc = $('#caparv-doctor-select').find('option:selected').data('avatar') || '';
                const $docAvatar = $doctorItem.find('.summary-avatar');
                if (avatarSrc) { $docAvatar.attr('src', avatarSrc).show(); } else { $docAvatar.hide(); }
                $doctorItem.fadeIn();
            } else {
                $doctorItem.hide();
            }

            if (this.selectedData.clinic || this.selectedData.doctor) {
                $summary.fadeIn();
            } else {
                $summary.hide();
            }
        },

        loadGenelSlots() {
            // Genel Randevu sahte takvimi: 14 gun, Cmt 09:30-18:00, diger 09:30-19:00, 30dk, hepsi Available.
            // Pazar ve 12:30 renderPages/buildDayColumn filtrelerinde elenir.
            $('#caparv-calendar-loading').hide();
            const pad = n => (n < 10 ? '0' + n : '' + n);
            const slots = {};
            const start = new Date(this.currentDate);
            for (let i = 0; i < 14; i++) {
                const d = new Date(start);
                d.setDate(d.getDate() + i);
                const dow = d.getDay();
                if (dow === 0) continue; // pazar kapali
                const endHour = (dow === 6) ? 18 : 19; // cmt 18:00, diger 19:00
                const key = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
                const arr = [];
                // Bugun icin: su an + 2 saat tamponundan onceki dilimler secilemez (disabled)
                const now = new Date();
                const isToday = (d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate());
                const minMin = isToday ? (now.getHours() * 60 + now.getMinutes() + 120) : -1;
                for (let m = 9 * 60 + 30; m + 30 <= endHour * 60; m += 30) {
                    const bH = Math.floor(m / 60), bM = m % 60;
                    const eH = Math.floor((m + 30) / 60), eM = (m + 30) % 60;
                    const passed = (m < minMin); // gecmis/tampon ici -> disabled
                    arr.push({ Type: passed ? 'NotAvailable' : 'Available', Time: { Begin: pad(bH) + ':' + pad(bM), End: pad(eH) + ':' + pad(eM) } });
                }
                slots[key] = arr;
            }
            this.renderCalendar(slots);
            $('#caparv-calendar-controls').hide();
            $('#caparv-calendar-container').show();
            $('#caparv-no-appointments').hide();
        },

        loadAppointmentSlots() {
            if (this.selectedData.doctor && this.selectedData.doctor.User.ID === 'GENEL_MUAYENE') {
                this.loadGenelSlots();
                return;
            }
            const clinicId = this.selectedData.clinic.ID;
            const doctorId = this.selectedData.doctor.User.ID;
            const dateStr = this.formatDate(this.currentDate);

            $('#caparv-calendar-loading').show();
            $('#caparv-calendar-container').hide();

            const ajaxSettings = {
                url: `${this.config.apiUrl}/Appointment/Doctor/${clinicId}/${doctorId}/${dateStr}/${this.dateRange}`,
                method: 'GET',
                dataType: 'json',
                success: (response) => {
                    $('#caparv-calendar-loading').hide();

                    if (response.Response && response.Response[0]) {
                        const slots = response.Response[0].Slot;
                        if (slots && Object.keys(slots).length > 0) {
                            this.renderCalendar(slots);
                            $('#caparv-calendar-controls').show();
                            $('#caparv-calendar-container').show();
                            $('#caparv-no-appointments').hide();
                        } else {
                            this.showNoAppointments();
                        }
                    } else {
                        this.showNoAppointments();
                    }
                },
                error: () => {
                    $('#caparv-calendar-loading').hide();
                    this.showError('Randevu saatleri yüklenirken hata oluştu.');
                }
            };

            if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                ajaxSettings.headers = {
                    'Authorization': `Bearer ${this.config.bearerToken}`
                };
            }

            $.ajax(ajaxSettings);
        },

        renderCalendar(slots) {
            const $container = $('#caparv-calendar-container');
            $container.empty();

            this.currentSlots = slots;
            this.loadingMore = false;
            this.noMoreSlots = false;

            const $track = $('<div>').addClass('caparv-cal-track');
            this.renderPages($track, Object.keys(slots).sort());

            const $dots = $('<div>').addClass('caparv-cal-dots');
            $container.append($track).append($dots);

            $track.on('scroll', () => {
                this.updateActiveDot();
                const el = $track[0];
                if (el.scrollLeft + el.clientWidth >= el.scrollWidth - 30) {
                    this.loadMoreSlots();
                }
            });

            this.buildDots();
        },

        renderPages($track, dates) {
            dates = dates.filter(dd => new Date(dd).getDay() !== 0); // pazar - klinik kapali, hic gosterme
            for (let i = 0; i < dates.length; i += 4) {
                const $page = $('<div>').addClass('caparv-cal-page');
                dates.slice(i, i + 4).forEach(date => $page.append(this.buildDayColumn(date)));
                $track.append($page);
            }
        },

        buildDots() {
            const $track = $('.caparv-cal-track');
            const $dots = $('.caparv-cal-dots');
            if (!$track.length || !$dots.length) return;
            $dots.empty();
            const n = $track.find('.caparv-cal-page').length;
            for (let i = 0; i < n; i++) {
                $dots.append($('<span>').addClass('caparv-cal-dot'));
            }
            this.updateActiveDot();
        },

        updateActiveDot() {
            const t = $('.caparv-cal-track')[0];
            if (!t) return;
            const active = Math.round(t.scrollLeft / t.clientWidth);
            $('.caparv-cal-dot').removeClass('active').eq(active).addClass('active');
        },

        buildDayColumn(date) {
            const gunler = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
            const aylar = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
            const d = new Date(date);
            const dayName = gunler[d.getDay()];
            const dayNum = d.getDate();
            const monthName = aylar[d.getMonth()];

            const $col = $('<div>').addClass('caparv-day-col');
            $col.append(`<div class="calendar-date-header"><span class="day-num">${dayNum} ${monthName}</span><span class="day-name">${dayName}</span></div>`);

            const $list = $('<div>').addClass('caparv-time-list');
            (this.currentSlots[date] || []).forEach(slot => {
                    if (slot.Time && slot.Time.Begin === '12:30') return; // 12:30 yemek molasi - hic gosterme
                const isAvailable = slot.Type === 'Available';
                const $btn = $('<button>')
                    .addClass('caparv-time-slot')
                    .attr('type', 'button')
                    .data('date', date)
                    .data('time', slot.Time.Begin)
                    .text(slot.Time.Begin);
                if (!isAvailable) {
                    $btn.addClass('disabled').prop('disabled', true);
                }
                $list.append($btn);
            });
            $col.append($list);
            return $col;
        },

        loadMoreSlots() {
            if (this.selectedData.doctor && this.selectedData.doctor.User.ID === 'GENEL_MUAYENE') return; // Genel'de sonsuz yukleme yok (14 gun sabit)
            if (this.loadingMore || this.noMoreSlots || !this.currentSlots) return;

            const dates = Object.keys(this.currentSlots).sort();
            if (dates.length === 0 || dates.length >= 60) {
                this.noMoreSlots = true;
                return;
            }

            const nextDate = new Date(dates[dates.length - 1]);
            nextDate.setDate(nextDate.getDate() + 1);
            const startStr = this.formatDate(nextDate);

            this.loadingMore = true;

            const clinicId = this.selectedData.clinic.ID;
            const doctorId = this.selectedData.doctor.User.ID;

            const ajaxSettings = {
                url: `${this.config.apiUrl}/Appointment/Doctor/${clinicId}/${doctorId}/${startStr}/${this.dateRange}`,
                method: 'GET',
                dataType: 'json',
                success: (response) => {
                    this.loadingMore = false;
                    if (response.Response && response.Response[0] && response.Response[0].Slot) {
                        const newSlots = response.Response[0].Slot;
                        const newDates = Object.keys(newSlots).filter(dd => !this.currentSlots[dd]).sort();
                        if (newDates.length === 0) {
                            this.noMoreSlots = true;
                            return;
                        }
                        newDates.forEach(dd => { this.currentSlots[dd] = newSlots[dd]; });
                        this.renderPages($('.caparv-cal-track'), newDates);
                        this.buildDots();
                    } else {
                        this.noMoreSlots = true;
                    }
                },
                error: () => {
                    this.loadingMore = false;
                }
            };

            if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                ajaxSettings.headers = {
                    'Authorization': `Bearer ${this.config.bearerToken}`
                };
            }

            $.ajax(ajaxSettings);
        },

        selectTimeSlot(e) {
            const $btn = $(e.currentTarget);

            if ($btn.hasClass('disabled')) {
                return;
            }

            // Toggle: zaten secili kutuya tekrar tiklayinca secimi kaldir
            if ($btn.hasClass('selected')) {
                $btn.removeClass('selected');
                this.selectedData.date = null;
                this.selectedData.time = null;
                $('.caparv-btn-next').prop('disabled', true);
                return;
            }

            const date = $btn.data('date');
            const time = $btn.data('time');

            $('.caparv-time-slot').removeClass('selected');
            $btn.addClass('selected');

            this.selectedData.date = date;
            this.selectedData.time = time;

            $('.caparv-btn-next').prop('disabled', false);
        },

        changeWeek(direction) {
            this.currentDate.setDate(this.currentDate.getDate() + (direction * this.dateRange));
            this.loadAppointmentSlots();
        },

        showNoAppointments() {
            $('#caparv-calendar-controls').hide();
            $('#caparv-calendar-container').hide();
            $('#caparv-no-appointments').show();

            if (this.selectedData.clinic && this.selectedData.clinic.ConcatInfo) {
                const contact = this.selectedData.clinic.ConcatInfo;
                let html = '<div class="caparv-contact-info">';

                if (contact.ContactPhone) {
                    html += `<p><strong>Telefon:</strong> <a href="tel:${contact.ContactPhone}">${contact.ContactPhone}</a></p>`;
                }
                if (contact.ContactEmail) {
                    html += `<p><strong>E-posta:</strong> <a href="mailto:${contact.ContactEmail}">${contact.ContactEmail}</a></p>`;
                }
                if (contact.ContactAddress) {
                    html += `<p><strong>Adres:</strong> ${contact.ContactAddress}</p>`;
                }

                html += '</div>';
                $('#caparv-clinic-contact-info').html(html);
            }
        },

        submitGenelRandevu() {
            // Genel Randevu (Muayene): DentSoft API'ye GITMEZ, sadece WP'ye gidip mail tetikler. DB'ye yazilmaz.
            this.showLoading();
            const data = {
                action: 'caparv_genel_randevu',
                nonce: this.config.nonce,
                patient_number: $('#caparv-patient-number').val(),
                patient_name: $('#caparv-patient-name').val(),
                patient_surname: $('#caparv-patient-surname').val(),
                patient_phone: $('#caparv-patient-phone').val(),
                patient_birthday: $('#caparv-patient-birthday').val(),
                patient_email: $('#caparv-patient-email').val(),
                clinic_name: (this.selectedData.clinic && this.selectedData.clinic.Name) || '',
                appointment_date: (this.selectedData.date || '') + ' ' + (this.selectedData.time || ''),
                appointment_time: (this.selectedData.time || '')
            };
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: data,
                success: (response) => {
                    this.hideLoading();
                    if (response.success) {
                        this.showGenelSuccess((response.data && response.data.islem_kodu) || '');
                    } else {
                        this.showError((response.data && response.data.message) || 'Talep gonderilemedi.');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Talep gonderilirken hata olustu.');
                }
            });
        },

        /* capa-randevu-sayac: anonim sayac pingi.
           GONDERILEN: randevu tipi, hekim adi (klinik personeli), randevu gununun
           kac gun sonrasi oldugu. GONDERILMEYEN: ad, soyad, telefon, e-posta,
           dogum tarihi, PNR, hasta numarasi — hicbiri. Sunucuda randevu basina
           satir olusmaz, yalnizca (gun,tip,hekim) sayaci artar. */
        capaSayac(tip, hekim, randevuGunu) {
            try {
                const cfg = this.config || {};
                if (!cfg.sayUrl) return;
                let fark = null;
                try {
                    const ham = String(randevuGunu || '').trim();
                    let iso = null;
                    if (/^\d{4}-\d{2}-\d{2}/.test(ham)) {
                        iso = ham.slice(0, 10);
                    } else if (/^\d{2}[.\/]\d{2}[.\/]\d{4}/.test(ham)) {
                        const p = ham.slice(0, 10).split(/[.\/]/);
                        iso = p[2] + '-' + p[1] + '-' + p[0];
                    }
                    if (iso) {
                        const d = new Date(iso + 'T00:00:00');
                        const b = new Date(); b.setHours(0, 0, 0, 0);
                        if (!isNaN(d.getTime())) {
                            const g = Math.round((d - b) / 86400000);
                            if (g >= 0 && g <= 730) fark = g;
                        }
                    }
                } catch (e) { fark = null; }
                fetch(cfg.sayUrl, {
                    method: 'POST',
                    keepalive: true,
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.sayNonce || '' },
                    // TEST MODU: olcum hatti ATESLENIR ama 'test' etiketiyle gider.
                    // Boylece hattin calistigini canliya almadan dogrularız, dashboard
                    // verisi de kirlenmez (site-customizations.php > sorgu whitelist).
                    body: JSON.stringify({
                        tip: ((cfg.testModu === true || cfg.testModu === 1 || cfg.testModu === '1') ? 'test' : tip),
                        hekim: hekim || '',
                        gun_farki: fark
                    })
                }).catch(function () {});
            } catch (e) {}
        },

        islemKoduGoster(kod) {
            const $s = $('#caparv-islem-kodu');
            if (!$s.length) return;
            if (kod) {
                $('#caparv-islem-kodu-deger').text(kod);
                $s.show();
            } else {
                $s.hide();
            }
        },

        showGenelSuccess(islemKodu) {
            try {
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    event: 'randevu_tamamlandi',
                    test_modu: (this.config && (this.config.testModu === true || this.config.testModu === 1 || this.config.testModu === '1')) ? 1 : 0,
                    randevu_tipi: 'genel',
                    hekim: 'Genel Randevu'
                });
            } catch (e) {}
            this.capaSayac('genel', 'Genel Randevu', (this.selectedData && this.selectedData.date) || '');
            // Step 5 (basari ekrani) Genel Randevu'ya gore: PNR/Print yok, talep mesaji
            $('#caparv-summary-patient').text($('#caparv-patient-name').val() + ' ' + $('#caparv-patient-surname').val());
            $('#caparv-summary-clinic').text((this.selectedData.clinic && this.selectedData.clinic.Name) || '');
            $('#caparv-summary-doctor').text('Genel Randevu - Tüm Tedaviler');
            $('#caparv-summary-datetime').text((this.selectedData.date || '') + ' ' + (this.selectedData.time || ''));
            $('#caparv-summary-pnr').text('Talebiniz alındı');
            this.islemKoduGoster(islemKodu);
            this.goToStep(5);
        },

        submitAppointment() {
            if (!this.validateForm()) return;
            if (this.selectedData.doctor && this.selectedData.doctor.User.ID === 'GENEL_MUAYENE') {
                this.submitGenelRandevu();
                return;
            }
            // KVKK onayi checkbox ile alindi (validateForm kontrol etti).
            // SMS onay kodu akisi kaldirildi; dogrudan randevu olusturuluyor.
            this.createAppointment();
        },

        sendApprovalCode(clinicId, contactRegion, contactMobile) {
            this.showLoading();

            const ajaxSettings = {
                url: `${this.config.apiUrl}/ApprovalDataShare`,
                method: 'POST',
                data: {
                    ClinicID: clinicId,
                    ContactRegion: contactRegion,
                    ContactMobile: contactMobile,
                    Type: 'Send'
                },
                success: (response) => {
                    this.hideLoading();

                    if (response.Response && response.Response.Html) {
                        $('#caparv-kvkk-content').html(response.Response.Html);
                        this.showApprovalCodeInput();
                    } else {
                        this.showError('KVKK onay kodu gönderilemedi.');
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('KVKK onay kodu gönderilirken hata oluştu.');
                }
            };

            if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                ajaxSettings.headers = {
                    'Authorization': `Bearer ${this.config.bearerToken}`
                };
            }

            $.ajax(ajaxSettings);
        },

        showApprovalCodeInput() {
            Swal.fire({
                title: '6 Haneli KVKK Onay Kodunu Girin',
                html: `
                    <input type="text" id="swal-approval-code" class="swal2-input" 
                           placeholder="Onay Kodu" maxlength="6" pattern="[0-9]{6}"
                           style="width: 80%; margin: 10px auto;">
                    <button type="button" class="swal2-confirm swal2-styled" 
                            style="margin-top: 10px; background-color: #6c757d;"
                            onclick="$('#caparv-kvkk-modal').fadeIn();">
                        KVKK Metnini Görüntüle
                    </button>
                `,
                showCancelButton: true,
                confirmButtonText: 'Onayla',
                cancelButtonText: 'İptal',
                preConfirm: () => {
                    const code = $('#swal-approval-code').val();

                    if (!code || !/^\d{6}$/.test(code)) {
                        Swal.showValidationMessage('Onay kodu 6 haneli olmalıdır ve sadece rakamlardan oluşmalıdır.');
                        return false;
                    }

                    return this.checkApprovalCode(code);
                },
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    this.createAppointment();
                }
            });
        },

        checkApprovalCode(code) {
            return new Promise((resolve) => {
                this.showLoading();

                const ajaxSettings = {
                    url: `${this.config.apiUrl}/ApprovalDataShare`,
                    method: 'POST',
                    data: {
                        Code: code,
                        Type: 'Check'
                    },
                    success: (response) => {
                        this.hideLoading();

                        if (response.Response && response.Response.Check) {
                            resolve(true);
                        } else {
                            Swal.showValidationMessage('Hatalı KVKK Onay Kodu!');
                            resolve(false);
                        }
                    },
                    error: () => {
                        this.hideLoading();
                        Swal.showValidationMessage('Onay kodu kontrol edilirken hata oluştu.');
                        resolve(false);
                    }
                };

                if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                    ajaxSettings.headers = {
                        'Authorization': `Bearer ${this.config.bearerToken}`
                    };
                }

                $.ajax(ajaxSettings);
            });
        },

        createAppointment() {
            const formData = this.getFormData();

            this.showLoading();

            const ajaxSettings = {
                url: `${this.config.apiUrl}/Appointment/New/${this.selectedData.clinic.ID}/${this.selectedData.doctor.User.ID}`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.hideLoading();

                    if (response.Status && response.Status.Code === 100) {
                        this.saveToDatabase(response.Response);
                    } else {
                        // Once API yanitindan ham bilgileri topla
                        const deepMsg = (obj) => {
                            if (obj == null) return '';
                            if (typeof obj === 'string') return obj;
                            if (typeof obj === 'object') {
                                for (const k in obj) {
                                    const m = deepMsg(obj[k]);
                                    if (m) return m;
                                }
                            }
                            return '';
                        };
                        const code = (response.Status && response.Status.Code) ? response.Status.Code : null;
                        const apiMsg = (response.Status && response.Status.Message) ? response.Status.Message : '';
                        const errMsg = deepMsg(response.Error);
                        const raw = [apiMsg, errMsg].filter(Boolean).join(': ');

                        // Bilinen Dentsoft hatalari icin Turkce karsiliklar (kod veya mesaj metnine gore)
                        const trByCode = {
                            106: 'Bu bilgilerle zaten bir randevunuz bulunuyor. Aynı anda birden fazla randevu oluşturulamaz.'
                        };
                        const trByText = [
                            { match: /multiple appointment|more than one appointment/i, tr: 'Bu bilgilerle zaten bir randevunuz bulunuyor. Aynı anda birden fazla randevu oluşturulamaz.' },
                            { match: /not available|already booked|slot/i, tr: 'Seçtiğiniz saat artık uygun değil. Lütfen başka bir saat seçin.' },
                            { match: /invalid|required|missing|format/i, tr: 'Girdiğiniz bilgilerde bir hata var. Lütfen kontrol edip tekrar deneyin.' }
                        ];

                        let msg = trByCode[code];
                        if (!msg) {
                            const hit = trByText.find(r => r.match.test(raw));
                            if (hit) msg = hit.tr;
                        }
                        if (!msg) msg = raw ? ('İşlem başarısız: ' + raw) : 'Randevu oluşturulurken bir hata oluştu. Lütfen tekrar deneyin.';
                        this.showError(msg);
                    }
                },
                error: () => {
                    this.hideLoading();
                    this.showError('Bağlantı hatası oluştu.');
                }
            };

            if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                ajaxSettings.headers = {
                    'Authorization': `Bearer ${this.config.bearerToken}`
                };
            }

            $.ajax(ajaxSettings);
        },

        saveToDatabase(appointmentData) {
            let appointmentLink = '';
            try {
                const clinicId = appointmentData.Clinic.ID;
                const apptId = appointmentData.Appointment.ID;
                if (clinicId && apptId) {
                    appointmentLink = 'https://clinic.dentsoft.com.tr/Print/' + clinicId + '/AR/' + apptId;
                }
            } catch (e) {
                appointmentLink = '';
            }

            let staffLink = '';
            try {
                const patientId = appointmentData.Patient && appointmentData.Patient.ID;
                if (patientId) {
                    staffLink = 'https://clinic.dentsoft.com.tr/Patient/Appointment/' + patientId;
                }
            } catch (e) {
                staffLink = '';
            }

            const data = {
                action: 'caparv_save_appointment',
                nonce: this.config.nonce,
                appointment_link: appointmentLink,
                appointment_staff_link: staffLink,
                patient_number: $('#caparv-patient-number').val(),
                patient_name: $('#caparv-patient-name').val(),
                patient_surname: $('#caparv-patient-surname').val(),
                patient_phone: $('#caparv-patient-phone').val(),
                patient_birthday: $('#caparv-patient-birthday').val(),
                patient_email: $('#caparv-patient-email').val(),
                clinic_name: appointmentData.Clinic.Name,
                clinic_address: appointmentData.Clinic.ContactInfo.ContactAddress || '',
                clinic_phone: appointmentData.Clinic.ContactInfo.ContactPhone || '',
                doctor_name: appointmentData.User.Name,
                pnr_no: appointmentData.Appointment.PNR,
                appointment_date: `${appointmentData.Appointment.Date} ${appointmentData.Appointment.Time.Begin}`,
                appointment_status: 'pending'
            };

            /* ⚠ 28 Tem 2026 DERSI — basari akisini yan isleme BAGLAMA.
               Bu noktaya gelindiyse randevu klinik sisteminde ZATEN olusmustur.
               Buradaki istek yalnizca yerel islem kaydi + bildirim maili icindir.
               Basarisiz olsa bile hasta basari ekranini gormeli, olcum olaylari
               atmalidir; aksi halde randevu olusur ama kimse bilmez. */
            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: data,
                success: (response) => {
                    const kod = (response && response.data && response.data.islem_kodu) || '';
                    this.showSuccess(appointmentData, kod);
                },
                error: () => {
                    this.showSuccess(appointmentData, '');
                }
            });
        },

        showSuccess(appointmentData, islemKodu) {
            try {
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({
                    event: 'randevu_tamamlandi',
                    test_modu: (this.config && (this.config.testModu === true || this.config.testModu === 1 || this.config.testModu === '1')) ? 1 : 0,
                    randevu_tipi: 'dentsoft',
                    hekim: (appointmentData.User && appointmentData.User.Name) || ''
                });
            } catch (e) {}
            this.capaSayac('dentsoft',
                (appointmentData.User && appointmentData.User.Name) || '',
                (appointmentData.Appointment && appointmentData.Appointment.Date) || '');
            $('#caparv-summary-patient').text(`${$('#caparv-patient-name').val()} ${$('#caparv-patient-surname').val()}`);
            $('#caparv-summary-clinic').text(appointmentData.Clinic.Name);
            $('#caparv-summary-doctor').text(appointmentData.User.Name);
            $('#caparv-summary-datetime').text(
                `${this.formatDisplayDate(appointmentData.Appointment.Date)} ${appointmentData.Appointment.Time.Begin}`
            );
            $('#caparv-summary-pnr').text(appointmentData.Appointment.PNR);
            this.islemKoduGoster(islemKodu);

            this.goToStep(5);

            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: this.config.strings.success || 'Randevunuz başarıyla oluşturuldu!',
                confirmButtonText: 'Tamam'
            });
        },

        validateForm() {
            const numberValue = ($('#caparv-patient-number').val() || '').trim();
            const nameValue = ($('#caparv-patient-name').val() || '').trim();
            const surnameValue = ($('#caparv-patient-surname').val() || '').trim();
            const phoneValue = ($('#caparv-patient-phone').val() || '').trim();

            if (!numberValue) {
                this.showError('TC Kimlik No alanı zorunludur.');
                $('#caparv-patient-number').focus();
                return false;
            }
            if (!this.isValidTCKN(numberValue)) {
                this.showError('Geçerli bir TC Kimlik No giriniz (11 haneli).');
                $('#caparv-patient-number').focus();
                return false;
            }

            if (!nameValue) {
                this.showError('Ad alanı zorunludur.');
                $('#caparv-patient-name').focus();
                return false;
            }
            if (!surnameValue) {
                this.showError('Soyad alanı zorunludur.');
                $('#caparv-patient-surname').focus();
                return false;
            }

            if (!phoneValue) {
                this.showError('Telefon alanı zorunludur.');
                $('#caparv-patient-phone').focus();
                return false;
            }
            const phoneDigits = phoneValue.replace(/[^0-9]/g, '');
            const normalizedPhone = (phoneDigits.length === 11 && phoneDigits.charAt(0) === '0') ? phoneDigits.substring(1) : phoneDigits;
            if (!/^5[0-9]{9}$/.test(normalizedPhone)) {
                this.showError('Geçerli bir telefon numarası giriniz (5XX XXX XX XX).');
                $('#caparv-patient-phone').focus();
                return false;
            }

            const emailValue = ($('#caparv-patient-email').val() || '').trim();
            if (emailValue && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
                this.showError('Geçerli bir e-posta adresi giriniz.');
                $('#caparv-patient-email').focus();
                return false;
            }

            const birthdayValue = $('#caparv-patient-birthday').val();
            if (birthdayValue) {
                const birthDate = new Date(birthdayValue);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (isNaN(birthDate.getTime()) || birthDate > today || birthDate.getFullYear() < 1900) {
                    this.showError('Geçerli bir doğum tarihi giriniz.');
                    $('#caparv-patient-birthday').focus();
                    return false;
                }
            }

            if (!$('#caparv-kvkk-checkbox').is(':checked')) {
                this.showError('KVKK onayı zorunludur.');
                return false;
            }

            return true;
        },

        isValidTCKN(value) {
            if (!/^[1-9][0-9]{10}$/.test(value)) return false;
            const d = value.split('').map(Number);
            const oddSum = d[0] + d[2] + d[4] + d[6] + d[8];
            const evenSum = d[1] + d[3] + d[5] + d[7];
            const digit10 = (((oddSum * 7) - evenSum) % 10 + 10) % 10;
            if (digit10 !== d[9]) return false;
            const sumFirst10 = d.slice(0, 10).reduce((a, b) => a + b, 0);
            if (sumFirst10 % 10 !== d[10]) return false;
            return true;
        },

        getFormData() {
            const formData = new FormData();
            formData.append('PatientNumber', $('#caparv-patient-number').val());
            formData.append('PatientFirstName', $('#caparv-patient-name').val());
            formData.append('PatientLastName', $('#caparv-patient-surname').val());
            formData.append('ContactMobile', $('#caparv-patient-phone').val());
            formData.append('ContactRegion', '90');
            formData.append('Date', this.selectedData.date);
            formData.append('BeginTime', this.selectedData.time);

            if ($('#caparv-patient-birthday').val()) {
                formData.append('PatientBirthday', $('#caparv-patient-birthday').val());
            }

            if ($('#caparv-patient-email').val()) {
                formData.append('ContactEmail', $('#caparv-patient-email').val());
            }

            return formData;
        },

        nextStep() {
            if (this.currentStep === 2 && this.selectedData.doctor) {
                this.loadAppointmentSlots();
            }

            // Genel Randevu: saat secimi -> hasta bilgileri gecisinde uyari modali
            if (this.currentStep === 3 && this.selectedData.doctor && this.selectedData.doctor.User.ID === 'GENEL_MUAYENE') {
                Swal.fire({
                    icon: 'info',
                    title: 'Bilgilendirme',
                    text: 'Seçtiğiniz gün ve saat, hekimlerimizin uygunluk durumuna göre değişiklik gösterebilir, sizi arayacağız.',
                    confirmButtonText: 'Tamam'
                }).then(() => { this.goToStep(4); });
                return;
            }

            if (this.currentStep < 5) {
                this.goToStep(this.currentStep + 1);
            }
        },

        prevStep() {
            if (this.currentStep > 2) {
                this.goToStep(this.currentStep - 1);
            }
        },

        goToStep(step, scroll = true) {
            $('.caparv-step').removeClass('active completed');
            $('.caparv-step-content').removeClass('active');

            for (let i = 1; i < step; i++) {
                $(`.caparv-step[data-step="${i}"]`).addClass('completed');
            }

            $(`.caparv-step[data-step="${step}"]`).addClass('active');
            $(`.caparv-step-content[data-step="${step}"]`).addClass('active');

            try { if (step > this.currentStep && step < 5) { window.dataLayer = window.dataLayer || []; window.dataLayer.push({ event: 'randevu_adim', adim: step, test_modu: (this.config && (this.config.testModu === true || this.config.testModu === 1 || this.config.testModu === '1')) ? 1 : 0 }); } } catch (e) {}

            this.currentStep = step;

            if (scroll) {
                $('html, body').animate({
                    scrollTop: $('.caparv-appointment-wrapper').offset().top - 50
                }, 500);
            }
        },

        resetForm() {
            this.currentStep = 1;
            this.selectedData = {
                clinic: null,
                doctor: null,
                date: null,
                time: null
            };

            $('#caparv-patient-form')[0].reset();
            $('#caparv-clinic-select').val('').trigger('change');
            $('#caparv-doctor-select').val('').trigger('change');

            this.updateSelectionSummary();
            this.hideQuerySection();
            this.goToStep(1);
        },

        showQuerySection() {
            $('#caparv-query-section').show().addClass('active');
            $('.caparv-step-content').removeClass('active');
            $('.caparv-step').removeClass('active');
            $('#caparv-query-result').hide();
            $('#caparv-query-error').hide();
            $('#caparv-query-pnr').val('');
            $('#caparv-query-patient-number').val('');

            $('html, body').animate({
                scrollTop: $('#caparv-query-section').offset().top - 50
            }, 500);
        },

        hideQuerySection() {
            $('#caparv-query-section').hide().removeClass('active');
            this.goToStep(this.currentStep >= 2 ? this.currentStep : 2);
        },

        queryAppointment() {
            const pnr = $('#caparv-query-pnr').val().trim();
            const patientNumber = $('#caparv-query-patient-number').val().trim();

            if (!pnr || !patientNumber) {
                $('#caparv-query-error').text('Lütfen tüm alanları doldurunuz.').show();
                return;
            }

            if (!/^\d{4}$/.test(patientNumber)) {
                $('#caparv-query-error').text('TC Kimlik No son 4 hane rakam olmalıdır.').show();
                return;
            }

            $('#caparv-query-error').hide();
            this.showLoading();

            const ajaxSettings = {
                url: `${this.config.apiUrl}/Appointment/Info`,
                method: 'POST',
                data: {
                    PatientNumber: patientNumber,
                    PNR: pnr
                },
                success: (response) => {
                    this.hideLoading();

                    if (response.Response && !response.Error.length) {
                        this.showQueryResult(response.Response, pnr, patientNumber);
                    } else {
                        let errorMsg = 'Randevu bulunamadı.';

                        if (response.Error && response.Error.length > 0) {
                            response.Error.forEach(err => {
                                if (err.includes('Appointment')) {
                                    errorMsg = 'Girilen bilgilere ait randevu bulunamadı.';
                                }
                            });
                        }

                        $('#caparv-query-error').text(errorMsg).show();
                        $('#caparv-query-result').hide();
                    }
                },
                error: () => {
                    this.hideLoading();
                    $('#caparv-query-error').text('Randevu sorgulanırken hata oluştu.').show();
                }
            };

            if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                ajaxSettings.headers = {
                    'Authorization': `Bearer ${this.config.bearerToken}`
                };
            }

            $.ajax(ajaxSettings);
        },

        hastaEpostasi(data) {
            const p = (data && data.Patient) || {};
            const c = p.ContactInfo || {};
            const aday = p.Email || p.EMail || p.Mail || p.ContactEmail
                || c.ContactEmail || c.Email || '';
            const v = String(aday || '').trim();
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? v : '';
        },

        showQueryResult(data, pnr, patientNumber) {
            const maskName = (name) => {
                return name.replace(/^(\w)\w*\s+(\w)\w*$/, '$1*** $2***');
            };

            const formatDate = (date) => {
                const d = new Date(date);
                return d.toLocaleDateString('tr-TR', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                    weekday: 'long'
                });
            };

            $('#caparv-query-patient-name').text(maskName(data.Patient.Name));
            $('#caparv-query-clinic').text(data.Clinic.Name);
            $('#caparv-query-doctor').text(`${data.User.Name} - ${data.User.Title || ''}`);
            $('#caparv-query-datetime').text(`${formatDate(data.Appointment.Date)} ${data.Appointment.Time.Begin} - ${data.Appointment.Time.End}`);
            $('#caparv-query-pnr-display').text(data.Appointment.PNR);

            $('#caparv-query-result').fadeIn();
            $('#caparv-cancel-appointment-btn')
                .data('pnr', pnr)
                .data('patient-number', patientNumber)
                .data('clinic', (data.Clinic && data.Clinic.Name) || '')
                .data('doctor', (data.User && data.User.Name) || '')
                .data('datetime', ((data.Appointment && data.Appointment.Date) || '') + ' ' + ((data.Appointment && data.Appointment.Time && data.Appointment.Time.Begin) || ''))
                .data('saat', (data.Appointment && data.Appointment.Time && data.Appointment.Time.Begin) || '')
                // ⚠ Hasta e-postasi YALNIZ iptal bildirimi gondermek icin tasinir.
                // Hicbir yere KAYDEDILMEZ (bkz. includes/class-db.php beyaz listesi).
                // API alan adi surume gore degisebildigi icin birkac aday denenir.
                .data('email', this.hastaEpostasi(data))
                .data('ad', (data.Patient && data.Patient.Name) || '');

            $('html, body').animate({
                scrollTop: $('#caparv-query-result').offset().top - 50
            }, 500);
        },

        confirmCancelAppointment() {
            const $btn = $('#caparv-cancel-appointment-btn');
            const pnr = $btn.data('pnr');
            const patientNumber = $btn.data('patient-number');
            const bilgi = {
                clinic: $btn.data('clinic') || '',
                doctor: $btn.data('doctor') || '',
                datetime: $btn.data('datetime') || '',
                saat: $btn.data('saat') || '',
                email: $btn.data('email') || '',
                ad: $btn.data('ad') || ''
            };

            /* Iptal onayi + e-posta sorusu.
               Randevu sorgulama ucu hasta e-postasini DONDURMUYOR (28 Tem 2026'da
               canlida dogrulandi: Patient => ID, UniqueID, Name, Registration).
               Bu yuzden iptal bildirimini hastaya gonderebilmek icin adresi
               burada soruyoruz. Girilen adres HICBIR YERE KAYDEDILMEZ; yalnizca
               tek seferlik bildirim icin kullanilir. Bos birakilabilir. */
            Swal.fire({
                title: 'Randevunuzu iptal edin',
                icon: 'warning',
                input: 'email',
                inputLabel: 'İptal onayını e-posta ile almak isterseniz adresinizi yazın (isteğe bağlı)',
                inputPlaceholder: 'ornek@eposta.com',
                inputValue: (bilgi && bilgi.email) || '',
                inputAttributes: { autocomplete: 'email' },
                inputValidator: (v) => {
                    if (!v) { return null; }
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? null : 'Geçerli bir e-posta adresi yazın.';
                },
                html: '<p style="margin:0 0 10px;">Randevunuzu iptal etmek istediğinize emin misiniz?</p>',
                showCancelButton: true,
                confirmButtonColor: 'var(--caparv-primary)',
                cancelButtonColor: 'var(--caparv-danger)',
                confirmButtonText: 'Evet, İptal Et',
                cancelButtonText: 'Vazgeç'
            }).then((result) => {
                if (result.isConfirmed) {
                    bilgi.email = (result.value || '').trim();
                    this.cancelAppointment(pnr, patientNumber, bilgi);
                }
            });
        },

        cancelAppointment(pnr, patientNumber, bilgi) {
            this.showLoading();

            const ajaxSettings = {
                url: `${this.config.apiUrl}/Appointment/Cancel`,
                method: 'POST',
                data: {
                    PNR: pnr,
                    PatientNumber: patientNumber
                },
                success: (response) => {
                    this.hideLoading();

                    $.ajax({
                        url: this.config.ajaxUrl,
                        method: 'POST',
                        // Klinik/hekim/tarih bilgisi BURADAN gonderilir: yerelde hasta
                        // kaydi tutulmadigi icin sunucunun bu bilgileri okuyacagi bir
                        // tablo yok. Iptal bildirimi personele bu veriyle gider.
                        data: {
                            action: 'caparv_cancel_appointment',
                            nonce: this.config.nonce,
                            pnr_no: pnr,
                            clinic_name: (bilgi && bilgi.clinic) || '',
                            doctor_name: (bilgi && bilgi.doctor) || '',
                            appointment_date: (bilgi && bilgi.datetime) || '',
                            appointment_time: (bilgi && bilgi.saat) || '',
                            patient_email: (bilgi && bilgi.email) || '',
                            patient_name: (bilgi && bilgi.ad) || ''
                        }
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Randevunuz başarıyla iptal edildi.',
                        confirmButtonText: 'Tamam'
                    }).then(() => {
                        $('#caparv-query-pnr').val('');
                        $('#caparv-query-patient-number').val('');
                        $('#caparv-query-result').hide();
                        $('#caparv-query-error').hide();
                    });
                },
                error: () => {
                    this.hideLoading();
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: 'Randevu iptal edilirken bir hata oluştu.',
                        confirmButtonText: 'Tamam'
                    });
                }
            };

            if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                ajaxSettings.headers = {
                    'Authorization': `Bearer ${this.config.bearerToken}`
                };
            }

            $.ajax(ajaxSettings);
        },

        showKVKK(e) {
            e.preventDefault();
            $('#caparv-kvkk-modal').fadeIn();
            const kvkkUrl = 'https://capaortodonti.com/kvkk/';
            $('#caparv-kvkk-content').html(
                '<div class="caparv-loading"><div class="caparv-spinner"></div><p>Y\u00fckleniyor...</p></div>' +
                '<iframe src="' + kvkkUrl + '" ' +
                'style="width:100%;height:60vh;border:0;display:block;" ' +
                'onload="this.previousElementSibling && this.previousElementSibling.remove();" ' +
                'title="KVKK Ayd\u0131nlatma Metni"></iframe>'
            );
        },

        closeModal() {
            $('#caparv-kvkk-modal').fadeOut();
        },

        formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        formatDisplayDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('tr-TR', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
        },

        showLoading() {
            $.blockUI({
                message: '<div class="caparv-loading"><div class="caparv-spinner"></div><p>Lütfen bekleyiniz...</p></div>',
                css: {
                    border: 'none',
                    padding: '20px',
                    backgroundColor: 'transparent'
                },
                overlayCSS: {
                    backgroundColor: '#000',
                    opacity: 0.6
                }
            });
        },

        hideLoading() {
            $.unblockUI();
        },

        showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: message,
                confirmButtonText: 'Tamam'
            });
        }
    };

    $(document).ready(function () {
        if ($('.caparv-appointment-wrapper').length > 0) {
            CaparvApp.init();
        }
    });

})(jQuery);
