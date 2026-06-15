; (function ($) {
    'use strict';

    var AppointmentManager = {
        activeStep: 1,
        currentDate: new Date().toISOString().split('T')[0],
        setDate: new Date().toISOString().split('T')[0],
        currentDoctorID: false,
        doctorNearestDay: 0,
        currentClinicID: false,
        currentClinicName: false,
        currentClinicTax: false,
        currentClinicAvatar: false,

        settings: (typeof dentsoft_settings !== 'undefined') ? dentsoft_settings : {},

        apiBase: (typeof dentsoft_settings !== 'undefined' && dentsoft_settings.api_url)
            ? dentsoft_settings.api_url
            : 'https://clinic.dentsoft.com.tr/Api/v1',

        clinicTax: (typeof dentsoft_settings !== 'undefined' && dentsoft_settings.vkn)
            ? dentsoft_settings.vkn
            : '',

        pluginUrl: (typeof dentsoft_settings !== 'undefined' && dentsoft_settings.plugin_url)
            ? dentsoft_settings.plugin_url
            : '',

        range: 6,

        /* Helper Functions */
        InitPhoneInput: function () {
            const input = document.querySelector("#ContactMobile");
            if (input) {
                this.phoneInput = window.intlTelInput(input, {
                    utilsScript: AppointmentManager.pluginUrl + '/assets/plugins/intl-tel-input/utils.js',
                    initialCountry: "tr",
                    separateDialCode: true,
                });
            }
        },

        BlockUI: function () {
            $.blockUI({
                message: '<div class="custom-loading-message"><i class="fa-regular fa-spinner fa-spin"></i> Lütfen bekleyiniz...</div>',
                css: {
                    border: 'none',
                    padding: '15px',
                    backgroundColor: 'transparent',
                    '-webkit-border-radius': '10px',
                    '-moz-border-radius': '10px',
                    opacity: .5,
                    color: '#fff'
                }
            });
        },

        IsMobile: function () {
            var userAgent = navigator.userAgent || navigator.vendor || window.opera;
            var isMobileDevice = /android|bb\d+|meego|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(userAgent) ||
                /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/.test(userAgent.substring(0, 4));

            return isMobileDevice;
        },

        NavigateToAppointment: function (ClinicTax) {
            window.location.href = 'https://online.dentsoft.com.tr/#' + ClinicTax;
        },

        /* Clinic Functions */
        FormatClinicOption: function (clinic) {
            return `
        <div class="row">
            <div class="d-flex align-items-center justify-content-start">
                <p class="p-responsive mb-0" data-clinicid="${clinic.id}">${clinic.name}</p>
            </div>
        </div>
        `;
        },

        OnClinicChange: function (clinic) {
            $('#DoctorChangeContent').addClass('d-none');
            $('#OfflineDoctor').addClass('d-none');
            $('#DoctorID').addClass('d-none');

            $('#DateLoad').addClass('d-none');

            $('#DoctorID').select2('destroy');
            $('#DoctorID').val(null);

            $('#ClinicName').html($('#ClinicID').select2('data').text);
            $('#DoctorImage').attr('src', $('#ClinicID').select2('data').avatar || plugin_url + '/assets/img/DefaultAvatar.png');
            $('#DoctorName').html('');
            $('#RolesText').html('');

            AppointmentManager.selectedClinic = clinic.added;

            AppointmentManager.currentClinicID = $('#ClinicID').select2('data').id;
            AppointmentManager.currentClinicName = $('#ClinicID').select2('data').name;
            AppointmentManager.currentClinicTax = $('#ClinicID').select2('data').tax;

            $('input[name=ClinicID]').val(this.currentClinicID);

            AppointmentManager.BlockUI();
            AppointmentManager.ChangeStep(2);
        },

        ClinicList: function () {
            // VKN kontrolü
            if (!AppointmentManager.clinicTax || AppointmentManager.clinicTax.trim() === '') {
                Swal.fire({
                    title: 'Yapılandırma Hatası',
                    text: 'Klinik vergi numarası (VKN) ayarlanmamış. Lütfen DentSoft ayarlarından VKN bilgisini giriniz.',
                    icon: 'error',
                    confirmButtonText: 'Tamam'
                });
                return false;
            }

            // API isteği
            $.ajax({
                url: `${AppointmentManager.apiBase}/Clinic/List/${AppointmentManager.clinicTax}`,
                method: 'GET',
                dataType: 'json',
                success: function (e) {
                    if (e.Status.Code == 100) {
                        $.unblockUI();

                        $('#NoClinicErr').addClass('d-none')
                        $('#ClinicID').removeClass('d-none');

                        if (e.Response.Clinic.length == 1) {
                            $('#ClinicID').addClass('d-none');
                            AppointmentManager.currentClinicName = e.Response.Clinic[0].Name;

                            AppointmentManager.currentClinicID = e.Response.Clinic[0].ID;
                            AppointmentManager.currentClinicTax = e.Response.Clinic[0].TaxNumber;
                            AppointmentManager.currentClinicAvatar = e.Response.Clinic[0].Avatar;

                            $('input[name=ClinicID]').val(e.Response.Clinic[0].ID);

                            AppointmentManager.ChangeStep(2);

                            return false;
                        }

                        $('#ClinicID').select2({
                            maximumSelectionSize: 1,
                            placeholder: 'Klinik Seç',
                            dropdownCssClass: 'bigdrop',
                            minimumResultsForSearch: -1,
                            dropdownParent: $(".appointmentcard-body"),
                            data: e.Response.Clinic.map(clinic => ({
                                id: clinic.ID,
                                name: clinic.Name,
                                text: `${clinic.Name + '<br> <small class="text-muted" style="font-size: 13px">' + clinic.ConcatInfo.ContactAddress + '</small>'}`,
                                tax: clinic.TaxNumber,
                                avatar: clinic.Avatar,
                                workTime: {
                                    startBegin: clinic.WorkTime.StartBegin,
                                    endBegin: clinic.WorkTime.EndBegin
                                },
                                contactInfo: {
                                    contactAddress: clinic.ConcatInfo.ContactAddress,
                                    contactCity: clinic.ConcatInfo.ContactCity,
                                    contactDistrict: clinic.ConcatInfo.ContactDistrict,
                                    contactEmail: clinic.ConcatInfo.ContactEmail,
                                    contactMap: clinic.ConcatInfo.ContactMap,
                                    contactWeb: clinic.ConcatInfo.ContactWeb,
                                    contactPhone: clinic.ConcatInfo.ContactPhone,
                                    contactWhatsapp: clinic.ConcatInfo.ContactWhatsapp
                                }
                            })),
                            formatResult: AppointmentManager.FormatClinicOption,
                            escapeMarkup: function (m) { return m; }
                        }).on('change', function (e) {
                            AppointmentManager.OnClinicChange(e);
                        });
                    } else {
                        $.unblockUI();
                        $('#NoClinicErr').removeClass('d-none').text('Klinik listesi alınamadı. Lütfen daha sonra tekrar deneyiniz.');
                    }
                },
                error: function (xhr, status, error) {
                    $.unblockUI();
                    $('#NoClinicErr').removeClass('d-none').text('Bağlantı hatası oluştu. Lütfen internet bağlantınızı kontrol edip tekrar deneyiniz.');
                    console.error('API Hatası:', error);
                }
            });
        },

        /* Doctor Functions */
        FormatDoctorOption: function (doctor) {
            return `
        <div class="row">
            <div class="d-flex align-items-center justify-content-start gap-3">
                <div class="avatar">
                    <img src="${doctor.avatar || plugin_url + '/assets/img/DefaultAvatar.png'}" alt="Avatar" width="50" height="50" style="object-fit: cover; border-radius: 50%;">
                </div>
                <div class="details">
                    <p class="p-responsive mb-0"><strong>${doctor.name}</strong> - ${doctor.role}</p>
                    <p class="p-responsive mb-0"><small>${doctor.nearestDay ? `<b>En Erken :</b> ${doctor.nearestDay}` : ''}</small></p>
                </div>
            </div>
        </div>
        `;
        },

        OnDoctorChange: function (doctor) {
            AppointmentManager.currentDoctorID = $('#DoctorID').select2('data').id;
            AppointmentManager.doctorNearestDay = $('#DoctorID').select2('data').nearestDayUnformatted;

            $('#DateLoad').removeClass('d-none');
            $('#PatientContent').removeClass('d-none');

            $('#DoctorImage').attr('src', $('#DoctorID').select2('data').avatar || plugin_url + '/assets/img/DefaultAvatar.png');
            $('#DoctorName').html($('#DoctorID').select2('data').name);
            $('#RolesText').html($('#DoctorID').select2('data').role);
            $('input[name=DoctorID]').val(AppointmentManager.currentDoctorID);

            AppointmentManager.BlockUI();
            AppointmentManager.ChangeStep(3);
        },

        DoctorList: function (e) {
            $('#ClinicName').text(AppointmentManager.currentClinicName);

            $.getJSON(`${AppointmentManager.apiBase}/Clinic/DoctorList/${AppointmentManager.currentClinicTax}/${AppointmentManager.currentClinicID}`, function (e) {
                if (e.Response.Users.length > 0) {
                    if (AppointmentManager.currentClinicAvatar) {
                        $('#DoctorImage').attr('src', AppointmentManager.currentClinicAvatar);
                    }
                    $('#noDocErr').addClass('d-none');
                    $('#DoctorID').removeClass('d-none');
                    $.unblockUI();

                    $('#DoctorID').select2({
                        maximumSelectionSize: 1,
                        placeholder: 'Hekim Seç',
                        dropdownCssClass: 'bigdrop',
                        minimumResultsForSearch: -1,
                        data: e.Response.Users.map(doctor => ({
                            id: doctor.User.ID,
                            nearestDay: doctor.NearestDay?.Date ? AppointmentManager.FormatTableDate(doctor.NearestDay.Date, "Full", doctor.NearestDay.Time.Begin) : '',
                            nearestDayUnformatted: doctor.NearestDay.Date,
                            avatar: doctor.User.Avatar,
                            text: `${doctor.User.FirstName + ' ' + doctor.User.LastName + '<br> <small class="text-muted" style="font-size: 13px">' + doctor.User.Roles + '<small>'}`,
                            name: `${doctor.User.FirstName + ' ' + doctor.User.LastName}`,
                            role: doctor.User.Roles,
                            title: doctor.User.Title
                        })),
                        formatResult: AppointmentManager.FormatDoctorOption,
                        dropdownParent: $(".appointmentcard-body"),
                        escapeMarkup: function (m) { return m; }
                    }).on('change', function (e) {
                        AppointmentManager.OnDoctorChange(e);
                    });

                } else {
                    $.unblockUI();
                    $('#DoctorID').addClass('d-none');
                    $('#DateLoad').addClass('d-none');
                    $('#OfflineDoctor').addClass('d-none');
                    $('#PatientContent').addClass('d-none');
                    $('#noDocErr').removeClass('d-none');
                }
            });
        },

        /* Date Functions */
        formatDate: function (date) {
            var d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2)
                month = '0' + month;
            if (day.length < 2)
                day = '0' + day;

            return [year, month, day].join('-');
        },

        FormatTableDate: function (dateString, format, time) {
            const dateOptions = format === "Date"
                ? { weekday: 'short' }
                : { day: 'numeric', month: 'long', year: 'numeric' };

            const formattedDate = new Date(dateString).toLocaleDateString('tr-TR', dateOptions);
            const formattedTime = time ? ` ${time}` : '';

            return formattedDate + formattedTime;
        },

        DateChange: function (AddDay = false) {
            const NewDate = new Date(AppointmentManager.currentDate);
            NewDate.setDate(NewDate.getDate() + (AddDay * AppointmentManager.range));

            if (AppointmentManager.formatDate(NewDate) < AppointmentManager.formatDate(new Date())) {
                return false;
            }

            if (AddDay > 0) {
                if (AppointmentManager.formatDate(NewDate) < AppointmentManager.currentDate) {
                    return false;
                }
            }
            if (AddDay < 0) {
                if (AppointmentManager.formatDate(NewDate) > AppointmentManager.currentDate) {
                    return false;
                }
            }

            AppointmentManager.GetDate(AppointmentManager.formatDate(NewDate));
        },

        HourSelect: function (AppDate = false, Hour = false, ID = false) {
            $('.list-group-item').removeClass('active-date');
            $('#Date_' + AppDate + '_' + ID).addClass('active-date');

            $('[name=Date]').val(AppDate);
            $('[name=BeginTime]').val(Hour);
            $('#SetDate').html($('#Date_' + AppDate + '_' + ID).attr('data-datetext') + ' - ' + Hour);

            AppointmentManager.ChangeStep(4);
        },

        GetDate: function (SetDate = AppointmentManager.doctorNearestDay) {
            AppointmentManager.BlockUI();

            $.getJSON(`${AppointmentManager.apiBase}/Appointment/Doctor/${AppointmentManager.currentClinicID}/${AppointmentManager.currentDoctorID}/${SetDate}/${AppointmentManager.range}`, function (e) {
                $.unblockUI();

                if (e.Response[0].NearestDay.length != 0) {
                    $('#DateLoad').removeClass('d-none');
                    $('#OfflineDoctor').addClass('d-none');

                    var DoctorSlot = e.Response[0].Slot

                    AppointmentManager.RenderDateTable(DoctorSlot);
                    AppointmentManager.currentDate = SetDate;

                    $('html, body').animate({
                        scrollTop: $('#DateLoad').offset().top
                    }, 500);
                } else {
                    $('#DateLoad').addClass('d-none');
                    $('#OfflineDoctor').removeClass('d-none');

                    if (AppointmentManager.selectedClinic.text) {
                        $('#ODClinicName').removeClass('d-none');
                        $('#ODClinicName > p').text(e.Response[0].Clinic.Name);
                    }

                    if (AppointmentManager.selectedClinic.contactInfo.contactAddress) {
                        $('#ODClinicAddress').removeClass('d-none');
                        $('#ODClinicAddress > p').text(AppointmentManager.selectedClinic.contactInfo.contactAddress);
                    }

                    if (AppointmentManager.selectedClinic.contactInfo.contactEmail) {
                        $('#ODClinicMail').removeClass('d-none');

                        $('#ODClinicMail > a').text(AppointmentManager.selectedClinic.contactInfo.contactEmail);
                        $('#ODClinicMail > a').attr('href', `${'mailto:' + AppointmentManager.selectedClinic.contactInfo.contactEmail} `);
                    }

                    if (AppointmentManager.selectedClinic.contactInfo.contactPhone) {
                        $('#ODClinicPhone').removeClass('d-none');

                        $('#ODClinicPhone > a').text(AppointmentManager.selectedClinic.contactInfo.contactPhone);
                        $('#ODClinicPhone > a').attr('href', `${'tel:90' + AppointmentManager.selectedClinic.contactInfo.contactPhone} `);
                    }

                    if (AppointmentManager.selectedClinic.contactInfo.contactWhatsapp) {
                        $('#ODClinicWhatsapp').removeClass('d-none');

                        $('#ODClinicWhatsapp > a').text(AppointmentManager.selectedClinic.contactInfo.contactWhatsapp);
                        $('#ODClinicWhatsapp > a').attr('href', `${'https://wa.me/90' + AppointmentManager.selectedClinic.contactInfo.contactWhatsapp} `);
                    }

                    if (AppointmentManager.selectedClinic.workTime.startBegin) {
                        $('#ODClinicTimes').removeClass('d-none');

                        $('#ODClinicTimes > p').text(
                            AppointmentManager.selectedClinic.workTime.startBegin.slice(0, 5) + ' - ' +
                            AppointmentManager.selectedClinic.workTime.endBegin.slice(0, 5)
                        );
                    }

                    $('html, body').animate({
                        scrollTop: $('#OfflineDoctor').offset().top
                    }, 500);
                }
            });
        },

        RenderDateTable: function (appointmentData) {
            const tableHead = $("#Table_Date thead tr");
            const tableBody = $("#Table_Date tbody tr");

            tableHead.empty();
            tableBody.empty();

            $.each(appointmentData, (date, appointments) => {
                const th = $("<th>")
                    .addClass('small')
                    .html(`${AppointmentManager.FormatTableDate(date, 'Y-m-d')}<br>${AppointmentManager.FormatTableDate(date, 'Date')}`);
                tableHead.append(th);

                const td = $("<td>").css("vertical-align", "top");
                const listGroup = $("<div>").addClass("list-group");

                $.each(appointments, (index, appointment) => {
                    const uniqueId = `Date_${date}_${index}`;
                    const link = $("<a>")
                        .attr("href", "javascript:void(0)")
                        .addClass("list-group-item list-group-item-action text-center border-primary")
                        .css('color', '#00cc61')
                        .attr("id", uniqueId)
                        .attr("data-datetext", AppointmentManager.FormatTableDate(date, 'Y-m-d'))
                        .text(appointment.Time.Begin);

                    if (appointment.Type === "Available") {
                        link.attr('onclick', 'AppointmentManager.HourSelect("' + date + '", "' + appointment.Time.Begin + '", ' + index + ')');
                    } else {
                        link.removeClass("text-primary border-primary");
                        link.addClass("date-disabled");
                        link.css("pointer-events", "none");
                    }

                    listGroup.append(link);
                });

                td.append(listGroup);
                tableBody.append(td);
            });
        },

        /* Form Functions */
        LoadForm: function () {
            AppointmentManager.InitPhoneInput();
            $('#DoctorContent').removeClass('d-none');
            $('#PatientContent').removeClass('d-none');
            $('#SetDate').removeClass('d-none');

            $('html, body').animate({
                scrollTop: $('#AppointmentForm').offset().top
            }, 500);
        },

        CheckFormValid: function () {
            const form = $("#AppointmentForm");
            form.validate({
                rules: {
                    PatientNumber: {
                        required: true,
                        minlength: 11
                    },
                    PatientFirstName: {
                        required: true
                    },
                    PatientLastName: {
                        required: true
                    },
                    ContactMobile: {
                        required: true,
                    },
                    kvkk: {
                        required: true,
                    }
                },
                messages: {
                    PatientNumber: {
                        required: "TC / Pasaport No zorunludur.",
                        minlength: "TC / Pasaport No en az 11 karakter olmalıdır."
                    },
                    PatientFirstName: {
                        required: "Adınız zorunludur."
                    },
                    PatientLastName: {
                        required: "Soyadınız zorunludur."
                    },
                    ContactMobile: {
                        required: "Cep Telefonu Numarası zorunludur.",
                        minlength: "Cep Telefonu Numarası en az 10 karakter olmalıdır.",
                        digits: "Cep Telefonu Numarası sadece rakamlardan oluşmalıdır."
                    },
                    kvkk: {
                        required: "KVKK Zorunludur.",
                    }
                },
                errorElement: "div",
                errorPlacement: function (error, element) {
                    error.addClass("text-danger mt-1");
                    if (element[0].id == "ContactMobile") {
                        error.insertAfter(element);
                        $(".iti__country-container").css('top', '-28px')
                    } else {
                        error.insertAfter(element);
                    }
                    element.addClass("border-danger");
                },
                highlight: function (element) {
                    if (element.id == "ContactMobile") {
                        $(element).addClass("border-danger");
                        $(".iti__country-container").css('top', '-28px')
                    }
                    $(element).addClass("border-danger");
                },
                unhighlight: function (element) {
                    $(element).removeClass("border-danger");

                    if (element.id == "ContactMobile") {
                        $(".iti__country-container").css('top', '0');
                    }
                },
            });

            return form.valid();
        },

        GetDataShare: function () {

            $.ajax({
                url: AppointmentManager.ApprovalDataShareRef,
                type: 'POST',
                data: {
                    ClinicID: AppointmentManager.currentClinicID,
                    ContactRegion: '90',
                    ContactMobile: '5555555555',
                    Type: 'Send',
                },
                dataType: 'json',
                beforeSend: function () {
                    AppointmentManager.BlockUI();
                },
                success: (e) => {
                    $.unblockUI();
                    console.log('GetDataShare: ', e);

                    $('#modalContent').html(e.Response.Html);

                    var modal = new bootstrap.Modal(document.getElementById('kvkkModal'));
                    modal.show();
                },
                error: (xhr, status, error) => {
                    $.unblockUI();
                    console.error('GetDataShare Error:', error);
                }
            });
        },

        SendApprovalCode: function (ClinicID, ContactRegion, ContactMobile, Type, callback) {

            if (AppointmentManager.CheckFormValid()) {
                $.ajax({
                    url: AppointmentManager.ApprovalDataShareRef,
                    type: 'POST',
                    data: {
                        ClinicID: ClinicID,
                        ContactRegion: ContactRegion,
                        ContactMobile: ContactMobile,
                        Type: Type,
                    },
                    dataType: 'json',
                    beforeSend: function () {
                        AppointmentManager.BlockUI();
                    },
                    success: (e) => {
                        $.unblockUI();
                        // console.log('SendApprovalCode: ', e);

                        $('#modalContent').html(e.Response.Html);

                        var modal = new bootstrap.Modal(document.getElementById('kvkkModal'));

                        Swal.fire({
                            title: "6 Haneli KVKK Onay Kodunu Girin",
                            input: "text",
                            inputAttributes: {
                                pattern: "[0-9]{6}",
                                maxlength: 6
                            },
                            showCancelButton: true,
                            confirmButtonText: 'Onayla',
                            cancelButtonText: 'İptal',
                            // html: '<div id="error-message" class="text-danger mt-2"></div> <button type="button" class="btn btn-primary text-white" data-bs-toggle="modal" data-bs-target="#kvkkModal">KVKK Metnini Görüntüle</button>',
                            preConfirm: async (ApprovalCode) => {
                                if (!ApprovalCode || !/^\d{6}$/.test(ApprovalCode)) {
                                    Swal.showValidationMessage("Onay kodu 6 haneli olmalıdır ve sadece rakamlardan oluşmalıdır.");
                                    return false;
                                }

                                const result = await AppointmentManager.CheckApprovalCode(ApprovalCode, 'Check');
                                // console.log('CheckApprovalCode: ', result);
                                if (!result) {
                                    Swal.showValidationMessage('Hatalı KVKK Onay Kodu !');
                                    return false;
                                }
                            }
                        }).then((result) => {
                            // console.log(result);
                            if (result.isConfirmed) {
                                if (result.value) {
                                    callback(true);
                                } else {
                                    Swal.update({
                                        html: '<div id="error-message" class="text-danger mt-2">Girdiğiniz onay kodu yanlış. Lütfen tekrar deneyin.</div>'
                                    });
                                }
                            } else {
                                // console.log("İşlem iptal edildi.");
                                callback(false);
                            }
                        });
                    },
                    error: (xhr, status, error) => {
                        $.unblockUI();
                        console.error('SendApprovalCode Error:', error);
                        callback(false);
                    }
                });
            } else {
                callback(false);
            }
        },

        CheckApprovalCode: function (Code, Type) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: AppointmentManager.ApprovalDataShareRef,
                    type: 'POST',
                    data: {
                        Code: Code,
                        Type: Type,
                    },
                    dataType: 'json',
                    beforeSend: function () {
                        AppointmentManager.BlockUI();
                    },
                    success: (e) => {
                        $.unblockUI();
                        // console.log('CheckApprovalCode: ', e);

                        if (e.Response.Check) {
                            // console.log("True");
                            resolve(true);
                        } else {
                            // console.log("False");
                            resolve(false);
                        }
                    },
                    error: (xhr, status, error) => {
                        $.unblockUI();
                        console.error('CheckApprovalCode Error:', error);
                        reject(error);
                    }
                });
            });
        },

        FormSubmit: function () {

            var countryData = AppointmentManager.phoneInput.getSelectedCountryData();
            var countryCode = countryData.dialCode;

            var formData = new FormData(document.getElementById('AppointmentForm'));
            formData.append('ContactRegion', countryCode);

            var contactMobile = formData.get('ContactMobile');
            contactMobile = parseInt(contactMobile.replace(/\s+/g, ''), 10);
            formData.set('ContactMobile', contactMobile);

            var patientBirthday = formData.get('PatientBirthday');
            if (!patientBirthday) {
                formData.delete('PatientBirthday');
            }

            $('#AppointmentForm .form-control').removeClass('border-danger');
            $('#AppointmentForm .error-message').remove();

            AppointmentManager.SendApprovalCode(formData.get('ClinicID'), countryCode, contactMobile, 'Send', function (ApprovalCode) {

                if (ApprovalCode) {

                    $.ajax({
                        url: `${AppointmentManager.SetFormRef + '/' + formData.get('ClinicID') + '/' + formData.get('DoctorID')}`,
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        dataType: 'json',
                        beforeSend: function () {
                            AppointmentManager.BlockUI();
                        },
                        success: (e) => {
                            $.unblockUI();
                            //console.log(e);

                            if (e.Error) {

                                $.each(e.Error, (field, messages) => {
                                    if (field == 'Error' || field == 'Appointment') {
                                        var ErrorText = $.map(messages, (error) => error).join('<br>');
                                        Swal.fire({
                                            title: "Opss!!",
                                            html: ErrorText,
                                            showConfirmButton: false,
                                            timer: 3000
                                        });
                                    }
                                    var input = $(`#AppointmentForm [name="${field}"]`);
                                    input.addClass('border-danger');

                                    var errorMessage = `<div class="error-message text-danger small mt-1">${messages[field]}</div>`;
                                    input.after(errorMessage);
                                });
                            }

                            if (e.Response.Appointment) {
                                const getCharacter = (str, n) => str.slice(-n);
                                const PatientNumber = getCharacter($('#PatientNumber').val(), 4);

                                AppointmentManager.SaveAppointmentToDatabase(e.Response);
                                //AppointmentManager.AppointmentSummary(e.Response.Appointment.PNR, PatientNumber);
                            }
                        }
                    });
                }
            });
        },

        SaveAppointmentToDatabase: function (appointmentData) {
            console.log("Gelen Randevu Verisi:", JSON.stringify(appointmentData, null, 2));

            // Tarih formatını düzenleme
            var appointmentDateTime = appointmentData.Appointment.Date + ' ' + appointmentData.Appointment.Time.Begin;

            var appointmentDetails = {
                action: 'save_appointment',
                patient_number: $('#PatientNumber').val(),
                patient_name: $('#PatientFirstName').val(),
                patient_surname: $('#PatientLastName').val(),
                patient_phone: $('#ContactMobile').val(),
                patient_birthday: $('#PatientBirthday').val() || null,
                patient_email: $('#PatientEmail').val() || null,
                clinic_name: appointmentData.Clinic.Name,
                clinic_address: appointmentData.Clinic.ContactInfo.ContactAddress,
                clinic_phone: appointmentData.Clinic.ContactInfo.ContactPhone,
                doctor_name: appointmentData.User.Name,
                pnr_no: appointmentData.Appointment.PNR,
                appointment_date: appointmentDateTime,
                appointment_status: 'active'
            };

            console.log("Veritabanına Gönderilecek Veri:", JSON.stringify(appointmentDetails, null, 2));

            $.ajax({
                url: dentsoft_ajax.ajax_url,
                type: 'POST',
                data: {
                    ...appointmentDetails,
                    nonce: dentsoft_ajax.nonce
                },
                success: function (response) {
                    console.log("Ajax Yanıtı:", response);
                    if (response.success) {
                        console.log('Randevu başarıyla kaydedildi');
                        // Başarılı kayıt sonrası randevu özetini göster
                        const PatientNumber = $('#PatientNumber').val().slice(-4);
                        AppointmentManager.AppointmentSummary(appointmentData.Appointment.PNR, PatientNumber);
                    } else {
                        console.error('Randevu kaydedilirken hata:', response);
                        Swal.fire({
                            title: 'Hata!',
                            text: response.data || 'Randevu kaydedilirken bir hata oluştu. Lütfen daha sonra tekrar deneyiniz.',
                            icon: 'error',
                            confirmButtonText: 'Tamam'
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Ajax hatası:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    Swal.fire({
                        title: 'Hata!',
                        text: 'Bağlantı hatası oluştu. Lütfen internet bağlantınızı kontrol edip tekrar deneyiniz.',
                        icon: 'error',
                        confirmButtonText: 'Tamam'
                    });
                }
            });
        },

        /* Summary Functions */
        AppointmentSummaryForm: function () {

            var getCharacter = (str, n) => str.slice(-n);
            var PatientNumber = getCharacter($('#PatientNumber').val(), 4);

            var PNRNo = $('#PNRNo').val();

            $.ajax({
                url: AppointmentManager.AppointmentSummaryRef,
                type: 'POST',
                data: {
                    PatientNumber: PatientNumber,
                    PNR: PNRNo
                },
                dataType: 'json',
                beforeSend: function () {
                    AppointmentManager.BlockUI();
                },
                success: function (e) {
                    $.unblockUI();
                    //console.log('AppointmentSummaryForm', e);

                    //console.log(e.Error.length);
                    if (e.Error.length == 0) {
                        //console.log("No error");
                        $('#AppointmentSummaryHtml, #SumDoctorImage, #SumDoctorName, #SumDoctorRole, #AppointmentBody').removeClass('d-none');

                        $('#AppointmentHeader').removeClass('bg-danger text-white justify-content-center');
                        $('#ErrText').html('');
                        $('#SummaryTitle').html('<i class="far fa-check text-primary me-3"></i> Randevunuz Detaylarını Aşağıdan İnceleyebilirsiniz');

                        $('#SumPatientFullName').text(e.Response.Patient.Name.replace(/^(\w)\w*\s+(\w)\w*$/, '$1*** $2***'));
                        $('#SumDoctorImage').attr('src', e.Response.User.Avatar ? e.Response.User.Avatar : 'img/DefaultAvatar.png');
                        $('#SumDoctorName').html(e.Response.User.Name);
                        $('#SumDoctorRole').html(e.Response.User.Title);
                        $('#SumClinicName').html(e.Response.Clinic.Name);
                        $('#SumClinicAddress').html(e.Response.Clinic.ContactInfo.ContactAddress);
                        $('#SumClinicPhone').html(e.Response.Clinic.ContactInfo.ContactPhone);
                        $('#SumPnrNo').html(e.Response.Appointment.PNR);
                        $('#SumDate').html(AppointmentManager.FormatTableDate(e.Response.Appointment.Date, "Full") + ' ' + e.Response.Appointment.Time.Begin + ' - ' + e.Response.Appointment.Time.End);

                        $('html, body').animate({
                            scrollTop: $('#AppointmentSummaryHtml').offset().top
                        }, 500);

                        $('#AppointmentCancelBtn').on('click', function () {

                            Swal.fire({
                                title: 'Emin misiniz?',
                                text: "Randevunuzu iptal etmek istediğinize emin misiniz?",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#00cc61',
                                cancelButtonColor: '#dc3545',
                                confirmButtonText: 'Evet',
                                cancelButtonText: 'Hayır'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    Swal.fire({
                                        title: 'Başarılı',
                                        text: "Randevunuz iptal edildi",
                                        icon: 'success',
                                    }).then((result) => {
                                        AppointmentManager.AppointmentCancel(PNRCode, PatientNumber);
                                    });
                                } else {
                                    //
                                }
                            });
                        });
                    } else {
                        $('#AppointmentSummaryForm .error-message').remove();
                        $('#AppointmentSummaryForm .form-control').removeClass('border-danger');

                        $.each(e.Error, function (key, val) {

                            if (val.includes("PNR")) {
                                $('#PNRNo').addClass('border-danger');
                                $('#PNRNo').after('<small class="text-danger error-message">PNR numarası gereklidir.</small>'); // Hata mesajını ekle
                            } else if (val.includes("PatientNumber")) {
                                $('#PatientNumber').addClass('border-danger');
                                $('#PatientNumber').after('<small class="text-danger error-message">Hasta numarası gereklidir.</small>'); // Hata mesajını ekle
                            } else if (val.includes("Appointment")) {

                                $('#AppointmentSummaryHtml').removeClass('d-none');
                                $('#SumDoctorImage, #SumDoctorName, #SumDoctorRole, #AppointmentBody').addClass('d-none');

                                $('#AppointmentHeader').addClass('bg-danger text-white justify-content-center');
                                $('#ErrText').html(val);
                                $('#SummaryTitle').html('<i class="far fa-circle-exclamation text-danger me-3"></i>' + e.Status.Message)
                            }

                            $('html, body').animate({
                                scrollTop: $('#AppointmentSummaryHtml').offset().top
                            }, 500);
                        });

                    }
                }
            });
        },

        AppointmentSummary: function (PNRCode, PatientNumber) {

            $.ajax({
                url: AppointmentManager.AppointmentSummaryRef,
                type: 'POST',
                data: {
                    PatientNumber: PatientNumber,
                    PNR: PNRCode
                },
                dataType: 'json',
                beforeSend: function () {
                    AppointmentManager.BlockUI();
                },
                success: (e) => {
                    $.unblockUI();
                    console.log("AppointmentSummary", e);

                    $('#AppointmentHtml').addClass('d-none');
                    $('#AppointmentSummaryHtml').removeClass('d-none');

                    $('#SumPatientFullName').text(e.Response.Patient.Name.replace(/^(\w)\w*\s+(\w)\w*$/, '$1*** $2***'));
                    $('#SumDoctorImage').attr('src', e.Response.User.Avatar ? e.Response.User.Avatar : 'img/DefaultAvatar.png');
                    $('#SumDoctorName').html(e.Response.User.Name);
                    $('#SumDoctorRole').html(e.Response.User.Title);
                    $('#SumClinicName').html(e.Response.Clinic.Name);
                    $('#SumClinicAddress').html(e.Response.Clinic.ContactInfo.ContactAddress);
                    $('#SumClinicPhone').html(e.Response.Clinic.ContactInfo.ContactPhone);
                    $('#SumPnrNo').html(e.Response.Appointment.PNR);
                    $('#SumDate').html(AppointmentManager.FormatTableDate(e.Response.Appointment.Date, "Full") + ' ' + e.Response.Appointment.Time.Begin + ' - ' + e.Response.Appointment.Time.End);

                    $('#AppointmentCancelBtn').on('click', function () {

                        Swal.fire({
                            title: 'Emin misiniz?',
                            text: "Randevunuzu iptal etmek istediğinize emin misiniz?",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#00cc61',
                            cancelButtonColor: '#dc3545',
                            confirmButtonText: 'Evet',
                            cancelButtonText: 'Hayır'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                Swal.fire({
                                    title: 'Başarılı',
                                    text: "Randevunuz iptal edildi",
                                    icon: 'success',
                                }).then((result) => {
                                    AppointmentManager.AppointmentCancel(PNRCode, PatientNumber);
                                });
                            } else {
                                //
                            }
                        });
                    });
                }
            });
        },

        AppointmentCancel: function (PNRCode, PatientNumber) {

            $.ajax({
                url: AppointmentManager.AppointmentCancelRef,
                type: 'POST',
                data: {
                    PNR: PNRCode,
                    PatientNumber: PatientNumber
                },
                dataType: 'json',
                beforeSend: function () {
                    AppointmentManager.BlockUI();
                },
                success: (e) => {
                    $.unblockUI();
                    //console.log("AppointmentCancel", e);
                    window.location.reload();
                }
            });
        },

        /* Step Functions */
        UpdateStepUI: function () {
            $('#step-' + (AppointmentManager.activeStep - 1)).removeClass('current-item');
        },

        ResetStepContent: function () {
            $('#PatientContent, #SetDate, #DoctorContent, #ChangeUser').addClass('d-none');
        },

        ChangeStep: function (Step) {
            AppointmentManager.activeStep = Step;
            AppointmentManager.UpdateStepUI();
            AppointmentManager.ResetStepContent();

            switch (Step) {
                case 1:
                    $('#ClinicChangeContent').removeClass('d-none');
                    AppointmentManager.ClinicList();
                    break;
                case 2:
                    $('#DoctorContent, #DoctorChangeContent').removeClass('d-none');
                    AppointmentManager.DoctorList();
                    break;
                case 3:
                    $('#DoctorContent, #DoctorChangeContent, #ChangeUser, #DateLoad').removeClass('d-none');
                    AppointmentManager.GetDate();
                    break;
                case 4:
                    AppointmentManager.LoadForm();
                    break;
                case 5:
                    $('#AppointmentHtml').addClass('d-none');
                    $('#AppointmentSummaryHtml').removeClass('d-none');
                    break;
                default:
            }
        }

    };

    window.AppointmentManager = AppointmentManager;

    //$(document).ready(function () {
    //    AppointmentManager.range = AppointmentManager.IsMobile() ? 3 : 6;
    //    AppointmentManager.ChangeStep(1);
    //});

    AppointmentManager.startStep = AppointmentManager.activeStep;
    AppointmentManager.range = AppointmentManager.IsMobile() ? 3 : 6;

    AppointmentManager.ClinicListRef = `${AppointmentManager.apiBase + '/Clinic/List'}`;
    AppointmentManager.DoctorListRef = `${AppointmentManager.apiBase + '/Clinic/DoctorList'}`;
    AppointmentManager.GetDateRef = `${AppointmentManager.apiBase + '/Appointment/Doctor'}`;
    AppointmentManager.ApprovalDataShareRef = `${AppointmentManager.apiBase + '/ApprovalDataShare'}`;
    AppointmentManager.SetFormRef = `${AppointmentManager.apiBase + '/Appointment/New'}`;
    AppointmentManager.AppointmentSummaryRef = `${AppointmentManager.apiBase + '/Appointment/Info'}`;
    AppointmentManager.AppointmentCancelRef = `${AppointmentManager.apiBase + '/Appointment/Cancel'}`;

    AppointmentManager.ChangeStep(1);

})(jQuery);