<?php
require_once __DIR__ . '/databases/edit-patients-process.php';
?>

<main class="main-content">
    <div class="page-heading">
        <div>
            <h1>Edit Patient Information</h1>
            <p>Update permitted personal, identity and contact information.</p>
        </div></br>
        <a href="patient-details.php?id=<?= (int) $patientId ?>" class="cancel-btn">Cancel</a>
    </div>

    <section class="content-card">
        <div class="card-heading">
            <div>
                <h3><?= escapeEditPatient($storedPatient['first_name'] . ' ' . $storedPatient['last_name']) ?></h3>
                <p class="patient-detail-subtitle">Patient ID #<?= (int) $patientId ?></p>
            </div>
        </div>

        <?php if ($generalError): ?>
            <div class="form-message error-message" role="alert">
                <?= escapeEditPatient($generalError) ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="form-message error-message" role="alert">
                <strong>Please correct the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= escapeEditPatient($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="edit-patient.php?id=<?= (int) $patientId ?>" method="POST"
              class="patient-registration-form" autocomplete="off">
            <input type="hidden" name="csrf_token"
                   value="<?= escapeEditPatient($_SESSION['patient_edit_csrf']) ?>">

            <h3 class="patient-detail-section-title">Personal Information</h3>
            <div class="registration-form-grid">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required maxlength="100"
                           value="<?= escapeEditPatient($form['first_name']) ?>">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required maxlength="100"
                           value="<?= escapeEditPatient($form['last_name']) ?>">
                </div>

                <!-- Fixed after registration. They are rendered as text, not form inputs. -->
                <div class="form-group">
                    <label>Date of Birth — Read-only</label>
                    <div class="patient-edit-readonly">
                        <?= !empty($storedPatient['date_of_birth'])
                            ? escapeEditPatient(date('d M Y', strtotime($storedPatient['date_of_birth'])))
                            : 'Not provided' ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Gender — Read-only</label>
                    <div class="patient-edit-readonly"><?= displayEditPatient($storedPatient['gender']) ?></div>
                </div>
                <div class="form-group registration-full-width">
                    <label>Race / Population Group — Read-only</label>
                    <div class="patient-edit-readonly"><?= displayEditPatient($storedPatient['race']) ?></div>
                    <small>These registration details cannot be modified from Patient Management.</small>
                </div>
            </div>

            <h3 class="patient-detail-section-title">Identification Information</h3>
            <div class="registration-form-grid">
                <div class="form-group">
                    <label for="citizenship_status">Citizenship Status *</label>
                    <select id="citizenship_status" name="citizenship_status" required>
                        <option value="">Select Citizenship Status</option>
                        <?php foreach ($citizenshipOptions as $option): ?>
                            <option value="<?= escapeEditPatient($option) ?>"
                                <?= $form['citizenship_status'] === $option ? 'selected' : '' ?>>
                                <?= escapeEditPatient($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="document_type">Identification Document *</label>
                    <select id="document_type" name="document_type" required>
                        <option value="">Select Identification Document</option>
                        <?php foreach ($documentOptions as $option): ?>
                            <option value="<?= escapeEditPatient($option) ?>"
                                <?= $form['document_type'] === $option ? 'selected' : '' ?>>
                                <?= escapeEditPatient($option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Changing document type replaces the previously recorded document.</small>
                </div>

                <div class="form-group registration-full-width" id="edit_sa_id_fields"
                     <?= $form['document_type'] === 'South African ID' ? '' : 'hidden' ?>>
                    <label for="id_number">South African ID Number</label>
                    <?php if ($storedPatient['document_type'] === 'South African ID'): ?>
                        <small>Current ID: <?= maskEditDocument($storedPatient['id_number']) ?>.
                            Leave blank to keep it, or enter a replacement ID.</small>
                    <?php endif; ?>
                    <input type="text" id="id_number" name="id_number"
                           maxlength="13" inputmode="numeric" pattern="[0-9]{13}"
                           value="<?= escapeEditPatient($form['id_number']) ?>"
                           placeholder="13 digits"
                           <?= $form['document_type'] === 'South African ID' ? '' : 'disabled' ?>>
                </div>

                <div class="form-group" id="edit_passport_number_field"
                     <?= $form['document_type'] === 'Passport' ? '' : 'hidden' ?>>
                    <label for="passport_number">Passport Number</label>
                    <?php if ($storedPatient['document_type'] === 'Passport'): ?>
                        <small>Current passport: <?= maskEditDocument($storedPatient['passport_number']) ?>.
                            Leave blank to keep it.</small>
                    <?php endif; ?>
                    <input type="text" id="passport_number" name="passport_number"
                           maxlength="50" value="<?= escapeEditPatient($form['passport_number']) ?>"
                           placeholder="Enter a new passport number if changing it"
                           <?= $form['document_type'] === 'Passport' ? '' : 'disabled' ?>>
                </div>

                <div class="form-group" id="edit_passport_country_field"
                     <?= $form['document_type'] === 'Passport' ? '' : 'hidden' ?>>
                    <label for="passport_issuing_country">Passport Issuing Country</label>
                    <input type="text" id="passport_issuing_country" name="passport_issuing_country"
                           maxlength="100"
                           value="<?= escapeEditPatient($form['passport_issuing_country']) ?>"
                           <?= $form['document_type'] === 'Passport' ? '' : 'disabled' ?>>
                </div>

                <div class="form-group registration-full-width" id="edit_no_document_note"
                     <?= $form['document_type'] === 'No document' ? '' : 'hidden' ?>>
                    <p class="registration-help">No identification document will be stored.
                        The patient's existing system Patient ID will remain unchanged.</p>
                </div>
            </div>

            <h3 class="patient-detail-section-title">Contact Information</h3>
            <div class="registration-form-grid">
                
            <div class="form-group">

                <label for="contact_number">
                    Contact Number
                </label>

                <input
                    type="tel" id="contact_number" name="contact_number"
                    maxlength="20" pattern="^\+?[0-9\s\-()]{7,20}$" 
                    value="<?= htmlspecialchars(
                        (string)($form['contact_number'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

            </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" maxlength="150"
                           value="<?= escapeEditPatient($form['email']) ?>">
                </div>
                <div class="form-group registration-full-width">
                    <label for="address">Residential Address</label>
                    <textarea id="address" name="address" rows="4" maxlength="1000"><?= escapeEditPatient($form['address']) ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <a href="patient-details.php?id=<?= (int) $patientId ?>" class="cancel-btn">Cancel</a>
                <button class="primary-btn" type="submit">Save Changes</button>
            </div>
        </form>
    </section>
</main>

<script >
    // JavaScript for handling document type selection and related fields in edit patient form
(function () {
    'use strict';
    const select = document.getElementById('document_type');
    const originalType = <?= json_encode((string) $storedPatient['document_type'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const hasExistingId = <?= !empty($storedPatient['id_number']) ? 'true' : 'false' ?>;
    const hasExistingPassport = <?= !empty($storedPatient['passport_number']) ? 'true' : 'false' ?>;
    const fields = {
        'South African ID': [document.getElementById('edit_sa_id_fields')],
        'Passport': [document.getElementById('edit_passport_number_field'), document.getElementById('edit_passport_country_field')],
        'No document': [document.getElementById('edit_no_document_note')]
    };

    // Function to update the visibility and requirements of fields based on the selected document type
    function updateFields() {
        const selected = select.value;
        Object.entries(fields).forEach(([type, sections]) => {
            const visible = type === selected;
            sections.forEach(section => {
                section.hidden = !visible;
                section.style.display = visible ? '' : 'none';
                section.querySelectorAll('input').forEach(input => {
                    input.disabled = !visible;
                });
            });
        });

        const id = document.getElementById('id_number');
        const passport = document.getElementById('passport_number');
        const country = document.getElementById('passport_issuing_country');
        id.required = selected === 'South African ID' &&
                      !(originalType === 'South African ID' && hasExistingId);
        passport.required = selected === 'Passport' &&
                            !(originalType === 'Passport' && hasExistingPassport);
        country.required = selected === 'Passport';
    }

    select.addEventListener('change', updateFields);
    updateFields();
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
