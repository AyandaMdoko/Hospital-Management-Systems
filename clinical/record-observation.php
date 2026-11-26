<?php include __DIR__ . '/databases/record-observations-process.php'; ?>

<main class="clinical-main ob-page">
  <div class="clinical-page-heading">
    <div>
      <div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
      <h1>Record Clinical Observation</h1>
      <p>Professional Nurse entry · Visit #<?= (int)$visitId ?></p>
    </div>
    <a class="ob-btn ob-btn-outline" href="/clinical/observations.php?patient_id=<?= (int)$visit['patient_id'] ?>">Back to Observations</a>
  </div>

  <?php if ($err !== ''): ?>
    <div class="ob-alert ob-alert-error" role="alert"><?= roH($err) ?></div>
  <?php endif; ?>

  <?php if ($reviewWarnings): ?>
    <div class="ob-review-warning" role="alert">
      <strong>Measurement requires review before recording</strong>
      <?php foreach ($reviewWarnings as $warning): ?>
        <p><?= roH($warning) ?></p>
      <?php endforeach; ?>
      <p>This is a data-quality flag, not a diagnosis. Recheck the patient, measuring device, and units. A genuinely unusual value can be recorded with an explanation.</p>
    </div>
  <?php endif; ?>

  <section class="clinical-card ob-card">
    <div class="clinical-card-heading">
      <div>
        <h2><?= roH($visit['first_name'] . ' ' . $visit['last_name']) ?></h2>
        <p>Patient #<?= (int)$visit['patient_id'] ?> · Visit #<?= (int)$visitId ?> · <?= roH($visit['visit_status']) ?></p>
      </div>
    </div>

    <p class="ob-muted">Checked in <?= roH(date('d M Y, h:i A', strtotime($visit['check_in_at']))) ?>. The system records the observation time automatically.</p>

    <form action="/clinical/record-observation.php?visit_id=<?= (int)$visitId ?>" method="post" class="ob-form" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= roH($_SESSION['clinical_observation_csrf']) ?>">

      <div class="ob-form-grid">
        <div>
          <label for="ob-type">Observation type <span aria-hidden="true">*</span></label>
          <select id="ob-type" name="observation_type" required>
            <option value="">Choose an observation</option>
            <?php foreach ($types as $name => $meta): ?>
              <option value="<?= roH($name) ?>" <?= $selectedType === $name ? 'selected' : '' ?>><?= roH($name) ?> (<?= roH($meta['unit']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="ob-value">Measured value (without unit) <span aria-hidden="true">*</span></label>
          <input
            id="ob-value"
            name="observation_value"
            type="text"
            maxlength="30"
            required
            value="<?= roH($enteredValue) ?>"
            placeholder="e.g. 120/80 or 36.8"
            aria-describedby="ob-format"
          >
        </div>
      </div>

      <p id="ob-format" class="ob-muted">Examples: blood pressure 120/80; temperature 36.8; pulse 74; oxygen saturation 98; glucose 5.4 (mmol/L). Units are appended automatically. The server rejects invalid formats and asks for confirmation of unusual values. Enter only actual measurements.</p>

      <div class="ob-notes-area">
        <label for="ob-notes">Clinical observation notes <?= $reviewWarnings ? '(required for unusual readings)' : '(optional)' ?></label>
        <textarea id="ob-notes" name="notes" rows="4" maxlength="2000" placeholder="Optional factual observations; do not enter prescriptions."><?= roH($enteredNotes) ?></textarea>
      </div>

      <?php if ($reviewWarnings): ?>
        <div class="ob-confirm-review">
          <label for="ob-confirm-unusual">
            <input id="ob-confirm-unusual" type="checkbox" name="confirm_unusual" value="1" <?= $reviewConfirmed ? 'checked' : '' ?>>
            I have rechecked this unusually high/low measurement and its unit, and documented the verification in the notes.
          </label>
        </div>
      <?php endif; ?>

      <div class="ob-actions-row">
        <button class="ob-btn ob-btn-primary" type="submit">Save Observation</button>
        <a class="ob-btn ob-btn-outline" href="/clinical/observations.php?patient_id=<?= (int)$visit['patient_id'] ?>">Cancel</a>
      </div>
    </form>
  </section>

  <p class="ob-footnote">This form saves an independent patient observation. It does not start, finish, or change a consultation or visit. Review warnings are not diagnostic thresholds. Completed entries are read-only; corrections require a traceable amendment workflow.</p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
