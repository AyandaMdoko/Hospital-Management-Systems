<?php
/**
 * HMS — Clinical consultation workflow (Doctor and Professional Nurse).
 * Every NEW consultation is bound to a patient_visits.visit_id.
 * Uses InnoDB row locking and audit logs for every state-changing action.
 * Only the author can save or complete their own in-progress consultation.
 */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
$page_title = 'Patient Consultation';

function ccH($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function ccText($value, int $limit): string
{
    if (!is_string($value)) {
        throw new DomainException('Invalid clinical form input.');
    }
    $value = trim($value);
    // Database uses text fields; impose conservative byte limit for MySQL UTF-8 safety.
    if (strlen($value) > $limit) {
        throw new DomainException('One of your clinical entries is too long. Please shorten it.');
    }
    return $value;
}

function ccAudit(PDO $pdo, int $userId, string $action, string $entityType, int $entityId): void
{
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $entityType, $entityId]);
}

function ccFlash(string $type, string $message): void
{
    $_SESSION['clinical_consultation_flash'] = ['type' => $type, 'message' => $message];
}

$visitId = filter_input(INPUT_GET, 'visit_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if (!is_int($visitId) || $visitId < 1) {
    http_response_code(400);
    exit('Choose a valid visit from the Clinical Waiting List.');
}

$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
$staffId = filter_var($_SESSION['staff_id'] ?? null, FILTER_VALIDATE_INT);
if (!$userId || !$staffId) {
    http_response_code(403);
    exit('An active linked clinical account is required.');
}

if (!isset($_SESSION['clinical_consultation_csrf']) || !is_string($_SESSION['clinical_consultation_csrf'])) {
    $_SESSION['clinical_consultation_csrf'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!is_string($token) || !hash_equals($_SESSION['clinical_consultation_csrf'], $token)) {
        $error = 'Invalid or expired form token. Refresh this page and try again.';
    } elseif (!is_string($action) || !in_array($action, ['start', 'save', 'complete'], true)) {
        $error = 'Select a valid consultation action.';
    } else {
        try {
            $pdo->beginTransaction();

            // A single lock on the visit serializes all clinicians' start/save/finish
            // operations. It prevents two simultaneous starts for the same visit.
            $lock = $pdo->prepare('SELECT patient_id, visit_status FROM patient_visits WHERE visit_id = ? FOR UPDATE');
            $lock->execute([$visitId]);
            $lockedVisit = $lock->fetch(PDO::FETCH_ASSOC);
            if (!$lockedVisit) {
                throw new DomainException('The selected patient visit no longer exists.');
            }
            $patientId = (int) $lockedVisit['patient_id'];
            $visitStatus = (string) $lockedVisit['visit_status'];

            if ($action === 'start') {
                if (!in_array($visitStatus, ['Waiting', 'In Consultation'], true)) {
                    throw new DomainException('This visit is not active. A consultation cannot be started.');
                }
                $check = $pdo->prepare("SELECT consultation_id FROM consultations
                                       WHERE visit_id = ? AND consultation_status = 'In Progress'
                                       LIMIT 1 FOR UPDATE");
                $check->execute([$visitId]);
                if ($check->fetchColumn()) {
                    throw new DomainException('A consultation is already in progress for this visit. Refresh the page.');
                }

                // Explicitly set all clinical columns to avoid relying on nullable defaults.
                $create = $pdo->prepare("INSERT INTO consultations
                    (visit_id, patient_id, staff_id, consultation_date,
                     symptoms, diagnosis, treatment_plan, notes, consultation_status)
                    VALUES (?, ?, ?, NOW(), '', '', '', '', 'In Progress')");
                $create->execute([$visitId, $patientId, (int) $staffId]);
                $consultationId = (int) $pdo->lastInsertId();

                $updateVisit = $pdo->prepare("UPDATE patient_visits
                                              SET visit_status = 'In Consultation', completed_at = NULL
                                              WHERE visit_id = ?");
                $updateVisit->execute([$visitId]);

                ccAudit($pdo, (int) $userId, 'Started clinical consultation', 'Consultation', $consultationId);
                ccAudit($pdo, (int) $userId, 'Visit moved to In Consultation', 'Patient Visit', $visitId);
                $flashMessage = 'Consultation #' . $consultationId . ' started. Document the care below.';
            } else {
                if ($visitStatus !== 'In Consultation') {
                    throw new DomainException('This visit is not currently in consultation. Refresh the page.');
                }

                $consultationId = filter_var($_POST['consultation_id'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if (!$consultationId) {
                    throw new DomainException('The consultation reference is invalid.');
                }

                $check = $pdo->prepare('SELECT consultation_id, patient_id, staff_id, consultation_status
                                        FROM consultations WHERE consultation_id = ? AND visit_id = ? FOR UPDATE');
                $check->execute([$consultationId, $visitId]);
                $consultation = $check->fetch(PDO::FETCH_ASSOC);
                if (!$consultation || (int) $consultation['patient_id'] !== $patientId) {
                    throw new DomainException('Consultation and visit do not match.');
                }
                if ((int) $consultation['staff_id'] !== (int) $staffId) {
                    throw new DomainException('Only the clinician who started this consultation may edit or complete it.');
                }
                if ($consultation['consultation_status'] !== 'In Progress') {
                    throw new DomainException('This consultation is already completed. Completed records cannot be overwritten.');
                }

                $symptoms = ccText($_POST['symptoms'] ?? null, 5000);
                $diagnosis = ccText($_POST['diagnosis'] ?? null, 5000);
                $treatmentPlan = ccText($_POST['treatment_plan'] ?? null, 5000);
                $notes = ccText($_POST['notes'] ?? null, 10000);

                if ($action === 'complete' && $symptoms === '' && $diagnosis === ''
                    && $treatmentPlan === '' && $notes === '') {
                    throw new DomainException('Add at least one clinical note before completing the consultation.');
                }

                $newStatus = $action === 'complete' ? 'Completed' : 'In Progress';
                $update = $pdo->prepare('UPDATE consultations
                    SET symptoms = ?, diagnosis = ?, treatment_plan = ?, notes = ?, consultation_status = ?
                    WHERE consultation_id = ? AND visit_id = ? AND staff_id = ? AND consultation_status = \'In Progress\'');
                $update->execute([
                    $symptoms, $diagnosis, $treatmentPlan, $notes, $newStatus,
                    $consultationId, $visitId, (int) $staffId,
                ]);

                if ($action === 'complete') {
                    $nextStep = $_POST['next_step'] ?? null;
                    if (!is_string($nextStep) || !in_array($nextStep, ['finish_visit', 'handoff'], true)) {
                        throw new DomainException('Select whether the patient is finished or needs another clinician.');
                    }
                    if ($nextStep === 'finish_visit') {
                        $finish = $pdo->prepare("UPDATE patient_visits
                            SET visit_status = 'Completed', completed_at = NOW()
                            WHERE visit_id = ?");
                        $finish->execute([$visitId]);
                        ccAudit($pdo, (int) $userId, 'Completed patient visit', 'Patient Visit', $visitId);
                        $flashMessage = 'Consultation completed and the patient visit marked Completed.';
                    } else {
                        $handoff = $pdo->prepare("UPDATE patient_visits
                            SET visit_status = 'Waiting', completed_at = NULL
                            WHERE visit_id = ?");
                        $handoff->execute([$visitId]);
                        ccAudit($pdo, (int) $userId, 'Returned patient visit to Waiting for further clinical care', 'Patient Visit', $visitId);
                        $flashMessage = 'Consultation completed. The patient has returned to the Waiting List for further care.';
                    }
                    ccAudit($pdo, (int) $userId, 'Completed clinical consultation', 'Consultation', (int) $consultationId);
                } else {
                    ccAudit($pdo, (int) $userId, 'Saved clinical consultation draft', 'Consultation', (int) $consultationId);
                    $flashMessage = 'Consultation notes saved as a draft.';
                }
            }

            $pdo->commit();
            ccFlash('success', $flashMessage);
            // Post/Redirect/Get, fresh token after every successful action.
            $_SESSION['clinical_consultation_csrf'] = bin2hex(random_bytes(32));
            header('Location: /clinical/consultation.php?visit_id=' . $visitId, true, 303);
            exit;
        } catch (DomainException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Clinical consultation error: ' . $e->getMessage());
            $error = 'We could not save the clinical action. Your visit was not changed. Please try again or contact the administrator.';
        }
    }
}

$visitStmt = $pdo->prepare('SELECT pv.visit_id, pv.patient_id, pv.visit_status, pv.visit_type,
    pv.check_in_at, p.first_name, p.last_name, p.date_of_birth, p.gender
    FROM patient_visits pv JOIN patients p ON p.patient_id = pv.patient_id
    WHERE pv.visit_id = ? LIMIT 1');
$visitStmt->execute([$visitId]);
$visit = $visitStmt->fetch(PDO::FETCH_ASSOC);
if (!$visit) {
    http_response_code(404);
    exit('Patient visit not found.');
}

// A currently active visit is legitimate clinical queue work. Once a visit
// is closed, only a clinician who personally documented that visit may open
// this visit-specific screen. Other clinicians can review old records through
// a NEW active visit for that patient. This prevents easy ID enumeration of
// closed visit pages by unrelated staff.
if (!in_array($visit['visit_status'], ['Waiting', 'In Consultation'], true)) {
    $participated = $pdo->prepare('SELECT 1 FROM consultations WHERE visit_id = ? AND staff_id = ? LIMIT 1');
    $participated->execute([$visitId, (int) $staffId]);
    if (!$participated->fetchColumn()) {
        http_response_code(403);
        exit('Access denied to this closed visit. Open the patient through an active clinical visit.');
    }
}

// Track individual access to the visit and associated history. If audit logging
// fails, do not display sensitive clinical history.
try {
    ccAudit($pdo, (int) $userId, 'Viewed clinical visit and medical history', 'Patient Visit', $visitId);
} catch (Throwable $e) {
    error_log('Clinical history access audit failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Unable to record patient-record access. Please contact the administrator.');
}

$activeStmt = $pdo->prepare("SELECT c.consultation_id, c.patient_id, c.staff_id,
    c.consultation_date, c.symptoms, c.diagnosis, c.treatment_plan, c.notes,
    c.consultation_status, s.first_name AS staff_first, s.last_name AS staff_last,
    s.professional_role
    FROM consultations c JOIN staff s ON s.staff_id = c.staff_id
    WHERE c.visit_id = ? AND c.consultation_status = 'In Progress'
    ORDER BY c.consultation_id DESC LIMIT 1");
$activeStmt->execute([$visitId]);
$active = $activeStmt->fetch(PDO::FETCH_ASSOC) ?: null;

$historyStmt = $pdo->prepare('SELECT c.consultation_id, c.visit_id, c.consultation_date,
    c.symptoms, c.diagnosis, c.treatment_plan, c.notes, c.consultation_status,
    s.first_name AS staff_first, s.last_name AS staff_last, s.professional_role
    FROM consultations c JOIN staff s ON s.staff_id = c.staff_id
    WHERE c.patient_id = ? AND c.consultation_status = \'Completed\'
    ORDER BY c.consultation_date DESC, c.consultation_id DESC LIMIT 12');
$historyStmt->execute([(int) $visit['patient_id']]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

$observationsStmt = $pdo->prepare('SELECT n.observation_date, n.observation_type,
    n.observation_value, n.notes, s.first_name AS staff_first, s.last_name AS staff_last
    FROM nursing_observations n JOIN staff s ON s.staff_id = n.staff_id
    WHERE n.patient_id = ? ORDER BY n.observation_date DESC, n.observation_id DESC LIMIT 8');
$observationsStmt->execute([(int) $visit['patient_id']]);
$observations = $observationsStmt->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['clinical_consultation_flash'] ?? null;
unset($_SESSION['clinical_consultation_flash']);
$ownActive = $active && (int) $active['staff_id'] === (int) $staffId;
$isActiveVisit = in_array($visit['visit_status'], ['Waiting', 'In Consultation'], true);
$canStart = $isActiveVisit && !$active;
$canEdit = $ownActive && $visit['visit_status'] === 'In Consultation';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="clinical-main clinical-consultation-page">
    <div class="clinical-page-heading">
        <div>
            <div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
            <h1>Patient Consultation</h1>
            <p>Review this visit and its relevant medical history before recording clinical care.</p>
        </div>
        <a class="cc-btn cc-btn-outline" href="/clinical/waiting-list.php">Back to Waiting List</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="cc-alert cc-alert-error" role="alert"><?= ccH($error) ?></div>
    <?php endif; ?>
    <?php if (is_array($flash) && isset($flash['message'])): ?>
        <div class="cc-alert cc-alert-success" role="status"><?= ccH($flash['message']) ?></div>
    <?php endif; ?>

    <section class="clinical-card">
        <div class="clinical-card-heading">
            <div><h2><?= ccH($visit['first_name'] . ' ' . $visit['last_name']) ?></h2>
                 <p>Patient #<?= (int) $visit['patient_id'] ?> &middot; Visit #<?= (int) $visitId ?></p></div>
            <span class="clinical-status <?= $visit['visit_status'] === 'Waiting' ? 'clinical-badge-waiting' : ($visit['visit_status'] === 'In Consultation' ? 'clinical-badge-progress' : '') ?>">
                <?= ccH($visit['visit_status']) ?>
            </span>
        </div>
        <dl class="cc-facts">
            <div><dt>Visit Category</dt><dd><?= ccH($visit['visit_type']) ?></dd></div>
            <div><dt>Checked In</dt><dd><?= ccH(date('d M Y, h:i A', strtotime($visit['check_in_at']))) ?></dd></div>
            <div><dt>Date of Birth</dt><dd><?= ccH($visit['date_of_birth'] ?: 'Not recorded') ?></dd></div>
            <div><dt>Gender</dt><dd><?= ccH($visit['gender'] ?: 'Not recorded') ?></dd></div>
        </dl>
    </section>

    <section class="clinical-card" aria-labelledby="cc-work-title">
        <div class="clinical-card-heading">
            <div>
                <h2 id="cc-work-title">Current Clinical Work</h2>
                <p>Only the clinician who starts a consultation can change its notes.</p>
            </div>
        </div>

        <?php if ($canStart): ?>
            <p class="cc-hint">Review the patient's past records below. Click Start Consultation only when you are ready to begin care.</p>
            <form method="post" action="/clinical/consultation.php?visit_id=<?= $visitId ?>">
                <input type="hidden" name="csrf_token" value="<?= ccH($_SESSION['clinical_consultation_csrf']) ?>">
                <input type="hidden" name="action" value="start">
                <button class="cc-btn cc-btn-primary" type="submit">Start Consultation</button>
            </form>
        <?php elseif ($active): ?>
            <p class="cc-hint">
                Consultation #<?= (int) $active['consultation_id'] ?> started by
                <strong><?= ccH($active['staff_first'] . ' ' . $active['staff_last']) ?></strong>
                (<?= ccH($active['professional_role']) ?>).
            </p>
            <?php if ($canEdit): ?>
                <form class="cc-form" method="post" action="/clinical/consultation.php?visit_id=<?= $visitId ?>">
                    <input type="hidden" name="csrf_token" value="<?= ccH($_SESSION['clinical_consultation_csrf']) ?>">
                    <input type="hidden" name="consultation_id" value="<?= (int) $active['consultation_id'] ?>">
                    <div class="cc-fields">
                        <div><label for="cc-symptoms">Symptoms / Assessment</label><textarea id="cc-symptoms" name="symptoms" rows="3" maxlength="5000"><?= ccH($active['symptoms']) ?></textarea></div>
                        <div><label for="cc-diagnosis">Diagnosis / Clinical Impression</label><textarea id="cc-diagnosis" name="diagnosis" rows="3" maxlength="5000"><?= ccH($active['diagnosis']) ?></textarea></div>
                        <div><label for="cc-plan">Treatment / Care Plan</label><textarea id="cc-plan" name="treatment_plan" rows="3" maxlength="5000"><?= ccH($active['treatment_plan']) ?></textarea></div>
                        <div><label for="cc-notes">Additional Clinical Notes</label><textarea id="cc-notes" name="notes" rows="4" maxlength="10000"><?= ccH($active['notes']) ?></textarea></div>
                    </div>
                    <p class="cc-hint">Document only care you are professionally authorized to provide. This module does not authorize prescribing medication.</p>
                    <div class="cc-next">
                        <label for="cc-next-step">After completing this consultation</label>
                        <select name="next_step" id="cc-next-step">
                            <option value="">Choose when completing</option>
                            <option value="finish_visit">Patient care is finished — close visit</option>
                            <option value="handoff">Patient needs another clinician — return to Waiting</option>
                        </select>
                    </div>
                    <div class="cc-actions">
                        <button class="cc-btn cc-btn-outline" type="submit" name="action" value="save">Save Draft</button>
                        <button class="cc-btn cc-btn-primary" type="submit" name="action" value="complete" onclick="return confirm('Complete this consultation and apply the selected visit outcome? Completed records cannot be edited here.');">Complete Consultation</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="cc-alert cc-alert-neutral">This consultation is in progress. The responsible clinician must finish or hand over their work before another consultation can start.</p>
                <div class="cc-readonly-grid">
                    <div><h3>Symptoms / Assessment</h3><p><?= nl2br(ccH($active['symptoms'] ?: 'No notes yet')) ?></p></div>
                    <div><h3>Clinical Impression</h3><p><?= nl2br(ccH($active['diagnosis'] ?: 'No notes yet')) ?></p></div>
                    <div><h3>Care Plan</h3><p><?= nl2br(ccH($active['treatment_plan'] ?: 'No notes yet')) ?></p></div>
                    <div><h3>Additional Notes</h3><p><?= nl2br(ccH($active['notes'] ?: 'No notes yet')) ?></p></div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="cc-alert cc-alert-neutral">This visit is <?= ccH($visit['visit_status']) ?> and cannot start another consultation. Its recorded history remains available below.</p>
        <?php endif; ?>
    </section>

    <section class="clinical-card">
        <div class="clinical-card-heading"><div><h2>Previous Consultations</h2><p>Up to 12 most recent completed records for this patient.</p></div></div>
        <?php if ($history): ?>
            <div class="cc-history">
                <?php foreach ($history as $entry): ?>
                    <details class="cc-history-entry">
                        <summary><strong><?= ccH(date('d M Y', strtotime($entry['consultation_date']))) ?></strong>
                            &middot; <?= ccH($entry['staff_first'] . ' ' . $entry['staff_last']) ?>
                            (<?= ccH($entry['professional_role']) ?>)
                            &middot; Consultation #<?= (int) $entry['consultation_id'] ?></summary>
                        <div class="cc-readonly-grid">
                            <div><h3>Symptoms / Assessment</h3><p><?= nl2br(ccH($entry['symptoms'] ?: 'Not recorded')) ?></p></div>
                            <div><h3>Clinical Impression</h3><p><?= nl2br(ccH($entry['diagnosis'] ?: 'Not recorded')) ?></p></div>
                            <div><h3>Treatment / Care Plan</h3><p><?= nl2br(ccH($entry['treatment_plan'] ?: 'Not recorded')) ?></p></div>
                            <div><h3>Notes</h3><p><?= nl2br(ccH($entry['notes'] ?: 'Not recorded')) ?></p></div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="cc-hint">No completed consultations have been recorded for this patient.</p>
        <?php endif; ?>
    </section>

    <section class="clinical-card">
        <div class="clinical-card-heading"><div><h2>Previous Nursing Observations</h2><p>Up to 8 most recent observation records.</p></div></div>
        <?php if ($observations): ?>
            <div class="clinical-table-scroll"><table class="clinical-table">
                <thead><tr><th>Date</th><th>Type</th><th>Observation</th><th>Recorded By</th></tr></thead>
                <tbody>
                <?php foreach ($observations as $observation): ?>
                    <tr>
                        <td><?= ccH(date('d M Y, h:i A', strtotime($observation['observation_date']))) ?></td>
                        <td><?= ccH($observation['observation_type']) ?></td>
                        <td><?= ccH($observation['observation_value']) ?>
                            <?php if (trim((string) ($observation['notes'] ?? '')) !== ''): ?>
                                <div class="cc-muted"><?= ccH($observation['notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= ccH($observation['staff_first'] . ' ' . $observation['staff_last']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
        <?php else: ?>
            <p class="cc-hint">No nursing observations have been recorded for this patient.</p>
        <?php endif; ?>
    </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
