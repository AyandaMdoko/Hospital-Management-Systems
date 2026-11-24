<?php
/** Clinical patient history: read-only, patient-scoped access gate, fail-closed audit. */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
$page_title = 'Patient Medical History';
function phH($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function phDate($v): string { return $v ? phH(date('d M Y, h:i A', strtotime($v))) : 'Not recorded'; }
$patientId = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$staffId = filter_var($_SESSION['staff_id'] ?? null, FILTER_VALIDATE_INT);
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
if (!$patientId) { http_response_code(400); exit('Choose a patient from Medical Records.'); }
if (!$staffId || !$userId) { http_response_code(403); exit('Active clinical account required.'); }
// The access decision is made in SQL before querying or displaying medical history.
$check = $pdo->prepare("SELECT p.patient_id, p.first_name, p.last_name, p.date_of_birth, p.gender,
    p.citizenship_status, p.document_type
    FROM patients p
    WHERE p.patient_id = ? AND (
       EXISTS (SELECT 1 FROM patient_visits v WHERE v.patient_id = p.patient_id AND v.visit_status IN ('Waiting', 'In Consultation'))
       OR EXISTS (SELECT 1 FROM consultations c WHERE c.patient_id = p.patient_id AND c.staff_id = ?)
    ) LIMIT 1");
$check->execute([$patientId, $staffId]);
$patient = $check->fetch(PDO::FETCH_ASSOC);
if (!$patient) { http_response_code(404); exit('Patient not found or no authorized clinical relationship.'); }
// If audit insertion fails, do not reveal medical records.
try {
    $audit = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)');
    $audit->execute([$userId, 'Viewed patient medical record', 'Patient', $patientId]);
} catch (Throwable $e) {
    error_log('Clinical medical-history audit error: ' . $e->getMessage());
    http_response_code(503); exit('Unable to record medical-history access. Please contact the administrator.');
}
$visitStmt = $pdo->prepare("SELECT visit_id, visit_type, visit_status, check_in_at, completed_at
    FROM patient_visits WHERE patient_id = ? ORDER BY check_in_at DESC, visit_id DESC LIMIT 20");
$visitStmt->execute([$patientId]); $visits = $visitStmt->fetchAll(PDO::FETCH_ASSOC);
$consultStmt = $pdo->prepare("SELECT c.consultation_id, c.visit_id, c.consultation_date, c.symptoms,
    c.diagnosis, c.treatment_plan, c.notes, c.consultation_status,
    s.first_name AS clinician_first, s.last_name AS clinician_last, s.professional_role
    FROM consultations c JOIN staff s ON s.staff_id = c.staff_id
    WHERE c.patient_id = ? AND c.consultation_status = 'Completed'
    ORDER BY c.consultation_date DESC, c.consultation_id DESC LIMIT 30");
$consultStmt->execute([$patientId]); $consultations = $consultStmt->fetchAll(PDO::FETCH_ASSOC);
$obsStmt = $pdo->prepare("SELECT n.observation_id, n.observation_date, n.observation_type, n.observation_value,
    n.notes, s.first_name AS clinician_first, s.last_name AS clinician_last, s.professional_role
    FROM nursing_observations n JOIN staff s ON s.staff_id = n.staff_id
    WHERE n.patient_id = ? ORDER BY n.observation_date DESC, n.observation_id DESC LIMIT 30");
$obsStmt->execute([$patientId]); $observations = $obsStmt->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="clinical-main pm-page">
  <div class="clinical-page-heading"><div><div class="clinical-eyebrow">PATIENT MEDICAL RECORDS</div><h1><?= phH($patient['first_name'] . ' ' . $patient['last_name']) ?></h1><p>Read-only clinical record · Patient #<?= (int)$patientId ?></p></div><a class="pm-btn pm-secondary" href="/clinical/patients.php">Back to Patient Search</a></div>
  <section class="clinical-card pm-card"><div class="clinical-card-heading"><div><h2>Patient Information</h2><p>Identity details are displayed without ID/passport numbers or contact information.</p></div></div>
    <dl class="pm-facts"><div><dt>Patient ID</dt><dd>#<?= (int)$patientId ?></dd></div>
    <div><dt>Date of Birth</dt><dd><?= phH($patient['date_of_birth'] ?: 'Not recorded') ?></dd></div>
    <div><dt>Gender</dt><dd><?= phH($patient['gender'] ?: 'Not recorded') ?></dd></div>
    <div><dt>Citizenship</dt><dd><?= phH($patient['citizenship_status'] ?: 'Not confirmed') ?></dd></div></dl>
  </section>
  <section class="clinical-card pm-card"><div class="clinical-card-heading"><div><h2>Visit History</h2><p>Most recent 20 check-ins. Visit status is separate from consultation status.</p></div></div>
    <div class="clinical-table-scroll"><table class="clinical-table pm-table"><thead><tr><th>Visit</th><th>Checked In</th><th>Category</th><th>Status</th><th>Completed At</th></tr></thead><tbody>
    <?php if (!$visits): ?><tr><td colspan="5">No visits recorded.</td></tr><?php endif; ?>
    <?php foreach ($visits as $v): ?><tr><td>#<?= (int)$v['visit_id'] ?></td><td><?= phDate($v['check_in_at']) ?></td><td><?= phH($v['visit_type']) ?></td><td><?= phH($v['visit_status']) ?></td><td><?= phDate($v['completed_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
  <section class="clinical-card pm-card"><div class="clinical-card-heading"><div><h2>Completed Consultations</h2><p>Most recent 30 completed records, including Doctor and Professional Nurse entries.</p></div></div>
  <?php if (!$consultations): ?><p class="pm-empty">No completed consultations recorded.</p><?php endif; ?>
  <div class="pm-history">
  <?php foreach ($consultations as $c): ?><details class="pm-history-entry"><summary><strong><?= phDate($c['consultation_date']) ?></strong> · <?= phH($c['clinician_first'] . ' ' . $c['clinician_last']) ?> (<?= phH($c['professional_role']) ?>) · Consultation #<?= (int)$c['consultation_id'] ?><?= $c['visit_id'] ? ' · Visit #' . (int)$c['visit_id'] : ' · Legacy record' ?></summary>
    <div class="pm-notes-grid"><div><h3>Symptoms / Assessment</h3><p><?= nl2br(phH($c['symptoms'] ?: 'Not recorded')) ?></p></div><div><h3>Clinical Impression</h3><p><?= nl2br(phH($c['diagnosis'] ?: 'Not recorded')) ?></p></div><div><h3>Treatment / Care Plan</h3><p><?= nl2br(phH($c['treatment_plan'] ?: 'Not recorded')) ?></p></div><div><h3>Notes</h3><p><?= nl2br(phH($c['notes'] ?: 'Not recorded')) ?></p></div></div>
  </details><?php endforeach; ?></div></section>
  <section class="clinical-card pm-card"><div class="clinical-card-heading"><div><h2>Nursing Observations</h2><p>Most recent 30 observations (read-only).</p></div></div>
    <div class="clinical-table-scroll"><table class="clinical-table pm-table"><thead><tr><th>Recorded</th><th>Type</th><th>Value</th><th>Clinical Notes</th><th>Recorded By</th></tr></thead><tbody>
    <?php if (!$observations): ?><tr><td colspan="5">No nursing observations have been recorded.</td></tr><?php endif; ?>
    <?php foreach ($observations as $o): ?><tr><td><?= phDate($o['observation_date']) ?></td><td><?= phH($o['observation_type']) ?></td><td><?= phH($o['observation_value']) ?></td><td class="pm-wrap"><?= nl2br(phH($o['notes'] ?: '—')) ?></td><td><?= phH($o['clinician_first'] . ' ' . $o['clinician_last']) ?> (<?= phH($o['professional_role']) ?>)</td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
  <p class="pm-footnote">Access is logged. Medical information is confidential and must only be used for authorized patient care. No changes can be made from this page.</p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
