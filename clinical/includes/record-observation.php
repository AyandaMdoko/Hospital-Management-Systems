<?php
/**
 * Record an observation for a currently active clinic visit.
 * Professional Nurse only. Observation is patient-scoped in the existing schema.
 * Never trusts patient_id, staff_id, or timestamps supplied by the browser.
 */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
$page_title = 'Record Clinical Observation';

if (($_SESSION['role'] ?? null) !== 'Nurse') {
    http_response_code(403);
    exit('Only authorized Professional Nurses can record clinical observations.');
}
$staffId = filter_var($_SESSION['staff_id'] ?? null, FILTER_VALIDATE_INT);
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
$visitId = filter_input(INPUT_GET, 'visit_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$staffId || !$userId) { http_response_code(403); exit('An active linked Nurse account is required.'); }
if (!is_int($visitId) || $visitId < 1) {
    http_response_code(400); exit('Choose an active patient visit from Clinical Observations.');
}

function roH($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function roAudit(PDO $db, int $uid, string $action, string $type, int $id): void {
    $s = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)');
    $s->execute([$uid, $action, $type, $id]);
}

// These examples are format guides, not clinical interpretation or diagnostic ranges.
$types = [
    'Blood Pressure' => ['unit' => 'mmHg', 'example' => '120/80', 'pattern' => '/\A[0-9]{1,3}\s*\/\s*[0-9]{1,3}\z/D'],
    'Temperature' => ['unit' => '°C', 'example' => '36.8', 'pattern' => '/\A[0-9]{1,2}(?:\.[0-9]{1,2})?\z/D'],
    'Pulse Rate' => ['unit' => 'bpm', 'example' => '74', 'pattern' => '/\A[0-9]{1,3}\z/D'],
    'Respiratory Rate' => ['unit' => 'breaths/min', 'example' => '18', 'pattern' => '/\A[0-9]{1,3}\z/D'],
    'Oxygen Saturation' => ['unit' => '%', 'example' => '98', 'pattern' => '/\A[0-9]{1,3}\z/D'],
    'Weight' => ['unit' => 'kg', 'example' => '70.5', 'pattern' => '/\A[0-9]{1,3}(?:\.[0-9]{1,2})?\z/D'],
    'Height' => ['unit' => 'cm', 'example' => '172', 'pattern' => '/\A[0-9]{1,3}(?:\.[0-9]{1,2})?\z/D'],
    'Blood Glucose' => ['unit' => 'mmol/L', 'example' => '5.4', 'pattern' => '/\A[0-9]{1,3}(?:\.[0-9]{1,2})?\z/D'],
];

if (!isset($_SESSION['clinical_observation_csrf']) || !is_string($_SESSION['clinical_observation_csrf'])) {
    $_SESSION['clinical_observation_csrf'] = bin2hex(random_bytes(32));
}
$err = '';
$selectedType = '';
$enteredValue = '';
$enteredNotes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    $selectedType = $_POST['observation_type'] ?? '';
    $enteredValue = $_POST['observation_value'] ?? '';
    $enteredNotes = $_POST['notes'] ?? '';
    try {
        if (!is_string($token) || !hash_equals($_SESSION['clinical_observation_csrf'], $token)) {
            throw new DomainException('Your form token has expired. Refresh and try again.');
        }
        if (!is_string($selectedType) || !array_key_exists($selectedType, $types)) {
            throw new DomainException('Select a valid observation type.');
        }
        if (!is_string($enteredValue) || !is_string($enteredNotes)) {
            throw new DomainException('Invalid observation details.');
        }
        $enteredValue = trim($enteredValue);
        $enteredNotes = trim($enteredNotes);
        if (strlen($enteredValue) > 30 || !preg_match($types[$selectedType]['pattern'], $enteredValue)) {
            throw new DomainException('Enter the measurement in the format shown for the selected observation type. Do not type the unit.');
        }
        if (strlen($enteredNotes) > 2000) {
            throw new DomainException('Notes must be 2,000 bytes or fewer.');
        }

        // Normalize spacing in blood pressure; the stored value includes an
        // explicit unit for safe interpretation in historical records.
        if ($selectedType === 'Blood Pressure') {
            $enteredValue = preg_replace('/\s+/', '', $enteredValue);
        }
        $storedValue = $enteredValue . ' ' . $types[$selectedType]['unit'];

        $pdo->beginTransaction();
        // Lock the visit; do not allow observations for cancelled/completed
        // visits, even if a clinician submitted a stale form.
        $s = $pdo->prepare('SELECT patient_id, visit_status FROM patient_visits WHERE visit_id = ? FOR UPDATE');
        $s->execute([$visitId]);
        $v = $s->fetch(PDO::FETCH_ASSOC);
        if (!$v || !in_array($v['visit_status'], ['Waiting', 'In Consultation'], true)) {
            throw new DomainException('This patient visit is no longer active. No observation was recorded.');
        }
        $patientId = (int)$v['patient_id'];

        $s = $pdo->prepare('INSERT INTO nursing_observations
            (patient_id, staff_id, observation_date, observation_type, observation_value, notes)
            VALUES (?, ?, NOW(), ?, ?, ?)');
        $s->execute([$patientId, (int)$staffId, $selectedType, $storedValue, $enteredNotes]);
        $observationId = (int)$pdo->lastInsertId();
        roAudit($pdo, (int)$userId, 'Recorded clinical observation during visit #' . $visitId, 'Nursing Observation', $observationId);
        $pdo->commit();

        // One-use token + PRG prevents an ordinary refresh from saving twice.
        $_SESSION['clinical_observation_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['clinical_observation_flash'] = 'Observation #' . $observationId . ' saved for Patient #' . $patientId . '.';
        header('Location: /clinical/observations.php?patient_id=' . $patientId, true, 303);
        exit;
    } catch (DomainException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $err = $e->getMessage();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('Recording clinical observation failed: ' . $e->getMessage());
        $err = 'Unable to record the observation. No changes were saved. Please contact the administrator if this continues.';
    }
}

$s = $pdo->prepare("SELECT v.visit_id, v.patient_id, v.check_in_at, v.visit_status,
    p.first_name, p.last_name, p.date_of_birth FROM patient_visits v
    JOIN patients p ON p.patient_id = v.patient_id
    WHERE v.visit_id = ? AND v.visit_status IN ('Waiting','In Consultation') LIMIT 1");
$s->execute([$visitId]);
$visit = $s->fetch(PDO::FETCH_ASSOC);
if (!$visit) {
    http_response_code(403);
    exit('This visit is not active or cannot receive new observations.');
}
try {
    roAudit($pdo, (int)$userId, 'Viewed clinical observation entry for visit', 'Patient', (int)$visit['patient_id']);
} catch (Throwable $e) {
    error_log('Clinical observation entry audit failed: ' . $e->getMessage());
    http_response_code(503); exit('Unable to record clinical record access.');
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="clinical-main ob-page">
  <div class="clinical-page-heading"><div><div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
    <h1>Record Clinical Observation</h1>
    <p>Professional Nurse entry · Visit #<?= (int)$visitId ?></p></div>
    <a class="ob-btn ob-btn-outline" href="/clinical/observations.php?patient_id=<?= (int)$visit['patient_id'] ?>">Back to Observations</a>
  </div>
  <?php if ($err !== ''): ?><div class="ob-alert ob-alert-error" role="alert"><?= roH($err) ?></div><?php endif; ?>
  <section class="clinical-card ob-card">
    <div class="clinical-card-heading"><div><h2><?= roH($visit['first_name'] . ' ' . $visit['last_name']) ?></h2>
      <p>Patient #<?= (int)$visit['patient_id'] ?> · Visit #<?= (int)$visitId ?> · <?= roH($visit['visit_status']) ?></p></div></div>
    <p class="ob-muted">Checked in <?= roH(date('d M Y, h:i A', strtotime($visit['check_in_at']))) ?>. The system records the observation time automatically.</p>
    <form action="/clinical/record-observation.php?visit_id=<?= (int)$visitId ?>" method="post" class="ob-form" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= roH($_SESSION['clinical_observation_csrf']) ?>">
      <div class="ob-form-grid">
        <div><label for="ob-type">Observation type <span aria-hidden="true">*</span></label>
          <select id="ob-type" name="observation_type" required>
            <option value="">Choose an observation</option>
            <?php foreach ($types as $name => $meta): ?>
              <option value="<?= roH($name) ?>" <?= $selectedType === $name ? 'selected' : '' ?>><?= roH($name) ?> (<?= roH($meta['unit']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div><label for="ob-value">Measured value (without unit) <span aria-hidden="true">*</span></label>
          <input id="ob-value" name="observation_value" type="text" maxlength="30" required
                 value="<?= roH($enteredValue) ?>" placeholder="e.g. 120/80 or 36.8" aria-describedby="ob-format">
        </div>
      </div>
      <p id="ob-format" class="ob-muted">Examples: blood pressure 120/80; temperature 36.8; pulse 74; oxygen saturation 98. Units are appended automatically. Enter only values measured, not estimates.</p>
      <div class="ob-notes-area"><label for="ob-notes">Clinical observation notes (optional)</label>
        <textarea id="ob-notes" name="notes" rows="4" maxlength="2000" placeholder="Optional factual observations; do not enter prescriptions."><?= roH($enteredNotes) ?></textarea>
      </div>
      <div class="ob-actions-row"><button class="ob-btn ob-btn-primary" type="submit">Save Observation</button>
        <a class="ob-btn ob-btn-outline" href="/clinical/observations.php?patient_id=<?= (int)$visit['patient_id'] ?>">Cancel</a></div>
    </form>
  </section>
  <p class="ob-footnote">This form saves an independent patient observation. It does not start, finish, or change a consultation or visit. Completed entries are read-only; corrections require a traceable amendment workflow.</p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
