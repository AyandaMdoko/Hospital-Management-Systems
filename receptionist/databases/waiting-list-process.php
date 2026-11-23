<?php
/**
 * Receptionist: Today's Waiting List (read-only controller).
 * Included by receptionist/waiting-list.php before any HTML output.
 */
require_once __DIR__ . '/../includes/receptionist-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = "Today's Waiting List";
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

function escapeWaiting($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$allowedStatuses = ['Waiting', 'In Consultation', 'Completed', 'Cancelled'];

$searchInput = $_GET['search'] ?? '';
$search = is_string($searchInput) ? trim(substr($searchInput, 0, 100)) : '';

$statusInput = $_GET['status'] ?? '';
$selectedStatus = is_string($statusInput) && in_array($statusInput, $allowedStatuses, true)
    ? $statusInput
    : '';

$pageInput = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = ($pageInput !== false && $pageInput !== null && $pageInput > 0)
    ? $pageInput
    : 1;
$perPage = 15;

// Today follows the MySQL server's date, consistent with check_in_at's default.
$dbToday = (string) $pdo->query('SELECT CURDATE()')->fetchColumn();
$todayLabel = date('d M Y', strtotime($dbToday));

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

$todayCounts = [
    'total' => (int) ($summary['total'] ?? 0),
    'waiting' => (int) ($summary['waiting'] ?? 0),
    'in_consultation' => (int) ($summary['in_consultation'] ?? 0),
    'completed' => (int) ($summary['completed'] ?? 0),
    'cancelled' => (int) ($summary['cancelled'] ?? 0),
];

// A check-in on a previous date can remain active and block a new check-in.
$olderActiveVisits = (int) $pdo->query("
    SELECT COUNT(*) FROM patient_visits
    WHERE check_in_at < CURDATE()
      AND visit_status IN ('Waiting', 'In Consultation')
")->fetchColumn();

$conditions = [
    'pv.check_in_at >= CURDATE()',
    'pv.check_in_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)',
];
$params = [];

if ($selectedStatus !== '') {
    $conditions[] = 'pv.visit_status = :status';
    $params[':status'] = $selectedStatus;
}

if ($search !== '') {
    $nameSearch = '%' . $search . '%';
    $searchParts = [
        'p.first_name LIKE :first_name',
        'p.last_name LIKE :last_name',
        "CONCAT(p.first_name, ' ', p.last_name) LIKE :full_name",
    ];
    $params[':first_name'] = $nameSearch;
    $params[':last_name'] = $nameSearch;
    $params[':full_name'] = $nameSearch;

    // Exact numeric lookup for the system Patient ID or Visit ID.
    if (ctype_digit($search) && strlen($search) <= 10) {
        $searchParts[] = 'pv.visit_id = :visit_id';
        $searchParts[] = 'pv.patient_id = :patient_id';
        $params[':visit_id'] = $search;
        $params[':patient_id'] = $search;
    }
    $conditions[] = '(' . implode(' OR ', $searchParts) . ')';
}

$whereSql = 'WHERE ' . implode(' AND ', $conditions);

$countSql = "
    SELECT COUNT(*)
    FROM patient_visits pv
    INNER JOIN patients p ON p.patient_id = pv.patient_id
    $whereSql
";
$countStmt = $pdo->prepare($countSql);
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
        u.username AS checked_in_by_username
    FROM patient_visits pv
    INNER JOIN patients p ON p.patient_id = pv.patient_id
    INNER JOIN users u ON u.user_id = pv.checked_in_by
    $whereSql
    ORDER BY pv.check_in_at ASC, pv.visit_id ASC
    LIMIT :limit OFFSET :offset
";
$listStmt = $pdo->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value, PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$visits = $listStmt->fetchAll(PDO::FETCH_ASSOC);

function waitingPageUrl(int $targetPage, string $search, string $status): string
{
    return 'waiting-list.php?' . http_build_query([
        'search' => $search,
        'status' => $status,
        'page' => $targetPage,
    ]);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
