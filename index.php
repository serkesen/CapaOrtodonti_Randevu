<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<main class="d-flex flex-column justify-content-between" style="min-height: 80vh;">
    <section class="d-flex flex-column position-relative content-section pt-0">

        <h2 class="title text-primary"></h2>
        <div class="appointment-card shadow" id="AppointmentHtml">
            <div class="appointment-header d-flex align-items-center d-none" id="DoctorContent">
                <div class="">
                    <img id="DoctorImage" src="<?php echo esc_url(plugin_dir_url(__FILE__)); ?>assets/img/DefaultAvatar.png" alt="Doktor Avatar" class="doctor-avatar me-3">
                </div>
                <div>
                    <span class="h4 fw-bolder mb-0" id="DoctorName"></span> - <span class="h5 mb-0" id="ClinicName"></span>
                    <br>
                    <p class="mb-0" id="RolesText"></p>
                    <p class="mb-0" id="SetDate"></p>
                </div>
            </div>

            <div class="appointmentcard-body p-4">

                <div class="row justify-content-center align-items-center">
                    <div class="col-md-12 text-center">
                        <ul class="step-wizard-list">
                            <li id="step-1" class="step-wizard-item current-item">
                                <span class="progress-count">
                                    <i class="fa-regular fa-house-chimney-medical"></i>
                                </span>
                                <span class="progress-label progress-label d-flex align-items-center justify-content-center gap-3">
                                    Klinik
                                </span>
                            </li>
                            <li id="step-2" class="step-wizard-item current-item">
                                <span class="progress-count">
                                    <i class="fa-regular fa-user-md"></i>
                                </span>
                                <span class="progress-label progress-label d-flex align-items-center justify-content-center gap-3">
                                    Hekim
                                </span>
                            </li>
                            <li id="step-3" class="step-wizard-item current-item">
                                <span class="progress-count">
                                    <i class="fa-regular fa-calendar"></i>
                                </span>
                                <span class="progress-label progress-label d-flex align-items-center justify-content-center gap-3">
                                    Tarih
                                </span>
                            </li>
                            <li id="step-4" class="step-wizard-item current-item">
                                <span class="progress-count">
                                    <i class="fa-regular fa-id-card"></i>
                                </span>
                                <span class="progress-label progress-label d-flex align-items-center justify-content-center gap-3">
                                    Hasta
                                </span>
                            </li>
                            <li id="step-5" class="step-wizard-item current-item">
                                <span class="progress-count">
                                    <i class="fa-regular fa-check"></i>
                                </span>
                                <span class="progress-label progress-label d-flex align-items-center justify-content-center gap-3">
                                    Tamamlandı
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div class="col-12 col-md-12 col-lg-12 col-xl-12 mt-2" id="ClinicChangeContent">
                        <input type="text" id="ClinicID" class="d-none">
                    </div>

                </div>

                <div class="row justify-content-between">
                    <div class="col-12 mt-2">
                        <h3 class="text-danger fw-bold text-center d-none" id="NoClinicErr">An error occurred</h3>

                        <div id="DoctorChangeContent" class="mb-2">
                            <h3 class="text-danger text-center fw-bold d-none" id="noDocErr">No registered doctor found in this clinic.</h3>
                            <input type="text" id="DoctorID" class="d-none">
                        </div>

                        <div id="DateLoad" class="d-none">
                            <div class="table-responsive">
                                <div class="card">
                                    <div class="d-flex justify-content-between" style="padding-top:1rem !important;padding-right:1rem !important; padding-left:1rem !important;">
                                        <button type="button" name="button" onclick="AppointmentManager.DateChange(-1)" class="btn btn-danger text-light" style="padding: 0.5rem 2rem 0.5rem 2rem;"><i class="fas fa-arrow-left"></i></button>
                                        <button type="button" name="button" onclick="AppointmentManager.DateChange(1)" class="btn btn-success text-light" style="padding: 0.5rem 2rem 0.5rem 2rem;"><i class="fas fa-arrow-right"></i></button>
                                    </div>
                                    <div class="table-responsive" style="height:30rem;">
                                        <table class="table" id="Table_Date">
                                            <thead class="sticky-top">
                                                <tr class="text-center" id="tableDates">

                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="tableTimes">
                                                    <td>
                                                        <div>

                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="PatientContent" class="d-none">
                            <div class="card border-0">
                                <div class="mt-4">
                                    <form id="AppointmentForm">
                                        <input type="hidden" name="DoctorID" value="">
                                        <input type="hidden" name="ClinicID" value="">
                                        <input type="hidden" name="Date" value="">
                                        <input type="hidden" name="BeginTime" value="">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-12 col-lg-6 col-xl-6 form-floating">
                                                <input type="text" id="PatientNumber" name="PatientNumber" class="form-control" value="" required>
                                                <label class="form-label required text-dark" for="PatientNumber">TC / Pasaport No</label>
                                            </div>

                                            <div class="col-12 col-md-12 col-lg-6 col-xl-6 form-floating">
                                                <input type="text" id="PatientFirstName" name="PatientFirstName" class="form-control" value="" required>
                                                <label class="form-label required text-dark" for="PatientFirstName">Adınız</label>
                                            </div>

                                            <div class="col-12 col-md-12 col-lg-6 col-xl-6 form-floating">
                                                <input type="text" id="PatientLastName" name="PatientLastName" class="form-control" value="" required>
                                                <label class="form-label required text-dark" for="PatientLastName">Soyadınız</label>
                                            </div>

                                            <div class="col-12 col-md-12 col-lg-6 col-xl-6 form-floating">
                                                <input type="text" id="ContactMobile" name="ContactMobile" class="form-control" value="" maxlength="13" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                                <label class="form-label required text-dark" for="ContactMobile">Cep Telefonu Numarası</label>
                                            </div>

                                            <div class="col-12 col-md-12 col-lg-6 col-xl-6 form-floating">
                                                <input type="date" id="PatientBirthday" name="PatientBirthday" class="form-control">
                                                <label class="form-label text-dark" for="PatientBirthday">Doğum Tarihiniz</label>
                                            </div>

                                            <div class="col-12 col-md-12 col-lg-6 col-xl-6 form-floating">
                                                <input type="text" id="ContactEmail" name="ContactEmail" class="form-control" value="">
                                                <label class="form-label text-dark" for="ContactEmail">E-posta Adresiniz</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="kvkk" id="defaultCheck1" required="">
                                                <label class="form-check-label" for="defaultCheck1">
                                                    <a onclick="AppointmentManager.GetDataShare();" class="link-primary custom-hover-link primary">KVKK Aydınlatma Metnini</a> Okudum, Onayladım
                                                </label>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <button class="btn btn-primary rounded-3 text-white w-100" onclick="AppointmentManager.FormSubmit();" type="button" name="button">
                                                    RANDEVU AL
                                                    <i class="fa-regular fa-arrow-right-long ms-3"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div id="OfflineDoctor" class="d-none">
                            <div class="card p-4">
                                <p class="mb-3 lead-responsive">Bu hekimden randevu almak için klinikle iletişime geçebilirsiniz.</p>
                                <div class="mb-4 d-none" id="ODClinicName">
                                    <h4 class="fw-bold">Klinik</h4>
                                    <p class="p-responsive mb-0">DentSoft Test</p>
                                </div>
                                <div class="mb-4 d-none" id="ODClinicAddress">
                                    <h4 class="fw-bold">Adres</h4>
                                    <p class="p-responsive mb-0"></p>
                                </div>
                                <div class="mb-4 d-none" id="ODClinicMail">
                                    <h4 class="fw-bold">E-posta</h4>
                                    <a href="" target="_blank" class="p-responsive text-decoration-none"></a>
                                </div>
                                <div class="mb-4 d-none" id="ODClinicPhone">
                                    <h4 class="fw-bold">İletişim</h4>
                                    <a href="" target="_blank" class="p-responsive text-decoration-none"></a>
                                </div>
                                <div class="mb-4 d-none" id="ODClinicWhatsapp">
                                    <h4 class="fw-bold">İletişim (Whatsapp)</h4>
                                    <a href="" target="_blank" class="p-responsive text-decoration-none"></a>
                                </div>
                                <div class="d-none" id="ODClinicTimes">
                                    <h4 class="fw-bold">Çalışma Saatleri</h4>
                                    <p class="p-responsive mb-0"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container d-none" id="AppointmentSummaryHtml">
            <div class="row justify-content-center mt-5">
                <div class="col-12 text-center">
                    <h3>
                        <i class="far fa-check text-primary me-3"></i>
                        Randevunuz Başarıyla Oluşturulmuştur
                    </h3>
                    <p class="lead-responsive">Detayları aşağıdan inceleyebilirsiniz</p>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="appointment-card bg-white">
                        <div class="appointment-header d-flex align-items-center">
                            <img id="SumDoctorImage" src="" alt="Doktor Avatar" class="doctor-avatar me-3">
                            <div>
                                <h4 class="mb-0" id="SumDoctorName"></h4>
                                <p class="mb-0" id="SumDoctorRole"></p>
                            </div>
                        </div>
                        <div class="appointment-body">
                            <h5 class="mb-4 fw-bolder">Randevu Detayları</h5>
                            <div class="appointment-info">
                                <div class="row mb-2">
                                    <div class="col-4"><span class="text-muted">İsim Soyisim: </span></div>
                                    <div class="col-8"><span class="fw-bold" id="SumPatientFullName"></span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4"><span class="text-muted">Klinik Adı: </span></div>
                                    <div class="col-8"><span class="fw-bold" id="SumClinicName"></span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4"><span class="text-muted">Klinik Adresi: </span></div>
                                    <div class="col-8"><span class="fw-bold" id="SumClinicAddress"></span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4"><span class="text-muted">Klinik Telefon Numarası: </span></div>
                                    <div class="col-8"><span class="fw-bold" id="SumClinicPhone"></span></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4"><span class="text-muted">PNR No: </span></div>
                                    <div class="col-8"><span class="fw-bold" id="SumPnrNo"></span></div>
                                </div>
                                <div class="row">
                                    <div class="col-4"><span class="text-muted">Randevu Tarihi: </span></div>
                                    <div class="col-8"><span class="fw-bold text-center" id="SumDate"></span></div>
                                </div>
                            </div>

                            <button id="AppointmentCancelBtn" class="btn btn-outline-danger w-100">Randevuyu İptal Et</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="copyright text-center mb-4">
        <small>
            Powered by
            <a class="text-decoration-none" target="_blank" href="https://dentsoft.com.tr">DentSoft</a>
        </small>
    </div>

    <div class="modal fade" id="kvkkModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true" style="z-index: 9999!important;">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Modal Title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalContent">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
</main>