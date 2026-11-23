<?php
/**
 * Shared clinical dashboard for Doctors and Professional Nurses.
 * Read-only overview. Consultation actions will be added in a later stage.
 */
require_once __DIR__ . '/includes/clinical-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';

$page_title = 'Clinical Dashboard';

// Do not allow the browser to retain a cached clinical dashboard after logout.
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

function clinicalEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Match the existing Receptionist Waiting List's MySQL definition of today.
$dbToday = (string) $pdo->query('SELECT CURDATE()')->fetchColumn();
$todayLabel = date('d M Y', strtotime($dbToday));

// All currently active visits, even when the patient checked in yesterday.
$activeSummary = $pdo->query("
    SELECT
        COALESCE(SUM(visit_status = 'Waiting'), 0) AS waiting,
        COALESCE(SUM(visit_status = 'In Consultation'), 0) AS in_consultation
    FROM patient_visits
")->fetch(PDO::FETCH_ASSOC) ?: [];

// Visits registered today and completed visits among those check-ins.
$todaySummary = $pdo->query("
    SELECT
        COUNT(*) AS check_ins,
        COALESCE(SUM(visit_status = 'Completed'), 0) AS completed
    FROM patient_visits
    WHERE check_in_at >= CURDATE()
      AND check_in_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
")->fetch(PDO::FETCH_ASSOC) ?: [];

$stats = [
    'waiting' => (int) ($activeSummary['waiting'] ?? 0),
    'in_consultation' => (int) ($activeSummary['in_consultation'] ?? 0),
    'check_ins' => (int) ($todaySummary['check_ins'] ?? 0),
    'completed' => (int) ($todaySummary['completed'] ?? 0),
];

$olderActiveVisits = (int) $pdo->query("
    SELECT COUNT(*)
    FROM patient_visits
    WHERE check_in_at < CURDATE()
      AND visit_status IN ('Waiting', 'In Consultation')
")->fetchColumn();

// Active clinical queue; no diagnoses, symptoms, treatments, or document numbers.
$queueStmt = $pdo->query("
    SELECT
        pv.visit_id,
        pv.patient_id,
        pv.check_in_at,
        pv.visit_type,
        pv.visit_status,
        p.first_name,
        p.last_name
    FROM patient_visits pv
    INNER JOIN patients p ON p.patient_id = pv.patient_id
    WHERE pv.visit_status IN ('Waiting', 'In Consultation')
    ORDER BY
        CASE pv.visit_status
            WHEN 'Waiting' THEN 0
            ELSE 1
        END,
        pv.check_in_at ASC,
        pv.visit_id ASC
    LIMIT 15
");
$activeVisits = $queueStmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main class="clinical-main">
    <div class="clinical-page-heading">
        <div>
            <div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
            <h1>Clinical Dashboard</h1>
            <p>Welcome, <?= clinicalEscape($_SESSION['role']) ?>. Clinic activity for <?= clinicalEscape($todayLabel) ?>.</p>
        </div>
        <span class="clinical-read-only">Overview only</span>
    </div>

    <section class="clinical-stats" aria-label="Clinic visit statistics">
        <div class="clinical-stat">
            <span>Waiting</span>
            <strong><?= $stats['waiting'] ?></strong>
            <small>All active visits awaiting care</small>
        </div>
        <div class="clinical-stat">
            <span>In Consultation</span>
            <strong><?= $stats['in_consultation'] ?></strong>
            <small>All active visits in progress</small>
        </div>
        <div class="clinical-stat">
            <span>Today's Check-Ins</span>
            <strong><?= $stats['check_ins'] ?></strong>
            <small>Includes all visit statuses</small>
        </div>
        <div class="clinical-stat">
            <span>Completed</span>
            <strong><?= $stats['completed'] ?></strong>
            <small>Completed among today's check-ins</small>
        </div>
    </section>

    <?php if ($olderActiveVisits > 0): ?>
        <div class="clinical-notice" role="status">
            <strong><?= $olderActiveVisits ?> older active visit(s) need review.</strong>
            These patients checked in before today but are still waiting or in consultation.
        </div>
    <?php endif; ?>

    <section class="clinical-card">
        <div class="clinical-card-heading">
            <div>
                <h2>Active Patient Visits</h2>
                <p>Patients registered by Receptionists and awaiting or receiving care. Oldest waiting visits appear first.</p>
            </div>
            <span class="clinical-note-pill">15 most relevant</span>
        </div>
        <div class="clinical-table-scroll">
            <table class="clinical-table">
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Patient</th>
                        <th>Patient ID</th>
                        <th>Checked In</th>
                        <th>Visit Category</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($activeVisits): ?>
                        <?php foreach ($activeVisits as $visit): ?>
                            <?php $statusClass = $visit['visit_status'] === 'Waiting'
                                ? 'clinical-badge-waiting'
                                : 'clinical-badge-progress'; ?>
                            <tr>
                                <td>#<?= (int) $visit['visit_id'] ?></td>
                                <td><strong><?= clinicalEscape($visit['first_name'] . ' ' . $visit['last_name']) ?></strong></td>
                                <td>#<?= (int) $visit['patient_id'] ?></td>
                                <td><?= clinicalEscape(date('d M Y, h:i A', strtotime($visit['check_in_at']))) ?></td>
                                <td><?= clinicalEscape($visit['visit_type']) ?></td>
                                <td>
                                    <span class="clinical-status <?= $statusClass ?>">
                                        <?= clinicalEscape($visit['visit_status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="clinical-empty">No active patient visits at the moment.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <p class="clinical-bottom-note">
        Consultation actions and medical-history access will be enabled after we implement
        the Clinical Waiting List and confirm their permission checks. This dashboard does
        not modify patient visits or clinical records.
    </p>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
