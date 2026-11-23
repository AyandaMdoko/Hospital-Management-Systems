<?php
require_once __DIR__ . '/databases/check-in-process.php';
?>

<main class="main-content">
    <div class="page-heading">
        <div>
            <h1>Patient Check-In</h1>
            <p>Register an existing patient for a clinic visit without creating another patient profile.</p>
        </div>
        <a href="patients.php" class="cancel-btn">Back to Patients</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="form-message error-message" role="alert">
            <?= escapeCheckIn($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>
        <div class="form-message success-message" role="status">
            <?= escapeCheckIn($successMessage) ?>
        </div>
    <?php endif; ?>

    <section class="content-card">
        <div class="card-heading">
            <div>
                <h3><?= escapeCheckIn($patient['first_name'] . ' ' . $patient['last_name']) ?></h3>
                <p class="patient-detail-subtitle">Patient ID #<?= (int) $patient['patient_id'] ?></p>
            </div>
        </div>

        <h3 class="patient-detail-section-title">Patient Verification</h3>
        <div class="patient-details-grid">
            <div class="patient-detail-item">
                <label>Patient ID</label>
                <p>#<?= (int) $patient['patient_id'] ?></p>
            </div>
            <div class="patient-detail-item">
                <label>Identification Document</label>
                <p><?= escapeCheckIn($patient['document_type'] ?: 'Not recorded') ?></p>
            </div>
            <?php if ($patient['document_type'] === 'South African ID'): ?>
                <div class="patient-detail-item">
                    <label>SA ID (masked)</label>
                    <p><?= maskCheckInDocument($patient['id_number']) ?></p>
                </div>
            <?php elseif ($patient['document_type'] === 'Passport'): ?>
                <div class="patient-detail-item">
                    <label>Passport (masked)</label>
                    <p><?= maskCheckInDocument($patient['passport_number']) ?></p>
                </div>
                <div class="patient-detail-item">
                    <label>Issuing Country</label>
                    <p><?= escapeCheckIn($patient['passport_issuing_country'] ?: 'Not recorded') ?></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($activeVisit !== null): ?>
            <div class="form-message <?= $successMessage !== '' ? 'success-message' : 'error-message' ?>" role="status">
                <strong>Active visit #<?= (int) $activeVisit['visit_id'] ?></strong>
                — <?= escapeCheckIn($activeVisit['visit_status']) ?>
                (<?= escapeCheckIn(date('d M Y, h:i A', strtotime($activeVisit['check_in_at']))) ?>).
                A second check-in is not allowed while this visit is active.
            </div>
            <div class="form-actions">
                <a href="patient-details.php?id=<?= (int) $patientId ?>" class="cancel-btn">Patient Details</a>
                <a href="patients.php" class="primary-btn">Back to Patients</a>
            </div>
        <?php else: ?>
            <h3 class="patient-detail-section-title">Register Today's Visit</h3>
            <p class="receptionist-card-description">
                The arrival time will be recorded automatically. The patient will appear as
                <strong>Waiting</strong> until a Doctor or Nurse begins the consultation.
            </p>
            <form action="check-in.php?id=<?= (int) $patientId ?>" method="POST" class="patient-registration-form">
                <input type="hidden" name="csrf_token"
                       value="<?= escapeCheckIn($_SESSION['patient_checkin_csrf']) ?>">

                <div class="registration-form-grid">
                    <div class="form-group">
                        <label for="visit_type">Visit Category *</label>
                        <select id="visit_type" name="visit_type" required>
                            <?php foreach ($visitTypes as $visitType): ?>
                                <option value="<?= escapeCheckIn($visitType) ?>"
                                    <?= $selectedVisitType === $visitType ? 'selected' : '' ?>>
                                    <?= escapeCheckIn($visitType) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="patients.php" class="cancel-btn">Cancel</a>
                    <button type="submit" class="primary-btn">Check In Patient</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
