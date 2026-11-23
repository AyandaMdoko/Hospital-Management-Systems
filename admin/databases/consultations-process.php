
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = "Consultation Management";

// Escape values before displaying them.
function escapeConsultation($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format consultation date.
function formatConsultationDate($date) {
    if (empty($date)) {
        return 'N/A';
    }

    $timestamp = strtotime($date);

    return $timestamp !== false
        ? date('d M Y, g:i A', $timestamp)
        : 'N/A';
}

// Read search and filter parameters.
$search = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search'])
    : '';

$search = substr($search, 0, 100);

$status = isset($_GET['status']) && is_string($_GET['status'])
    ? $_GET['status']
    : '';

$allowedStatuses = ['Completed', 'In Progress'];

if (
    $status !== '' &&
    !in_array($status, $allowedStatuses, true)
) {
    $status = '';
}

// Pagination.
$perPage = 10;

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);

$page = ($page && $page > 0) ? $page : 1;

// Build conditions and parameters.
$where = "WHERE 1 = 1";
$params = [];

if ($search !== '') {
    $where .= "
        AND (
            p.first_name LIKE ?
            OR p.last_name LIKE ?
            OR CONCAT(p.first_name, ' ', p.last_name) LIKE ?
            OR s.first_name LIKE ?
            OR s.last_name LIKE ?
            OR CONCAT(s.first_name, ' ', s.last_name) LIKE ?
        )
    ";

    $searchTerm = '%' . $search . '%';

    for ($i = 0; $i < 6; $i++) {
        $params[] = $searchTerm;
    }
}

if ($status !== '') {
    $where .= " AND c.consultation_status = ?";
    $params[] = $status;
}

// Count matching consultations.
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM consultations c
    INNER JOIN patients p
        ON c.patient_id = p.patient_id
    INNER JOIN staff s
        ON c.staff_id = s.staff_id
    $where
");

$countStmt->execute($params);

$totalConsultations = (int)$countStmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalConsultations / $perPage)
);

$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

// Retrieve consultations.
$stmt = $pdo->prepare("
    SELECT
        c.consultation_id,
        c.consultation_date,
        c.consultation_status,

        p.patient_id,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,

        s.first_name AS staff_first_name,
        s.last_name AS staff_last_name,
        s.professional_role

    FROM consultations c

    INNER JOIN patients p
        ON c.patient_id = p.patient_id

    INNER JOIN staff s
        ON c.staff_id = s.staff_id

    $where

    ORDER BY
        c.consultation_date DESC,
        c.consultation_id DESC

    LIMIT ? OFFSET ?
");

// Bind filter parameters.
foreach ($params as $index => $value) {
    $stmt->bindValue(
        $index + 1,
        $value,
        PDO::PARAM_STR
    );
}

// Bind pagination parameters.
$stmt->bindValue(
    count($params) + 1,
    $perPage,
    PDO::PARAM_INT
);

$stmt->bindValue(
    count($params) + 2,
    $offset,
    PDO::PARAM_INT
);

$stmt->execute();

$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>