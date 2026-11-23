<?php
/**
 * HMS - Receptionist Patient Check-In controller.
 * Included at the top of receptionist/check-in.php, before any HTML output.
 * Uses existing hospital_management.patient_visits schema.
 */
require_once __DIR__ . '/../includes/receptionist-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = 'Patient Check-In';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

function escapeCheckIn($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function maskCheckInDocument($value): string
{
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return 'Not recorded';
    }
    return escapeCheckIn(
        str_repeat('*', max(0, strlen($value) - 4)) . substr($value, -4)
    );
}

$patientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$patientId || $patientId < 1) {
    http_response_code(400);
    exit('A valid patient ID is required. Select a patient from Patient Management.');
}

if (empty($_SESSION['patient_checkin_csrf'])) {
    $_SESSION['patient_checkin_csrf'] = bin2hex(random_bytes(32));
}

$visitTypes = ['General Consultation', 'Chronic Follow-up', 'Other'];
$selectedVisitType = 'General Consultation';
$error = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedVisitType = isset($_POST['visit_type']) && is_string($_POST['visit_type'])
        ? trim($_POST['visit_type'])
        : '';
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) ||
        !hash_equals($_SESSION['patient_checkin_csrf'], $submittedToken)) {
        $error = 'Your session token is invalid. Refresh the page and try again.';
    } elseif (!in_array($selectedVisitType, $visitTypes, true)) {
        $error = 'Select a valid visit category.';
    } else {
        try {
            $pdo->beginTransaction();

            // Lock the permanent patient record first. All check-ins using this
            // controller serialize on this row, preventing simultaneous active visits.
            $lockPatient = $pdo->prepare(
                'SELECT patient_id FROM patients WHERE patient_id = ? FOR UPDATE'
            );
            $lockPatient->execute([$patientId]);
            if (!$lockPatient->fetchColumn()) {
                throw new RuntimeException('The patient record no longer exists.');
            }

            // An active visit on ANY day prevents another check-in until resolved.
            $findActive = $pdo->prepare(
                "SELECT visit_id FROM patient_visits
                 WHERE patient_id = ?
                   AND visit_status IN ('Waiting', 'In Consultation')
                 ORDER BY visit_id DESC LIMIT 1"
            );
            $findActive->execute([$patientId]);
            if ($findActive->fetchColumn()) {
                throw new RuntimeException(
                    'This patient already has an active visit. Please review the existing visit before checking in again.'
                );
            }

            // Visit status begins as Waiting. The clinician updates it later.
            $insert = $pdo->prepare(
                "INSERT INTO patient_visits (patient_id, checked_in_by, visit_type, visit_status)
                 VALUES (?, ?, ?, 'Waiting')"
            );
            $insert->execute([
                $patientId,
                (int) $_SESSION['user_id'],
                $selectedVisitType,
            ]);
            $visitId = (int) $pdo->lastInsertId();

            $audit = $pdo->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id)
                 VALUES (?, ?, ?, ?)'
            );
            $audit->execute([
                (int) $_SESSION['user_id'],
                'Checked in patient for visit',
                'Patient Visit',
                $visitId,
            ]);

            $pdo->commit();

            // Post/Redirect/Get prevents accidental duplicate form submission.
            $_SESSION['patient_checkin_success'] = [
                'patient_id' => $patientId,
                'visit_id' => $visitId,
            ];
            unset($_SESSION['patient_checkin_csrf']);
            header('Location: check-in.php?id=' . $patientId, true, 303);
            exit;
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Patient check-in failed: ' . $e->getMessage());
            $error = 'Unable to check in this patient. Please try again.';
        }
    }
}

// Load the patient for display. No clinical history is exposed to Receptionists.
$stmt = $pdo->prepare(
    'SELECT patient_id, first_name, last_name, document_type,
            id_number, passport_number, passport_issuing_country
     FROM patients WHERE patient_id = ? LIMIT 1'
);
$stmt->execute([$patientId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    http_response_code(404);
    exit('Patient record not found.');
}

// Show any pre-existing visit, even one from a prior day not yet closed.
$findActive = $pdo->prepare(
    "SELECT visit_id, check_in_at, visit_type, visit_status
     FROM patient_visits
     WHERE patient_id = ?
       AND visit_status IN ('Waiting', 'In Consultation')
     ORDER BY visit_id DESC LIMIT 1"
);
$findActive->execute([$patientId]);
$activeVisit = $findActive->fetch(PDO::FETCH_ASSOC) ?: null;

$flash = $_SESSION['patient_checkin_success'] ?? null;
unset($_SESSION['patient_checkin_success']);
if (is_array($flash) && (int) ($flash['patient_id'] ?? 0) === $patientId) {
    $successMessage = 'Patient checked in successfully. Visit #'
        . (int) ($flash['visit_id'] ?? 0) . ' is now Waiting.';
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
