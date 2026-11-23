<?php include __DIR__ . '/databases/patient-details-process.php'; ?>

<main class="main-content">

    <div class="page-heading patient-details-heading">

        <div>
            <h1>Patient Details</h1>
            <p>
                Administrative view of patient information
                and medical history.
            </p>
        </div>

        <a href="patients.php" class="cancel-btn">
            Back to Patients
        </a>

    </div>

    <div class="patient-details-grid">

        <!-- PATIENT PERSONAL INFORMATION -->
        <section class="content-card patient-profile-card">

            <h2>
                <?= patientText(
                    $patient['first_name'] . ' ' .
                    $patient['last_name']
                ) ?>
            </h2>

            <p class="patient-reference">
                Patient #<?= (int)$patient['patient_id'] ?>
            </p>

            <div class="patient-information">

                <div class="patient-info-field">
                    <span>National ID Number</span>
                    <p><?= patientText($patient['id_number']) ?></p>
                </div>

                <div class="patient-info-field">
                    <span>Date of Birth</span>
                    <p>
                        <?= patientDate($patient['date_of_birth']) ?>
                    </p>
                </div>

                <div class="patient-info-field">
                    <span>Gender</span>
                    <p><?= patientText($patient['gender']) ?></p>
                </div>

                <div class="patient-info-field">
                    <span>Email</span>
                    <p><?= patientText($patient['email']) ?></p>
                </div>

                <div class="patient-info-field">
                    <span>Contact Number</span>
                    <p>
                        <?= patientText($patient['contact_number']) ?>
                    </p>
                </div>

                <div class="patient-info-field">
                    <span>Address</span>
                    <p><?= patientText($patient['address']) ?></p>
                </div>

                <div class="patient-info-field">
                    <span>Date Registered</span>
                    <p><?= patientDate($patient['created_at']) ?></p>
                </div>

            </div>

        </section>

        <!-- PATIENT MEDICAL HISTORY -->
        <section class="content-card patient-history-card">

            <h2>Medical History</h2>

            <div class="history-section">

                <h3>Consultation History</h3>

                <?php if (!empty($consultations)): ?>

                    <div class="medical-timeline">

                        <?php foreach ($consultations as $consultation): ?>

                            <article class="medical-timeline-item">

                                <div class="medical-entry-header">

                                    <strong>
                                        <?= patientDate(
                                            $consultation['consultation_date'],
                                            true
                                        ) ?>
                                    </strong>

                                    <span class="status-badge <?= $consultation['consultation_status'] === 'Completed' ? 'completed' : 'in-progress' ?>">
                                        <?= patientText(
                                            $consultation['consultation_status']
                                        ) ?>
                                    </span>

                                </div>

                                <p class="medical-staff-name">
                                    Attending:
                                    <?= patientText(
                                        $consultation['staff_first_name'] .
                                        ' ' .
                                        $consultation['staff_last_name']
                                    ) ?>
                                    (<?= patientText(
                                        $consultation['professional_role']
                                    ) ?>)
                                </p>

                                <div class="medical-entry-details">

                                    <p>
                                        <strong>Symptoms:</strong>
                                        <?= patientText(
                                            $consultation['symptoms']
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Diagnosis:</strong>
                                        <?= patientText(
                                            $consultation['diagnosis']
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Treatment Plan:</strong>
                                        <?= patientText(
                                            $consultation['treatment_plan']
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Additional Notes:</strong>
                                        <?= patientText(
                                            $consultation['notes']
                                        ) ?>
                                    </p>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p class="history-empty-message">
                        No consultation history has been recorded
                        for this patient.
                    </p>

                <?php endif; ?>

            </div>

            <div class="history-section">

                <h3>Nursing Observations</h3>

                <?php if (!empty($observations)): ?>

                    <div class="medical-timeline">

                        <?php foreach ($observations as $observation): ?>

                            <article class="medical-timeline-item">

                                <div class="medical-entry-header">
                                    <strong>
                                        <?= patientDate(
                                            $observation['observation_date'],
                                            true
                                        ) ?>
                                    </strong>
                                </div>

                                <p class="medical-staff-name">
                                    Recorded by:
                                    <?= patientText(
                                        $observation['nurse_first_name'] .
                                        ' ' .
                                        $observation['nurse_last_name']
                                    ) ?>
                                </p>

                                <div class="medical-entry-details">

                                    <p>
                                        <strong>Observation:</strong>
                                        <?= patientText(
                                            $observation['observation_type']
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Value:</strong>
                                        <?= patientText(
                                            $observation['observation_value']
                                        ) ?>
                                    </p>

                                    <p>
                                        <strong>Notes:</strong>
                                        <?= patientText(
                                            $observation['notes']
                                        ) ?>
                                    </p>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p class="history-empty-message">
                        No nursing observations have been
                        recorded for this patient.
                    </p>

                <?php endif; ?>

            </div>

        </section>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
