<?php
/**
 * Record an observation for a currently active clinic visit.
 * Professional Nurse only. Observation is patient-scoped in the existing schema.
 * Never trusts patient_id, staff_id, or timestamps supplied by the browser.
 */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';
require_once __DIR__ . '/observation-validation.php';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
$page_title = 'Record Clinical Observation';

try {
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

// Measurement rules and supported units are centralized in observation-validation.php.
$types = hmsObservationTypes();

if (!isset($_SESSION['clinical_observation_csrf']) || !is_string($_SESSION['clinical_observation_csrf'])) {
    $_SESSION['clinical_observation_csrf'] = bin2hex(random_bytes(32));
}
$err = '';
$selectedType = '';
$enteredValue = '';
$enteredNotes = '';
$reviewWarnings = [];
$reviewConfirmed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    $selectedType = $_POST['observation_type'] ?? '';
    $enteredValue = $_POST['observation_value'] ?? '';
    $enteredNotes = $_POST['notes'] ?? '';
    $reviewConfirmed = ($_POST['confirm_unusual'] ?? null) === '1';
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
        if (strlen($enteredNotes) > 2000) {
            throw new DomainException('Notes must be 2,000 bytes or fewer.');
        }

        $measurement = hmsValidateObservation($selectedType, $enteredValue);
        if (!$measurement['valid']) {
            unset($_SESSION['clinical_observation_pending_review']);
            throw new DomainException($measurement['error']);
        }
        $reviewWarnings = $measurement['warnings'];
        $storedValue = $measurement['stored'];

        // Atypical readings are not auto-corrected or silently rejected.
        // The Nurse must see a warning, then verify it and document context.
        if ($reviewWarnings) {
            $fingerprint = hash('sha256', implode('|', [
                (string)$userId, (string)$visitId, $selectedType,
                $measurement['normalized'], $_SESSION['clinical_observation_csrf']
            ]));
            $pending = $_SESSION['clinical_observation_pending_review'] ?? null;
            if (!$reviewConfirmed || !is_string($pending) || !hash_equals($pending, $fingerprint)) {
                $_SESSION['clinical_observation_pending_review'] = $fingerprint;
                $reviewConfirmed = false;
                throw new DomainException('Unusual reading: check the measurement and units. If verified, add explanatory notes and tick the confirmation box before saving again.');
            }
            if (strlen($enteredNotes) < 10) {
                throw new DomainException('For an unusual reading, add at least 10 characters of factual verification notes before saving.');
            }
        } else {
            unset($_SESSION['clinical_observation_pending_review']);
        }

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
        roAudit($pdo, (int)$userId, 'Recorded clinical observation during visit #' . $visitId . ($reviewWarnings ? ' (atypical reading confirmed)' : ''), 'Nursing Observation', $observationId);
        $pdo->commit();

        // One-use token + PRG prevents an ordinary refresh from saving twice.
        unset($_SESSION['clinical_observation_pending_review']);
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

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
} catch (Throwable $e) {
    error_log('Clinical observation process failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to process the clinical observation request. Please try again or contact the administrator.');
}
?>