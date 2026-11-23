<?php
/**
 * Receptionist Dashboard
 * Shows permanent patient registrations and today's clinic visits.
 * Visits are read-only here: only authorized clinical staff update care status.
 */
require_once __DIR__ . '/../includes/receptionist-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = 'Receptionist Dashboard';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');

function escapeReceptionistDashboard($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Keep "today" consistent with the existing Waiting List (MySQL CURDATE()).
$dbToday = (string) $pdo->query('SELECT CURDATE()')->fetchColumn();
$todayLabel = date('d M Y', strtotime($dbToday));

// Permanent patient profiles.
$totalPatients = (int) $pdo->query('SELECT COUNT(*) FROM patients')->fetchColumn();

// New permanent patient profiles registered today (not the same as check-ins).
$patientsRegisteredToday = (int) $pdo->query("
    SELECT COUNT(*)
    FROM patients
    WHERE created_at >= CURDATE()
      AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
")->fetchColumn();

// Today's visits, counting all statuses (including cancelled visits in the total).
$summaryStmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(visit_status = 'Waiting'), 0) AS waiting,
        COALESCE(SUM(visit_status = 'In Consultation'), 0) AS in_consultation,
        COALESCE(SUM(visit_status = 'Completed'), 0) AS completed,
        COALESCE(SUM(visit_status = 'Cancelled'), 0) AS cancelled
    FROM patient_visits
    WHERE check_in_at >= CURDATE()
      AND check_in_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
");
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$visitCounts = [
    'total'           => (int) ($summary['total'] ?? 0),
    'waiting'         => (int) ($summary['waiting'] ?? 0),
    'in_consultation' => (int) ($summary['in_consultation'] ?? 0),
    'completed'       => (int) ($summary['completed'] ?? 0),
    'cancelled'       => (int) ($summary['cancelled'] ?? 0),
];

// Older unresolved visits need review; they are deliberately not counted as today's arrivals.
$olderActiveVisits = (int) $pdo->query("
    SELECT COUNT(*)
    FROM patient_visits
    WHERE check_in_at < CURDATE()
      AND visit_status IN ('Waiting', 'In Consultation')
")->fetchColumn();

// Most recent check-ins made today; no clinical information is shown.
$recentVisitStmt = $pdo->query("
    SELECT
        pv.visit_id,
        pv.patient_id,
        pv.check_in_at,
        pv.visit_type,
        pv.visit_status,
        p.first_name,
        p.last_name,
        u.username AS checked_in_by_username
    FROM patient_visits pv
    INNER JOIN patients p ON p.patient_id = pv.patient_id
    INNER JOIN users u ON u.user_id = pv.checked_in_by
    WHERE pv.check_in_at >= CURDATE()
      AND pv.check_in_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ORDER BY pv.check_in_at DESC, pv.visit_id DESC
    LIMIT 8
");
$recentVisits = $recentVisitStmt->fetchAll(PDO::FETCH_ASSOC);

// Keep the existing Recent Patient Registrations feature.
$recentPatientStmt = $pdo->query("
    SELECT patient_id, first_name, last_name, gender, created_at
    FROM patients
    ORDER BY created_at DESC, patient_id DESC
    LIMIT 5
");
$recentPatients = $recentPatientStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>