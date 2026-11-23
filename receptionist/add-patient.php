<?php
// Loads authentication, validation, form values, database actions and layout.
require_once __DIR__ . '/databases/add-patients-process.php';
?>

<main class="main-content">
    <div class="page-heading">
        <div>
            <h1>Register Patient</h1>
            <p>Capture patient demographic and identity information to create a patient record.</p>
        </div></br>
        <a href="patients.php" class="cancel-btn">Back to Patients</a>
    </div>

    <section class="content-card">
        <div class="card-heading">
            <h3>Patient Information</h3>
        </div>

        <!-- Display error messages if any -->
        <?php if ($generalError): ?>
            <div class="form-message error-message">
                <?= escapeRegistration($generalError) ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="form-message error-message" role="alert">
                <strong>Please correct the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escapeRegistration($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>


        <!-- Patient Registration Form -->
        <form action="add-patient.php" method="POST" class="patient-registration-form">
            <input type="hidden" name="csrf_token"
                   value="<?= escapeRegistration($_SESSION['patient_registration_csrf']) ?>">

            <div class="registration-form-grid">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name"
                           value="<?= escapeRegistration($patient['first_name']) ?>"
                           maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name"
                           value="<?= escapeRegistration($patient['last_name']) ?>"
                           maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth"
                           max="<?= date('Y-m-d') ?>"
                           value="<?= escapeRegistration($patient['date_of_birth']) ?>">
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <?php foreach ($genderOptions as $gender): ?>
                            <option value="<?= escapeRegistration($gender) ?>"
                                <?= $patient['gender'] === $gender ? 'selected' : '' ?>>
                                <?= escapeRegistration($gender) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="race">Race / Population Group (optional)</label>
                    <select id="race" name="race">
                        <option value="">Not provided</option>
                        <?php foreach ($raceOptions as $race): ?>
                            <option value="<?= escapeRegistration($race) ?>"
                                <?= $patient['race'] === $race ? 'selected' : '' ?>>
                                <?= escapeRegistration($race) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Self-reported only. Patients may decline to disclose this information.</small>
                </div>

                <div class="form-group">
                    <label for="citizenship_status">Citizenship Status *</label>
                    <select id="citizenship_status" name="citizenship_status" required>
                        <option value="">Select Citizenship Status</option>
                        <?php foreach ($citizenshipOptions as $citizenship): ?>
                            <option value="<?= escapeRegistration($citizenship) ?>"
                                <?= $patient['citizenship_status'] === $citizenship ? 'selected' : '' ?>>
                                <?= escapeRegistration($citizenship) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Citizenship and identification document type are recorded separately.</small>
                </div>

                <div class="form-group registration-full-width">
                    <label for="document_type">Identification Document *</label>
                    <select id="document_type" name="document_type" required>
                        <option value="">Select Identification Document</option>
                        <?php foreach ($documentOptions as $document): ?>
                            <option value="<?= escapeRegistration($document) ?>"
                                <?= $patient['document_type'] === $document ? 'selected' : '' ?>>
                                <?= escapeRegistration($document) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Choose the document presented, or select No document if none is available.</small>
                </div>

                <!-- Conditional: South African ID -->
                <div class="form-group registration-full-width" id="sa_id_fields"
                     <?= $patient['document_type'] === 'South African ID' ? '' : 'hidden' ?>>
                    <label for="id_number">South African ID Number *</label>
                    <input type="text" id="id_number" name="id_number"
                           value="<?= escapeRegistration($patient['id_number']) ?>"
                           inputmode="numeric" pattern="[0-9]{13}" maxlength="13"
                           autocomplete="off"
                           <?= $patient['document_type'] === 'South African ID' ? 'required' : 'disabled' ?>>
                    <small>Enter exactly 13 digits. Duplicate South African ID numbers are not permitted.</small>
                </div>

                <!-- Conditional: Passport -->
                <div class="form-group" id="passport_number_field"
                     <?= $patient['document_type'] === 'Passport' ? '' : 'hidden' ?>>
                    <label for="passport_number">Passport Number *</label>
                    <input type="text" id="passport_number" name="passport_number"
                           value="<?= escapeRegistration($patient['passport_number']) ?>"
                           maxlength="50" autocomplete="off"
                           <?= $patient['document_type'] === 'Passport' ? 'required' : 'disabled' ?>>
                </div>
                <div class="form-group" id="passport_country_field"
                     <?= $patient['document_type'] === 'Passport' ? '' : 'hidden' ?>>
                    <label for="passport_issuing_country">Passport Issuing Country *</label>
                    <input type="text" id="passport_issuing_country" name="passport_issuing_country"
                           value="<?= escapeRegistration($patient['passport_issuing_country']) ?>"
                           maxlength="100" autocomplete="off"
                           <?= $patient['document_type'] === 'Passport' ? 'required' : 'disabled' ?>>
                </div>

                <div class="form-group registration-full-width" id="no_document_note"
                     <?= $patient['document_type'] === 'No document' ? '' : 'hidden' ?>>
                    <p class="registration-help">
                        No identity document is available. A unique Patient ID will be assigned
                        automatically after registration. Check for an existing patient before registering.
                    </p>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number"
                           value="<?= escapeRegistration($patient['contact_number']) ?>"
                           maxlength="20"  pattern="^\+?[0-9\s\-()]{7,20}$" placeholder="e.g. +27 82 123 4567">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           value="<?= escapeRegistration($patient['email']) ?>"
                           maxlength="150">
                </div>

                <div class="form-group registration-full-width">
                    <label for="address">Residential Address</label>
                    <textarea id="address" name="address" rows="4" maxlength="1000"><?= escapeRegistration($patient['address']) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="patients.php" class="cancel-btn">Cancel</a>
                <button type="submit" class="primary-btn">Register Patient</button>
            </div>
        </form>
    </section>
</main>

<script src="assets/js/script.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
