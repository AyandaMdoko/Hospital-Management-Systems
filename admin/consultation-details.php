<?php include_once __DIR__ . '/databases/consultation-details-process.php'; ?>

<main class="main-content">

    <div class="page-heading consultation-detail-heading">

        <div>
            <h1>Consultation Details</h1>
            <p>
                View clinical information for consultation
                #<?= (int)$consultation['consultation_id'] ?>
            </p>
        </div>

        <a href="consultations.php" class="cancel-btn">
            Back to Consultations
        </a>

    </div>

    <div class="consultation-detail-grid">

        <!-- CONSULTATION INFORMATION -->
        <section class="content-card">

            <h2 class="consultation-section-title">
                Consultation Information
            </h2>

            <div class="consultation-info-field">
                <span>Consultation ID</span>
                <strong>
                    #<?= (int)$consultation['consultation_id'] ?>
                </strong>
            </div>

            <div class="consultation-info-field">
                <span>Patient</span>

                <strong>
                    <?= escapeConsultationDetail(
                        $consultation['patient_first_name'] .
                        ' ' .
                        $consultation['patient_last_name']
                    ) ?>
                </strong>

                <a
                    href="patient-details.php?id=<?= (int)$consultation['patient_id'] ?>"
                    class="consultation-patient-link"
                >
                    View Patient Details
                </a>
            </div>

            <div class="consultation-info-field">
                <span>Gender</span>
                <strong>
                    <?= escapeConsultationDetail(
                        $consultation['gender']
                    ) ?>
                </strong>
            </div>

            <div class="consultation-info-field">
                <span>Consultation Date</span>
                <strong>
                    <?= formatDetailDate(
                        $consultation['consultation_date']
                    ) ?>
                </strong>
            </div>

            <div class="consultation-info-field">
                <span>Attending Healthcare Professional</span>

                <strong>
                    <?= escapeConsultationDetail(
                        $consultation['staff_first_name'] .
                        ' ' .
                        $consultation['staff_last_name']
                    ) ?>
                </strong>
            </div>

            <div class="consultation-info-field">
                <span>Professional Role</span>
                <strong>
                    <?= escapeConsultationDetail(
                        $consultation['professional_role']
                    ) ?>
                </strong>
            </div>

            <div class="consultation-info-field">
                <span>Department</span>
                <strong>
                    <?= escapeConsultationDetail(
                        $consultation['department']
                    ) ?>
                </strong>
            </div>

            <div class="consultation-info-field">
                <span>Specialization</span>
                <strong>
                    <?= escapeConsultationDetail(
                        $consultation['specialization']
                    ) ?>
                </strong>
            </div>

            <div class="consultation-info-field">
                <span>Consultation Status</span>

                <span class="status-badge <?= $statusClass ?>">
                    <?= escapeConsultationDetail(
                        $consultation['consultation_status']
                    ) ?>
                </span>
            </div>

        </section>

        <!-- CLINICAL RECORD -->
        <section class="content-card">

            <h2 class="consultation-section-title">
                Clinical Record
            </h2>

            <div class="clinical-record-section">
                <h3>Symptoms</h3>

                <p>
                    <?= escapeConsultationDetail(
                        $consultation['symptoms']
                    ) ?>
                </p>
            </div>

            <div class="clinical-record-section">
                <h3>Diagnosis</h3>

                <p>
                    <?= escapeConsultationDetail(
                        $consultation['diagnosis']
                    ) ?>
                </p>
            </div>

            <div class="clinical-record-section">
                <h3>Treatment Plan</h3>

                <p>
                    <?= escapeConsultationDetail(
                        $consultation['treatment_plan']
                    ) ?>
                </p>
            </div>

            <div class="clinical-record-section">
                <h3>Additional Notes</h3>

                <p>
                    <?= escapeConsultationDetail(
                        $consultation['notes']
                    ) ?>
                </p>
            </div>

        </section>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
