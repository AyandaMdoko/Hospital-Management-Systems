<?php
/** Clinical patient finder: active visits or clinician's own previous consultations only. */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

$page_title = 'Patient Medical Records';

function pmH($v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$staffId = filter_var($_SESSION['staff_id'] ?? null, FILTER_VALIDATE_INT);

// Ensure the user is logged in and has an active clinical account
if (!$staffId) {
    http_response_code(403);
    exit('Active clinical account required.');
}

$search = $_GET['search'] ?? '';
$search = is_string($search) ? trim($search) : '';
$search = function_exists('mb_substr') ? mb_substr($search, 0, 80, 'UTF-8') : substr($search, 0, 80);

$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? min($page, 100000) : 1;
$perPage = 10;

$conditions = [
    "(EXISTS (
        SELECT 1
        FROM patient_visits av
        WHERE av.patient_id = p.patient_id
          AND av.visit_status IN ('Waiting', 'In Consultation')
    ) OR EXISTS (
        SELECT 1
        FROM consultations co
        WHERE co.patient_id = p.patient_id
          AND co.staff_id = ?
    ))"
];
$params = [(int) $staffId];

// If a search term is provided, add conditions for first name, last name, full name, and patient ID
if ($search !== '') {
    $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
    $lookups = [
        "p.first_name LIKE ? ESCAPE '!'",
        "p.last_name LIKE ? ESCAPE '!'",
        "CONCAT(p.first_name, ' ', p.last_name) LIKE ? ESCAPE '!'"
    ];
    array_push($params, $like, $like, $like);

    if (ctype_digit($search)) {
        $num = filter_var($search, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if ($num !== false) {
            $lookups[] = 'p.patient_id = ?';
            $params[] = $num;
        }
    }

    $conditions[] = '(' . implode(' OR ', $lookups) . ')';
}

$where = 'WHERE ' . implode(' AND ', $conditions);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM patients p $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

// Fetch the patient records with the most recent visit and latest visit status
$query = "SELECT p.patient_id, p.first_name, p.last_name,
    (SELECT MAX(v.check_in_at) FROM patient_visits v WHERE v.patient_id = p.patient_id) AS last_visit,
    (SELECT v.visit_status
     FROM patient_visits v
     WHERE v.patient_id = p.patient_id
     ORDER BY v.check_in_at DESC, v.visit_id DESC
     LIMIT 1) AS last_status
    FROM patients p
    $where
    ORDER BY p.last_name, p.first_name, p.patient_id
    LIMIT $perPage OFFSET $offset";
$rowsStmt = $pdo->prepare($query);
$rowsStmt->execute($params);
$patients = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

function pmUrl(int $page, string $search): string
{
    return '/clinical/patients.php?' . http_build_query(['search' => $search, 'page' => $page]);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>