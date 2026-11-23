<?php require_once __DIR__ . '/databases/patient-details-process.php'; ?>

<main class="main-content">

    <!-- PAGE HEADING -->

    <div class="page-heading">

        <div>
            <h1>Patient Details</h1>

            <p>
                View registered patient information.
            </p>
        </div></br>

        <a href="patients.php" class="cancel-btn">
            Back to Patients
        </a>

    </div>


    <?php if ($editSuccess): ?>

        <div class="form-message success-message">
            <?= htmlspecialchars(
                $editSuccess,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

    <?php endif; ?>


    <!-- PATIENT SUMMARY -->

    <section class="content-card">

        <div class="card-heading">

            <div>
                <h3><?= escapeDetails($fullName) ?></h3>

                <p class="patient-detail-subtitle">
                    Patient ID:
                    #<?= (int)$patient['patient_id'] ?>
                </p>
            </div>

        </div>

        <!-- PERSONAL INFORMATION -->

        <h3 class="patient-detail-section-title">
            Personal Information
        </h3>

        <div class="patient-details-grid">

            <div class="patient-detail-item">
                <label>First Name</label>
                <p>
                    <?= displayPatientValue(
                        $patient['first_name']
                    ) ?>
                </p>
            </div>

            <div class="patient-detail-item">
                <label>Last Name</label>
                <p>
                    <?= displayPatientValue(
                        $patient['last_name']
                    ) ?>
                </p>
            </div>

            <div class="patient-detail-item">
                <label>Date of Birth</label>
                <p>
                    <?= escapeDetails($dateOfBirth) ?>
                </p>
            </div>

            <div class="patient-detail-item">
                <label>Gender</label>
                <p>
                    <?= displayPatientValue(
                        $patient['gender']
                    ) ?>
                </p>
            </div>

            <div class="patient-detail-item">
                <label>Race / Population Group</label>
                <p>
                    <?= displayPatientValue(
                        $patient['race']
                    ) ?>
                </p>
            </div>

        </div>

        <!-- IDENTIFICATION INFORMATION -->

        <h3 class="patient-detail-section-title">
            Identification Information
        </h3>

        <div class="patient-details-grid">

            <div class="patient-detail-item">
                <label>Citizenship Status</label>
                <p>
                    <?= displayPatientValue(
                        $patient['citizenship_status']
                    ) ?>
                </p>
            </div>

            <div class="patient-detail-item">
                <label>Identification Document</label>
                <p>
                    <?= displayPatientValue(
                        $patient['document_type']
                    ) ?>
                </p>
            </div>

            <?php if (
                $patient['document_type'] === 'South African ID'
            ): ?>

                <div class="patient-detail-item">
                    <label>South African ID Number</label>
                    <p>
                        <?= maskPatientDocument(
                            $patient['id_number']
                        ) ?>
                    </p>
                </div>

            <?php elseif (
                $patient['document_type'] === 'Passport'
            ): ?>

                <div class="patient-detail-item">
                    <label>Passport Number</label>
                    <p>
                        <?= maskPatientDocument(
                            $patient['passport_number']
                        ) ?>
                    </p>
                </div>

                <div class="patient-detail-item">
                    <label>Passport Issuing Country</label>
                    <p>
                        <?= displayPatientValue(
                            $patient['passport_issuing_country']
                        ) ?>
                    </p>
                </div>

            <?php else: ?>

                <div class="patient-detail-item">
                    <label>Identification Status</label>
                    <p>
                        No identification document recorded.
                    </p>
                </div>

            <?php endif; ?>

        </div>

        <!-- CONTACT INFORMATION -->

        <h3 class="patient-detail-section-title">
            Contact Information
        </h3>

        <div class="patient-details-grid">

            <div class="patient-detail-item">
                <label>Contact Number</label>
                <p>
                    <?= displayPatientValue(
                        $patient['contact_number']
                    ) ?>
                </p>
            </div>

            <div class="patient-detail-item">
                <label>Email Address</label>
                <p>
                    <?= displayPatientValue(
                        $patient['email']
                    ) ?>
                </p>
            </div>

            <div class="patient-detail-item patient-detail-wide">
                <label>Residential Address</label>
                <p>
                    <?= nl2br(displayPatientValue(
                        $patient['address']
                    )) ?>
                </p>
            </div>

        </div>

        <!-- REGISTRATION INFORMATION -->

        <h3 class="patient-detail-section-title">
            Registration Information
        </h3>

        <div class="patient-details-grid">

            <div class="patient-detail-item">
                <label>System Patient ID</label>
                <p>
                    #<?= (int)$patient['patient_id'] ?>
                </p>
            </div>

            <div class="patient-detail-item">
                <label>Date Registered</label>
                <p>
                    <?= escapeDetails($registrationDate) ?>
                </p>
            </div>

        </div>

        <div class="form-actions">
            <a href="patients.php" class="cancel-btn">
                Back to Patients
            </a>


            <a
                href="edit-patient.php?id=<?= (int)$patient['patient_id'] ?>"
                class="primary-btn"
            >
                Edit Patient
            </a>


            <a
                href="check-in.php?id=<?= (int)$patient['patient_id'] ?>"
                class="primary-btn"
            >
                Check In Patient
            </a>


        </div>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
