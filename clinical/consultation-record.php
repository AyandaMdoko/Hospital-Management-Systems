<?php
/** Read-only single consultation, independently checked and audited. */
require_once __DIR__ . '/includes/clinical-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
$page_title = 'Consultation Record';

function crH($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function crDate($v): string
{
    return $v ? crH(date('d M Y, h:i A', strtotime((string)$v))) : 'Not recorded';
}

$consultationId = filter_input(INPUT_GET, 'consultation_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$staffId = filter_var($_SESSION['staff_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$consultationId) {
    http_response_code(400);
    exit('Choose a consultation from the history list.');
}
if (!$staffId || !$userId) {
    http_response_code(403);
    exit('An active clinical account is required.');
}

// Access gate applies even to manually entered IDs; notes are retrieved only
// after a confirmed clinical relationship. Excludes in-progress drafts.
$stmt = $pdo->prepare("SELECT c.consultation_id, c.patient_id, c.visit_id,
    c.consultation_date, c.consultation_status, c.symptoms, c.diagnosis,
    c.treatment_plan, c.notes,
    p.first_name AS patient_first, p.last_name AS patient_last,
    s.first_name AS clinician_first, s.last_name AS clinician_last, s.professional_role
    FROM consultations c JOIN patients p ON p.patient_id = c.patient_id
    JOIN staff s ON s.staff_id = c.staff_id
    WHERE c.consultation_id = ? AND c.consultation_status = 'Completed'
      AND (EXISTS (SELECT 1 FROM patient_visits av
                   WHERE av.patient_id = c.patient_id AND av.visit_status IN ('Waiting', 'In Consultation'))
           OR EXISTS (SELECT 1 FROM consultations own
                      WHERE own.patient_id = c.patient_id AND own.staff_id = ?))
    LIMIT 1");
$stmt->execute([(int)$consultationId, (int)$staffId]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$consultation) {
    http_response_code(404);
    exit('Consultation not found or no authorized clinical relationship.');
}

try {
    $audit = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)');
    $audit->execute([(int)$userId, 'Viewed completed clinical consultation', 'Consultation', (int)$consultationId]);
} catch (Throwable $e) {
    error_log('Consultation record audit failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Unable to log consultation access. Please contact the administrator.');
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="clinical-main ch-page">
  <div class="clinical-page-heading">
    <div>
      <div class="clinical-eyebrow">CLINICAL CONSULTATION HISTORY</div>
      <h1>Consultation #<?= (int)$consultation['consultation_id'] ?></h1>
      <p>Completed record · Read-only</p>
    </div>
    <a class="ch-btn ch-secondary" href="/clinical/consultation-history.php">Back to Consultation History</a>
  </div>
  <section class="clinical-card ch-card">
    <div class="clinical-card-heading">
      <div>
        <h2><?= crH($consultation['patient_first'] . ' ' . $consultation['patient_last']) ?></h2>
        <p>Patient #<?= (int)$consultation['patient_id'] ?></p>
      </div>
      <span class="ch-status">Completed</span>
    </div>
    <dl class="ch-facts">
      <div>
        <dt>Consultation ID</dt>
        <dd>#<?= (int)$consultation['consultation_id'] ?></dd>
      </div>
      <div>
        <dt>Visit ID</dt>
        <dd><?= $consultation['visit_id'] ? '#' . (int)$consultation['visit_id'] : 'Legacy record (no visit link)' ?></dd>
      </div>
      <div>
        <dt>Consultation date</dt>
        <dd><?= crDate($consultation['consultation_date']) ?></dd>
      </div>
      <div>
        <dt>Attending professional</dt>
        <dd><?= crH($consultation['clinician_first'] . ' ' . $consultation['clinician_last']) ?> (<?= crH($consultation['professional_role']) ?>)</dd>
      </div>
    </dl>
  </section>
  <section class="clinical-card ch-card">
    <div class="clinical-card-heading">
      <div>
        <h2>Clinical Documentation</h2>
        <p>Original completed entries are displayed without editing controls.</p>
      </div>
    </div>
    <div class="ch-notes">
      <div>
        <h3>Symptoms / Assessment</h3>
        <p><?= nl2br(crH($consultation['symptoms'] ?: 'Not recorded')) ?></p>
      </div>
      <div>
        <h3>Diagnosis / Clinical Impression</h3>
        <p><?= nl2br(crH($consultation['diagnosis'] ?: 'Not recorded')) ?></p>
      </div>
      <div>
        <h3>Treatment / Care Plan</h3>
        <p><?= nl2br(crH($consultation['treatment_plan'] ?: 'Not recorded')) ?></p>
      </div>
      <div>
        <h3>Additional Clinical Notes</h3>
        <p><?= nl2br(crH($consultation['notes'] ?: 'Not recorded')) ?></p>
      </div>
    </div>
    <div class="ch-actions">
      <a class="ch-btn ch-secondary" href="/clinical/patient-details.php?patient_id=<?= (int)$consultation['patient_id'] ?>">View Full Patient Medical History</a>
    </div>
  </section>
  <p class="ch-footnote">Access to this consultation is audited. Corrections to completed clinical records require a separate, traceable amendment process.</p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
