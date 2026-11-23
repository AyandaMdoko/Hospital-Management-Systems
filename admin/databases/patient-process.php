
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = "Patient Management";

// Securely display database values.
function escapePatient($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Retrieve the search term.
$search = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search'])
    : '';

$search = substr($search, 0, 100);

// Pagination settings.
$perPage = 10;

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);

$page = ($page && $page > 0) ? $page : 1;

// Prepare the search condition.
$where = '';
$params = [];

if ($search !== '') {
    $where = "
        WHERE first_name LIKE ?
           OR last_name LIKE ?
           OR CONCAT(first_name, ' ', last_name) LIKE ?
           OR id_number LIKE ?
    ";

    $searchValue = '%' . $search . '%';

    $params = [
        $searchValue,
        $searchValue,
        $searchValue,
        $searchValue
    ];
}

// Count matching patients.
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM patients
    $where
");

$countStmt->execute($params);

$totalPatients = (int)$countStmt->fetchColumn();

$totalPages = max(
    1,
    (int)ceil($totalPatients / $perPage)
);

$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

// Retrieve patients.
$stmt = $pdo->prepare("
    SELECT
        patient_id,
        id_number,
        first_name,
        last_name,
        gender,
        contact_number,
        created_at
    FROM patients
    $where
    ORDER BY created_at DESC, patient_id DESC
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

$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>