<?php
/** Clinical patient history: read-only, patient-scoped access gate, fail-closed audit. */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

$page_title = 'Patient Medical History';

function phH($v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function phDate($v): string
{
    return $v ? phH(date('d M Y, h:i A', strtotime($v))) : 'Not recorded';
}

$patientId = filter_input(INPUT_GET, 'patient_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$staffId = filter_var($_SESSION['staff_id'] ?? null, FILTER_VALIDATE_INT);
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);

if (!$patientId) {
    http_response_code(400);
    exit('Choose a patient from Medical Records.');
}

if (!$staffId || !$userId) {
    http_response_code(403);
    exit('Active clinical account required.');
}

// The access decision is made in SQL before querying or displaying medical history.
$check = $pdo->prepare("
    SELECT p.patient_id, p.first_name, p.last_name, p.date_of_birth, p.gender,
           p.citizenship_status, p.document_type
    FROM patients p
    WHERE p.patient_id = ? AND (
        EXISTS (
            SELECT 1
            FROM patient_visits v
            WHERE v.patient_id = p.patient_id
              AND v.visit_status IN ('Waiting', 'In Consultation')
        )
        OR EXISTS (
            SELECT 1
            FROM consultations c
            WHERE c.patient_id = p.patient_id
              AND c.staff_id = ?
        )
    ) LIMIT 1");
$check->execute([$patientId, $staffId]);
$patient = $check->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    http_response_code(404);
    exit('Patient not found or no authorized clinical relationship.');
}

// If audit insertion fails, do not reveal medical records.
try {
    $audit = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)');
    $audit->execute([$userId, 'Viewed patient medical record', 'Patient', $patientId]);
} catch (Throwable $e) {
    error_log('Clinical medical-history audit error: ' . $e->getMessage());
    http_response_code(503);
    exit('Unable to record medical-history access. Please contact the administrator.');
}

$visitStmt = $pdo->prepare("SELECT visit_id, visit_type, visit_status, check_in_at, completed_at
    FROM patient_visits WHERE patient_id = ? ORDER BY check_in_at DESC, visit_id DESC LIMIT 20");
$visitStmt->execute([$patientId]);
$visits = $visitStmt->fetchAll(PDO::FETCH_ASSOC);

$consultStmt = $pdo->prepare("SELECT c.consultation_id, c.visit_id, c.consultation_date, c.symptoms,
    c.diagnosis, c.treatment_plan, c.notes, c.consultation_status,
    s.first_name AS clinician_first, s.last_name AS clinician_last, s.professional_role
    FROM consultations c JOIN staff s ON s.staff_id = c.staff_id
    WHERE c.patient_id = ? AND c.consultation_status = 'Completed'
    ORDER BY c.consultation_date DESC, c.consultation_id DESC LIMIT 30");
$consultStmt->execute([$patientId]);
$consultations = $consultStmt->fetchAll(PDO::FETCH_ASSOC);

$obsStmt = $pdo->prepare("SELECT n.observation_id, n.observation_date, n.observation_type, n.observation_value,
    n.notes, s.first_name AS clinician_first, s.last_name AS clinician_last, s.professional_role
    FROM nursing_observations n JOIN staff s ON s.staff_id = n.staff_id
    WHERE n.patient_id = ? ORDER BY n.observation_date DESC, n.observation_id DESC LIMIT 30");
$obsStmt->execute([$patientId]);
$observations = $obsStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>