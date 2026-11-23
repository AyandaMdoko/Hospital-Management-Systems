<?php
/**
 * Shared Clinical Waiting List (read-only).
 * For active Doctors and Professional Nurses only.
 * Do not start consultations or update visit statuses from this page.
 */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

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

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>