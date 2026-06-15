<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
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
                                        <div></div>
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