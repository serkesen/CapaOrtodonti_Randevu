<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <?php wp_head(); ?>
</head>

<body>
    <main class="d-flex flex-column justify-content-between" style="min-height: 100vh;">
        <section class="d-flex flex-column position-relative content-section pt-0">
            <h2 class="title text-primary"></h2>
            <div class="appointment-card shadow" id="AppointmentHtml">
                <div class="appointment-header d-flex align-items-center d-none" id="DoctorContent">
                    <div class="">
                        <img id="DoctorImage" src="<?php echo esc_url(plugins_url('assets/img/DefaultAvatar.png', dirname(__FILE__))); ?>" alt="Doktor Avatar" class="doctor-avatar me-3">
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

                    <!-- Diğer içerik buraya gelecek -->
                    <?php include plugin_dir_path(__FILE__) . 'form-content.php'; ?>
                </div>
            </div>

            <!-- Randevu özeti -->
            <?php include plugin_dir_path(__FILE__) . 'appointment-summary.php'; ?>
        </section>
    </main>
    <?php wp_footer(); ?>
</body>

</html>

<script>
    jQuery(document).ready(function($) {
        // Form adımları arası geçiş
        $('.next-step').on('click', function() {
            var currentStep = $(this).closest('.step-content');
            var nextStep = currentStep.next('.step-content');

            if (validateStep(currentStep)) {
                currentStep.addClass('d-none');
                nextStep.removeClass('d-none');

                var stepNumber = nextStep.attr('id').replace('step', '').replace('-content', '');
                updateStepIndicator(stepNumber);
            }
        });

        $('.prev-step').on('click', function() {
            var currentStep = $(this).closest('.step-content');
            var prevStep = currentStep.prev('.step-content');

            currentStep.addClass('d-none');
            prevStep.removeClass('d-none');

            var stepNumber = prevStep.attr('id').replace('step', '').replace('-content', '');
            updateStepIndicator(stepNumber);
        });

        // Form gönderimi
        $('#appointmentForm').on('submit', function(e) {
            e.preventDefault();

            var formData = $(this).serialize();

            $.ajax({
                url: dentsoft_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dentsoft_save_appointment',
                    nonce: dentsoft_ajax.nonce,
                    formData: formData
                },
                success: function(response) {
                    if (response.success) {
                        alert('Randevunuz başarıyla oluşturuldu.');
                        window.location.reload();
                    } else {
                        alert('Bir hata oluştu: ' + response.data);
                    }
                },
                error: function() {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                }
            });
        });

        // Yardımcı fonksiyonlar
        function validateStep(step) {
            var isValid = true;
            step.find('input[required], select[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });
            return isValid;
        }

        function updateStepIndicator(stepNumber) {
            $('.step-wizard-item').removeClass('current-item');
            $('#step-' + stepNumber).addClass('current-item');
        }
    });
</script>

<style>
    .dentsoft-appointment-form {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .appointment-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    .step-wizard-list {
        list-style: none;
        padding: 0;
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }

    .step-wizard-item {
        flex: 1;
        text-align: center;
        position: relative;
    }

    .progress-count {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f0f0f0;
        margin: 0 auto 10px;
    }

    .current-item .progress-count {
        background: #007bff;
        color: #fff;
    }

    .progress-label {
        font-size: 14px;
        color: #666;
    }

    .current-item .progress-label {
        color: #007bff;
        font-weight: bold;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .doctor-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
    }
</style>