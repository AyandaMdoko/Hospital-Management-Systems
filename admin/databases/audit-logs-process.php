
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = "Audit Logs";

// Escape database values before displaying them.
function escapeAudit($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format log timestamps.
function formatAuditDate($date) {
    if (empty($date)) {
        return 'N/A';
    }

    $timestamp = strtotime($date);

    return $timestamp !== false
        ? date('d M Y, g:i A', $timestamp)
        : 'N/A';
}

// Read search input.
$search = isset($_GET['search'])
    && is_string($_GET['search'])
        ? trim($_GET['search'])
        : '';

$search = substr($search, 0, 100);

// Retrieve available entity types for filtering.
$typeStmt = $pdo->query("
    SELECT DISTINCT entity_type
    FROM audit_logs
    WHERE entity_type IS NOT NULL
      AND entity_type <> ''
    ORDER BY entity_type ASC
");

$entityTypes = $typeStmt->fetchAll(
    PDO::FETCH_COLUMN
);

// Read entity filter.
$entityType = isset($_GET['entity_type'])
    && is_string($_GET['entity_type'])
        ? $_GET['entity_type']
        : '';

if (
    $entityType !== '' &&
    !in_array($entityType, $entityTypes, true)
) {
    $entityType = '';
}

// Pagination settings.
$perPage = 10;

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);

$page = ($page && $page > 0) ? $page : 1;

// Build search conditions.
$conditions = [];
$params = [];

if ($search !== '') {

    $conditions[] = "
        (
            u.username LIKE ?
            OR a.action LIKE ?
            OR a.entity_type LIKE ?
        )
    ";

    $searchValue = '%' . $search . '%';

    $params[] = $searchValue;
    $params[] = $searchValue;
    $params[] = $searchValue;
}

if ($entityType !== '') {

    $conditions[] = "a.entity_type = ?";
    $params[] = $entityType;
}

$where = '';

if (!empty($conditions)) {
    $where = "WHERE " . implode(
        " AND ",
        $conditions
    );
}

// Count matching audit log records.
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM audit_logs a
    LEFT JOIN users u
        ON a.user_id = u.user_id
    $where
");

$countStmt->execute($params);

$totalLogs = (int)$countStmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalLogs / $perPage)
);

$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

// Retrieve audit logs.
$stmt = $pdo->prepare("
    SELECT
        a.log_id,
        a.user_id,
        a.action,
        a.entity_type,
        a.entity_id,
        a.created_at,
        u.username,
        u.role
    FROM audit_logs a
    LEFT JOIN users u
        ON a.user_id = u.user_id

    $where

    ORDER BY
        a.created_at DESC,
        a.log_id DESC

    LIMIT ? OFFSET ?
");

// Bind search parameters.
foreach ($params as $index => $value) {
    $stmt->bindValue(
        $index + 1,
        $value,
        PDO::PARAM_STR
    );
}

// Bind pagination values.
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

$auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>