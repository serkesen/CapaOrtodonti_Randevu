<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="container d-none" id="AppointmentSummaryHtml">
    <div class="row justify-content-center mt-5">
        <div class="col-12 text-center">
            <h3>
                <i class="far fa-check text-primary me-3"></i>
                Randevunuz Başarıyla Oluşturulmuştur
            </h3>
            <div class="card mt-4">
                <div class="card-header" id="AppointmentHeader">
                    <div class="d-flex align-items-center gap-3">
                        <img id="SumDoctorImage" src="<?php echo esc_url(plugins_url('assets/img/DefaultAvatar.png', dirname(__FILE__))); ?>" alt="Doktor Avatar" class="doctor-avatar me-3">
                        <div>
                            <span class="h4 fw-bolder mb-0" id="SumDoctorName"></span>
                            <br>
                            <p class="mb-0" id="SumDoctorRole"></p>
                        </div>
                    </div>
                    <p class="mb-0 text-center" id="ErrText"></p>
                </div>
                <div class="card-body" id="AppointmentBody">
                    <div class="row">
                        <div class="col-12">
                            <h4 class="fw-bold">Hasta</h4>
                            <p class="p-responsive mb-0" id="SumPatientFullName"></p>
                        </div>
                        <div class="col-12 mt-4">
                            <h4 class="fw-bold">Klinik</h4>
                            <p class="p-responsive mb-0" id="SumClinicName"></p>
                            <p class="p-responsive mb-0" id="SumClinicAddress"></p>
                            <p class="p-responsive mb-0" id="SumClinicPhone"></p>
                        </div>
                        <div class="col-12 mt-4">
                            <h4 class="fw-bold">PNR No</h4>
                            <p class="p-responsive mb-0" id="SumPnrNo"></p>
                        </div>
                        <div class="col-12 mt-4">
                            <h4 class="fw-bold">Randevu Tarihi</h4>
                            <p class="p-responsive mb-0" id="SumDate"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>