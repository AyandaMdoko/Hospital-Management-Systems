<?php
/**
 * Shared Clinical Waiting List (read-only).
 * For active Doctors and Professional Nurses only.
 * Do not start consultations or update visit statuses from this page.
 */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';

$page_title = 'Patient Waiting List';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

if (!function_exists('clinicalQueueEscape')) {
    function clinicalQueueEscape($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Filter inputs are intentionally limited to fields required for finding a visit.
$search = filter_input(INPUT_GET, 'search', FILTER_UNSAFE_RAW);
$search = is_string($search) ? trim($search) : '';
$search = function_exists('mb_substr')
    ? mb_substr($search, 0, 100, 'UTF-8')
    : substr($search, 0, 100);

$status = filter_input(INPUT_GET, 'status', FILTER_UNSAFE_RAW);
$status = is_string($status) ? trim($status) : '';
if (!in_array($status, ['', 'Waiting', 'In Consultation'], true)) {
    $status = '';
}

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = (is_int($page) && $page > 0) ? $page : 1;
$perPage = 10;

// Counts cover all currently active visits, even when checked in on previous days.
$summary = $pdo->query("
    SELECT
        COUNT(*) AS active_total,
        COALESCE(SUM(visit_status = 'Waiting'), 0) AS waiting_total,
        COALESCE(SUM(visit_status = 'In Consultation'), 0) AS consultation_total,
        COALESCE(SUM(check_in_at < CURDATE()), 0) AS older_active_total
    FROM patient_visits
    WHERE visit_status IN ('Waiting', 'In Consultation')
")->fetch(PDO::FETCH_ASSOC) ?: [];

// Use positional placeholders (no repeated named parameters) to work with PDO/MySQL.
$where = ["pv.visit_status IN ('Waiting', 'In Consultation')"];
$params = [];

if ($status !== '') {
    $where[] = 'pv.visit_status = ?';
    $params[] = $status;
}

if ($search !== '') {
    // Treat LIKE wildcards typed by the user as literal search characters.
    $safeTerm = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search);
    $like = '%' . $safeTerm . '%';
    $termSql = [
        "p.first_name LIKE ? ESCAPE '!'",
        "p.last_name LIKE ? ESCAPE '!'",
        "CONCAT(p.first_name, ' ', p.last_name) LIKE ? ESCAPE '!'",
    ];
    array_push($params, $like, $like, $like);

    // Numeric searches can also match an exact internal patient or visit identifier.
    if (ctype_digit($search)) {
        $numericId = filter_var($search, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 2147483647],
        ]);
        if ($numericId !== false) {
            $termSql[] = 'pv.patient_id = ?';
            $termSql[] = 'pv.visit_id = ?';
            $params[] = $numericId;
            $params[] = $numericId;
        }
    }
    $where[] = '(' . implode(' OR ', $termSql) . ')';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$fromSql = "
    FROM patient_visits pv
    INNER JOIN patients p ON p.patient_id = pv.patient_id
    INNER JOIN users u ON u.user_id = pv.checked_in_by
";

$countStmt = $pdo->prepare("SELECT COUNT(*) {$fromSql} {$whereSql}");
$countStmt->execute($params);
$matchingVisits = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($matchingVisits / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listSql = "
    SELECT
        pv.visit_id,
        pv.patient_id,
        pv.check_in_at,
        pv.visit_type,
        pv.visit_status,
        p.first_name,
        p.last_name,
        u.username AS checked_in_by
    {$fromSql}
    {$whereSql}
    ORDER BY
        CASE pv.visit_status WHEN 'Waiting' THEN 0 ELSE 1 END,
        pv.check_in_at ASC,
        pv.visit_id ASC
    LIMIT ? OFFSET ?
";
$listStmt = $pdo->prepare($listSql);
$position = 1;
foreach ($params as $value) {
    $listStmt->bindValue(
        $position++,
        $value,
        is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
    );
}
$listStmt->bindValue($position++, $perPage, PDO::PARAM_INT);
$listStmt->bindValue($position, $offset, PDO::PARAM_INT);
$listStmt->execute();
$visits = $listStmt->fetchAll(PDO::FETCH_ASSOC);

function clinicalQueueLink(int $page, string $search, string $status): string
{
    return '/clinical/waiting-list.php?' . http_build_query([
        'search' => $search,
        'status' => $status,
        'page' => $page,
    ]);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="clinical-main">
    <div class="clinical-page-heading">
        <div>
            <div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
            <h1>Patient Waiting List</h1>
            <p>Patients checked in by Receptionists who are waiting for care or currently in consultation.</p>
        </div>
        <span class="clinical-read-only">Read-only overview</span>
    </div>

    <section class="clinical-stats" aria-label="Active visit statistics">
        <div class="clinical-stat">
            <span>Active Visits</span>
            <strong><?= (int) ($summary['active_total'] ?? 0) ?></strong>
            <small>Waiting or in consultation</small>
        </div>
        <div class="clinical-stat">
            <span>Waiting</span>
            <strong><?= (int) ($summary['waiting_total'] ?? 0) ?></strong>
            <small>Not yet being attended to</small>
        </div>
        <div class="clinical-stat">
            <span>In Consultation</span>
            <strong><?= (int) ($summary['consultation_total'] ?? 0) ?></strong>
            <small>Care currently in progress</small>
        </div>
        <div class="clinical-stat">
            <span>Older Active Visits</span>
            <strong><?= (int) ($summary['older_active_total'] ?? 0) ?></strong>
            <small>Checked in before today</small>
        </div>
    </section>

    <?php if ((int) ($summary['older_active_total'] ?? 0) > 0): ?>
        <div class="clinical-notice" role="status">
            <strong>Earlier visits still need review.</strong>
            Some patients checked in before today and remain Waiting or In Consultation.
            Their visits are shown below; clinical staff will resolve their statuses in the consultation module.
        </div>
    <?php endif; ?>

    <section class="clinical-card">
        <div class="clinical-card-heading">
            <div>
                <h2>Active Patient Visits</h2>
                <p><?= $matchingVisits ?> matching visit<?= $matchingVisits === 1 ? '' : 's' ?>.
                    Oldest waiting patients appear first.</p>
            </div>
            <span class="clinical-note-pill">Read-only</span>
        </div>

        <form class="clinical-queue-filters" method="get" action="/clinical/waiting-list.php" role="search">
            <div class="clinical-queue-field">
                <label for="clinical-visit-search">Search patient or visit</label>
                <input type="search" name="search" id="clinical-visit-search"
                       placeholder="Patient name, Patient ID or Visit ID"
                       value="<?= clinicalQueueEscape($search) ?>" maxlength="100">
            </div>
            <div class="clinical-queue-field">
                <label for="clinical-status-filter">Visit status</label>
                <select name="status" id="clinical-status-filter">
                    <option value="" <?= $status === '' ? 'selected' : '' ?>>All active statuses</option>
                    <option value="Waiting" <?= $status === 'Waiting' ? 'selected' : '' ?>>Waiting</option>
                    <option value="In Consultation" <?= $status === 'In Consultation' ? 'selected' : '' ?>>In Consultation</option>
                </select>
            </div>
            <div class="clinical-queue-controls">
                <button type="submit" class="clinical-queue-button">Filter</button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="/clinical/waiting-list.php" class="clinical-queue-clear">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="clinical-table-scroll">
            <table class="clinical-table">
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Patient</th>
                        <th>Patient ID</th>
                        <th>Checked In</th>
                        <th>Visit Category</th>
                        <th>Visit Status</th>
                        <th>Checked In By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($visits): ?>
                        <?php foreach ($visits as $visit): ?>
                            <tr>
                                <td>#<?= (int) $visit['visit_id'] ?></td>
                                <td><strong><?= clinicalQueueEscape($visit['first_name'] . ' ' . $visit['last_name']) ?></strong></td>
                                <td>#<?= (int) $visit['patient_id'] ?></td>
                                <td><?= clinicalQueueEscape(date('d M Y, h:i A', strtotime($visit['check_in_at']))) ?></td>
                                <td><?= clinicalQueueEscape($visit['visit_type']) ?></td>
                                <td>
                                    <span class="clinical-status <?= $visit['visit_status'] === 'Waiting' ? 'clinical-badge-waiting' : 'clinical-badge-progress' ?>">
                                        <?= clinicalQueueEscape($visit['visit_status']) ?>
                                    </span>
                                </td>
                                <td><?= clinicalQueueEscape($visit['checked_in_by']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td class="clinical-empty" colspan="7">No active visits match the selected filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="clinical-queue-pages" aria-label="Waiting list pages">
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <div>
                    <?php if ($page > 1): ?>
                        <a href="<?= clinicalQueueEscape(clinicalQueueLink($page - 1, $search, $status)) ?>" class="clinical-queue-clear">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= clinicalQueueEscape(clinicalQueueLink($page + 1, $search, $status)) ?>" class="clinical-queue-button">Next</a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </section>

    <p class="clinical-bottom-note">
        Clinical actions will be added in the next stage. Starting a consultation must link the
        consultation to the correct Visit ID and update the visit status securely.
    </p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
