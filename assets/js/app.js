; (function ($) {
    'use strict';

    const DentSoftApp = {
        config: window.dentsoftConfig || {},
        currentStep: 1,
        selectedData: {
            clinic: null,
            doctor: null,
            date: null,
            time: null
        },
        currentDate: new Date(),
        dateRange: 6,

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
            $(document).on('click', '.dentsoft-btn-next', () => this.nextStep());
            $(document).on('click', '.dentsoft-btn-prev', () => this.prevStep());
            $(document).on('click', '.dentsoft-btn-prev-week', () => this.changeWeek(-1));
            $(document).on('click', '.dentsoft-btn-next-week', () => this.changeWeek(1));
            $(document).on('click', '.dentsoft-time-slot', (e) => this.selectTimeSlot(e));

            $('#dentsoft-submit-btn').on('click', () => this.submitAppointment());
            $('#dentsoft-new-appointment-btn').on('click', () => this.resetForm());
            $('#dentsoft-kvkk-link').on('click', (e) => this.showKVKK(e));
            $('.dentsoft-modal-close').on('click', () => this.closeModal());

            $('#dentsoft-query-appointment-btn, #dentsoft-header-query-btn').on('click', () => this.showQuerySection());
            $('#dentsoft-query-submit-btn').on('click', () => this.queryAppointment());
            $('#dentsoft-query-close-btn').on('click', () => this.hideQuerySection());
            $('#dentsoft-cancel-appointment-btn').on('click', () => this.confirmCancelAppointment());

            $('#dentsoft-patient-phone').on('input', function () {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 10) value = value.substring(0, 10);
                $(this).val(value);
            });

            $('#dentsoft-patient-number').on('input', function () {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length > 11) value = value.substring(0, 11);
                $(this).val(value);
            });
        },

        initializePlugins() {
            if (typeof $.fn.select2 !== 'undefined') {
                $('.dentsoft-select2').select2({
                    width: '100%',
                    placeholder: 'Seçiniz...',
                    allowClear: false,
                    minimumResultsForSearch: -1
                });
            }
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
            const $select = $('#dentsoft-clinic-select');

            if ($select.data('select2')) {
                $select.select2('destroy');
            }

            $select.empty().append('<option value="">Klinik Seçiniz...</option>');

            if (clinics && clinics.length > 0) {
                clinics.forEach(clinic => {
                    const $option = $('<option>')
                        .val(clinic.ID)
                        .text(clinic.Name)
                        .data('clinic', clinic);
                    $select.append($option);
                });

                $select.select2({
                    placeholder: 'Klinik Seçiniz...',
                    minimumResultsForSearch: -1,
                    escapeMarkup: function (m) { return m; }
                }).on('change', (e) => {
                    this.onClinicChange();
                });

                if (clinics.length === 1) {
                    $select.val(clinics[0].ID).trigger('change');
                }
            }
        },

        onClinicChange() {
            const $select = $('#dentsoft-clinic-select');
            const clinicId = $select.val();

            this.selectedData.doctor = null;
            $('.dentsoft-btn-next[data-step="2"]').prop('disabled', true);

            if (clinicId) {
                const clinicData = $select.find('option:selected').data('clinic');
                if (clinicData) {
                    this.selectedData.clinic = clinicData;
                    $('.dentsoft-btn-next[data-step="1"]').prop('disabled', false);
                    this.updateSelectionSummary();
                    this.loadDoctors(clinicData.ID);
                    // Tek klinik: klinik adimi atlanir, dogrudan hekim adimina gec
                    if (this.currentStep === 1) { this.goToStep(2); }
                }
            } else {
                this.selectedData.clinic = null;
                $('.dentsoft-btn-next[data-step="1"]').prop('disabled', true);
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
            const $select = $('#dentsoft-doctor-select');

            if ($select.data('select2')) {
                $select.select2('destroy');
            }

            $select.empty().append('<option value="">Hekim Seçiniz...</option>');

            if (doctors && doctors.length > 0) {
                doctors.forEach(doctor => {
                    const fullName = `${doctor.User.FirstName} ${doctor.User.LastName}`;
                    const nearestDay = doctor.NearestDay?.Date ?
                        this.formatNearestDay(doctor.NearestDay.Date, doctor.NearestDay.Time?.Begin) :
                        'Müsait tarih yok';

                    const $option = $('<option>')
                        .val(doctor.User.ID)
                        .text(fullName)
                        .data('doctor', doctor)
                        .data('avatar', doctor.User.Avatar || `${this.config.pluginUrl}assets/img/default-avatar.png`)
                        .data('role', doctor.User.Roles || 'Diş Hekimi')
                        .data('nearest', nearestDay);

                    $select.append($option);
                });

                $select.select2({
                    placeholder: 'Hekim Seçiniz...',
                    minimumResultsForSearch: -1,
                    dropdownCssClass: 'dentsoft-doctor-dropdown',
                    formatResult: this.formatDoctorOption.bind(this),
                    formatSelection: this.formatDoctorSelection.bind(this),
                    escapeMarkup: function (m) { return m; }
                }).on('change', (e) => {
                    this.onDoctorChange();
                });
            } else {
                $('#dentsoft-doctor-error').text('Bu klinikde kayıtlı hekim bulunamadı.').show();
            }
        },

        formatDoctorOption(item) {
            if (!item.id) return item.text;

            const $option = $(item.element);
            const avatar = $option.data('avatar');
            const role = $option.data('role');
            const nearest = $option.data('nearest');

            return `
                <div class="dentsoft-doctor-item">
                    <img src="${avatar}" class="dentsoft-doctor-avatar" onerror="this.src='${this.config.pluginUrl}assets/img/default-avatar.png'" />
                    <div class="dentsoft-doctor-info">
                        <div class="dentsoft-doctor-name">${item.text}</div>
                        <div class="dentsoft-doctor-role">${role}</div>
                        <div class="dentsoft-doctor-nearest">${nearest}</div>
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
            const dayName = dateObj.toLocaleDateString('tr-TR', { weekday: 'long' });
            const dayNum = dateObj.getDate();
            const monthName = dateObj.toLocaleDateString('tr-TR', { month: 'long' });
            const timeStr = time ? ` - ${time}` : '';
            return `En erken: ${dayNum} ${monthName} ${dayName}${timeStr}`;
        },

        onDoctorChange() {
            const $select = $('#dentsoft-doctor-select');
            const doctorId = $select.val();

            if (doctorId) {
                const doctorData = $select.find('option:selected').data('doctor');
                if (doctorData) {
                    this.selectedData.doctor = doctorData;
                    $('.dentsoft-btn-next[data-step="2"]').prop('disabled', false);
                    this.updateSelectionSummary();
                }
            } else {
                this.selectedData.doctor = null;
                $('.dentsoft-btn-next[data-step="2"]').prop('disabled', true);
                this.updateSelectionSummary();
            }
        },

        updateSelectionSummary() {
            const $summary = $('#dentsoft-selection-summary');
            const $clinicItem = $('#dentsoft-selected-clinic');
            const $doctorItem = $('#dentsoft-selected-doctor');

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

        loadAppointmentSlots() {
            const clinicId = this.selectedData.clinic.ID;
            const doctorId = this.selectedData.doctor.User.ID;
            const dateStr = this.formatDate(this.currentDate);

            $('#dentsoft-calendar-loading').show();
            $('#dentsoft-calendar-container').hide();

            const ajaxSettings = {
                url: `${this.config.apiUrl}/Appointment/Doctor/${clinicId}/${doctorId}/${dateStr}/${this.dateRange}`,
                method: 'GET',
                dataType: 'json',
                success: (response) => {
                    $('#dentsoft-calendar-loading').hide();

                    if (response.Response && response.Response[0]) {
                        const slots = response.Response[0].Slot;
                        if (slots && Object.keys(slots).length > 0) {
                            this.renderCalendar(slots);
                            $('#dentsoft-calendar-controls').show();
                            $('#dentsoft-calendar-container').show();
                            $('#dentsoft-no-appointments').hide();
                        } else {
                            this.showNoAppointments();
                        }
                    } else {
                        this.showNoAppointments();
                    }
                },
                error: () => {
                    $('#dentsoft-calendar-loading').hide();
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
            const $container = $('#dentsoft-calendar-container');
            $container.empty();

            const $table = $('<table>').addClass('dentsoft-calendar-table');
            const $thead = $('<thead>');
            const $tbody = $('<tbody>');
            const $headerRow = $('<tr>');
            const $timeRow = $('<tr>');

            Object.keys(slots).forEach(date => {
                const dateObj = new Date(date);
                const dayName = dateObj.toLocaleDateString('tr-TR', { weekday: 'short' });
                const dayNum = dateObj.getDate();
                const monthName = dateObj.toLocaleDateString('tr-TR', { month: 'short' });

                $headerRow.append(`
                    <th>
                        <div class="calendar-date-header">
                            <span class="day-name">${dayName}</span>
                            <span class="day-num">${dayNum} ${monthName}</span>
                        </div>
                    </th>
                `);

                const $timeCell = $('<td>');
                const $timeList = $('<div>').addClass('dentsoft-time-list');

                slots[date].forEach(slot => {
                    const isAvailable = slot.Type === 'Available';
                    const $timeBtn = $('<button>')
                        .addClass('dentsoft-time-slot')
                        .attr('type', 'button')
                        .data('date', date)
                        .data('time', slot.Time.Begin)
                        .text(slot.Time.Begin);

                    if (!isAvailable) {
                        $timeBtn.addClass('disabled').prop('disabled', true);
                    }

                    $timeList.append($timeBtn);
                });

                $timeCell.append($timeList);
                $timeRow.append($timeCell);
            });

            $thead.append($headerRow);
            $tbody.append($timeRow);
            $table.append($thead).append($tbody);
            $container.append($table);
        },

        selectTimeSlot(e) {
            const $btn = $(e.currentTarget);

            if ($btn.hasClass('disabled')) {
                return;
            }

            const date = $btn.data('date');
            const time = $btn.data('time');

            $('.dentsoft-time-slot').removeClass('selected');
            $btn.addClass('selected');

            this.selectedData.date = date;
            this.selectedData.time = time;

            $('.dentsoft-btn-next').prop('disabled', false);
        },

        changeWeek(direction) {
            this.currentDate.setDate(this.currentDate.getDate() + (direction * this.dateRange));
            this.loadAppointmentSlots();
        },

        showNoAppointments() {
            $('#dentsoft-calendar-controls').hide();
            $('#dentsoft-calendar-container').hide();
            $('#dentsoft-no-appointments').show();

            if (this.selectedData.clinic && this.selectedData.clinic.ConcatInfo) {
                const contact = this.selectedData.clinic.ConcatInfo;
                let html = '<div class="dentsoft-contact-info">';

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
                $('#dentsoft-clinic-contact-info').html(html);
            }
        },

        submitAppointment() {
            if (!this.validateForm()) {
                return;
            }

            const clinicId = this.selectedData.clinic.ID;
            const contactRegion = '90';
            const contactMobile = $('#dentsoft-patient-phone').val().replace(/\D/g, '');

            this.sendApprovalCode(clinicId, contactRegion, contactMobile);
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
                        $('#dentsoft-kvkk-content').html(response.Response.Html);
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
                            onclick="$('#dentsoft-kvkk-modal').fadeIn();">
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

                    if (response.Response && response.Response.Appointment) {
                        this.saveToDatabase(response.Response);
                    } else if (response.Error) {
                        this.showError('Randevu oluşturulurken hata oluştu.');
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
            const data = {
                action: 'dentsoft_save_appointment',
                nonce: this.config.nonce,
                patient_number: $('#dentsoft-patient-number').val(),
                patient_name: $('#dentsoft-patient-name').val(),
                patient_surname: $('#dentsoft-patient-surname').val(),
                patient_phone: $('#dentsoft-patient-phone').val(),
                patient_birthday: $('#dentsoft-patient-birthday').val(),
                patient_email: $('#dentsoft-patient-email').val(),
                clinic_name: appointmentData.Clinic.Name,
                clinic_address: appointmentData.Clinic.ContactInfo.ContactAddress || '',
                clinic_phone: appointmentData.Clinic.ContactInfo.ContactPhone || '',
                doctor_name: appointmentData.User.Name,
                pnr_no: appointmentData.Appointment.PNR,
                appointment_date: `${appointmentData.Appointment.Date} ${appointmentData.Appointment.Time.Begin}`,
                appointment_status: 'pending'
            };

            $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showSuccess(appointmentData);
                    } else {
                        this.showError(response.data.message || 'Randevu kaydedilemedi.');
                    }
                },
                error: () => {
                    this.showError('Randevu kaydedilirken hata oluştu.');
                }
            });
        },

        showSuccess(appointmentData) {
            $('#dentsoft-summary-patient').text(`${$('#dentsoft-patient-name').val()} ${$('#dentsoft-patient-surname').val()}`);
            $('#dentsoft-summary-clinic').text(appointmentData.Clinic.Name);
            $('#dentsoft-summary-doctor').text(appointmentData.User.Name);
            $('#dentsoft-summary-datetime').text(
                `${this.formatDisplayDate(appointmentData.Appointment.Date)} ${appointmentData.Appointment.Time.Begin}`
            );
            $('#dentsoft-summary-pnr').text(appointmentData.Appointment.PNR);

            this.goToStep(5);

            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: this.config.strings.success || 'Randevunuz başarıyla oluşturuldu!',
                confirmButtonText: 'Tamam'
            });
        },

        validateForm() {
            const requiredFields = [
                { id: '#dentsoft-patient-number', name: 'TC Kimlik / Pasaport No' },
                { id: '#dentsoft-patient-name', name: 'Ad' },
                { id: '#dentsoft-patient-surname', name: 'Soyad' },
                { id: '#dentsoft-patient-phone', name: 'Telefon' }
            ];

            for (const field of requiredFields) {
                const value = $(field.id).val();
                if (!value || value.trim() === '') {
                    this.showError(`${field.name} alanı zorunludur.`);
                    $(field.id).focus();
                    return false;
                }
            }

            if (!$('#dentsoft-kvkk-checkbox').is(':checked')) {
                this.showError('KVKK onayı zorunludur.');
                return false;
            }

            return true;
        },

        getFormData() {
            const formData = new FormData();
            formData.append('PatientNumber', $('#dentsoft-patient-number').val());
            formData.append('PatientFirstName', $('#dentsoft-patient-name').val());
            formData.append('PatientLastName', $('#dentsoft-patient-surname').val());
            formData.append('ContactMobile', $('#dentsoft-patient-phone').val());
            formData.append('ContactRegion', '90');
            formData.append('Date', this.selectedData.date);
            formData.append('BeginTime', this.selectedData.time);

            if ($('#dentsoft-patient-birthday').val()) {
                formData.append('PatientBirthday', $('#dentsoft-patient-birthday').val());
            }

            if ($('#dentsoft-patient-email').val()) {
                formData.append('ContactEmail', $('#dentsoft-patient-email').val());
            }

            return formData;
        },

        nextStep() {
            if (this.currentStep === 2 && this.selectedData.doctor) {
                this.loadAppointmentSlots();
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

        goToStep(step) {
            $('.dentsoft-step').removeClass('active completed');
            $('.dentsoft-step-content').removeClass('active');

            for (let i = 1; i < step; i++) {
                $(`.dentsoft-step[data-step="${i}"]`).addClass('completed');
            }

            $(`.dentsoft-step[data-step="${step}"]`).addClass('active');
            $(`.dentsoft-step-content[data-step="${step}"]`).addClass('active');

            this.currentStep = step;

            $('html, body').animate({
                scrollTop: $('.dentsoft-appointment-wrapper').offset().top - 50
            }, 500);
        },

        resetForm() {
            this.currentStep = 1;
            this.selectedData = {
                clinic: null,
                doctor: null,
                date: null,
                time: null
            };

            $('#dentsoft-patient-form')[0].reset();
            $('#dentsoft-clinic-select').val('').trigger('change');
            $('#dentsoft-doctor-select').val('').trigger('change');

            this.updateSelectionSummary();
            this.hideQuerySection();
            this.goToStep(1);
        },

        showQuerySection() {
            $('#dentsoft-query-section').show().addClass('active');
            $('.dentsoft-step-content').removeClass('active');
            $('.dentsoft-step').removeClass('active');
            $('#dentsoft-query-result').hide();
            $('#dentsoft-query-error').hide();
            $('#dentsoft-query-pnr').val('');
            $('#dentsoft-query-patient-number').val('');

            $('html, body').animate({
                scrollTop: $('#dentsoft-query-section').offset().top - 50
            }, 500);
        },

        hideQuerySection() {
            $('#dentsoft-query-section').hide().removeClass('active');
            $('.dentsoft-step-content[data-step="1"]').addClass('active');
            $('.dentsoft-step[data-step="1"]').addClass('active');
        },

        queryAppointment() {
            const pnr = $('#dentsoft-query-pnr').val().trim();
            const patientNumber = $('#dentsoft-query-patient-number').val().trim();

            if (!pnr || !patientNumber) {
                $('#dentsoft-query-error').text('Lütfen tüm alanları doldurunuz.').show();
                return;
            }

            if (!/^\d{4}$/.test(patientNumber)) {
                $('#dentsoft-query-error').text('TC Kimlik No son 4 hane rakam olmalıdır.').show();
                return;
            }

            $('#dentsoft-query-error').hide();
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

                        $('#dentsoft-query-error').text(errorMsg).show();
                        $('#dentsoft-query-result').hide();
                    }
                },
                error: () => {
                    this.hideLoading();
                    $('#dentsoft-query-error').text('Randevu sorgulanırken hata oluştu.').show();
                }
            };

            if (this.config.bearerToken && this.config.bearerToken.trim() !== '') {
                ajaxSettings.headers = {
                    'Authorization': `Bearer ${this.config.bearerToken}`
                };
            }

            $.ajax(ajaxSettings);
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

            $('#dentsoft-query-patient-name').text(maskName(data.Patient.Name));
            $('#dentsoft-query-clinic').text(data.Clinic.Name);
            $('#dentsoft-query-doctor').text(`${data.User.Name} - ${data.User.Title || ''}`);
            $('#dentsoft-query-datetime').text(`${formatDate(data.Appointment.Date)} ${data.Appointment.Time.Begin} - ${data.Appointment.Time.End}`);
            $('#dentsoft-query-pnr-display').text(data.Appointment.PNR);

            $('#dentsoft-query-result').fadeIn();
            $('#dentsoft-cancel-appointment-btn').data('pnr', pnr).data('patient-number', patientNumber);

            $('html, body').animate({
                scrollTop: $('#dentsoft-query-result').offset().top - 50
            }, 500);
        },

        confirmCancelAppointment() {
            const pnr = $('#dentsoft-cancel-appointment-btn').data('pnr');
            const patientNumber = $('#dentsoft-cancel-appointment-btn').data('patient-number');

            Swal.fire({
                title: 'Emin misiniz?',
                text: 'Randevunuzu iptal etmek istediğinize emin misiniz?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--dentsoft-primary)',
                cancelButtonColor: 'var(--dentsoft-danger)',
                confirmButtonText: 'Evet, İptal Et',
                cancelButtonText: 'Vazgeç'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.cancelAppointment(pnr, patientNumber);
                }
            });
        },

        cancelAppointment(pnr, patientNumber) {
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

                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: 'Randevunuz başarıyla iptal edildi.',
                        confirmButtonText: 'Tamam'
                    }).then(() => {
                        $('#dentsoft-query-pnr').val('');
                        $('#dentsoft-query-patient-number').val('');
                        $('#dentsoft-query-result').hide();
                        $('#dentsoft-query-error').hide();
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
            $('#dentsoft-kvkk-modal').fadeIn();
            const kvkkUrl = 'https://capaortodonti.com/kvkk/';
            $('#dentsoft-kvkk-content').html(
                '<div class="dentsoft-loading"><div class="dentsoft-spinner"></div><p>Y\u00fckleniyor...</p></div>' +
                '<iframe src="' + kvkkUrl + '" ' +
                'style="width:100%;height:60vh;border:0;display:block;" ' +
                'onload="this.previousElementSibling && this.previousElementSibling.remove();" ' +
                'title="KVKK Ayd\u0131nlatma Metni"></iframe>'
            );
        },

        closeModal() {
            $('#dentsoft-kvkk-modal').fadeOut();
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
                message: '<div class="dentsoft-loading"><div class="dentsoft-spinner"></div><p>Lütfen bekleyiniz...</p></div>',
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
        if ($('.dentsoft-appointment-wrapper').length > 0) {
            DentSoftApp.init();
        }
    });

})(jQuery);
