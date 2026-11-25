<?php
/**
 * Shared Clinical Observations: scoped patient finder and read-only observations.
 * Recording takes place separately, and only Professional Nurses may record.
 */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
$page_title = 'Clinical Observations';

function obH($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function obDate($value): string
{
    return $value
        ? obH(date('d M Y, h:i A', strtotime((string)$value)))
        : 'Not recorded';
}

function obAudit(PDO $db, int $uid, string $action, int $patientId): void
{
    $s = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)');
    $s->execute([$uid, $action, 'Patient', $patientId]);
}

$staffId = filter_var($_SESSION['staff_id'] ?? null, FILTER_VALIDATE_INT);
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);
$isNurse = ($_SESSION['role'] ?? null) === 'Nurse';
if (!$staffId || !$userId) {
    http_response_code(403);
    exit('An active clinical account is required.');
}

$patientId = null;
if (array_key_exists('patient_id', $_GET)) {
    $patientId = filter_var($_GET['patient_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$patientId) {
        http_response_code(400);
        exit('A valid Patient ID is required.');
    }
}

// These conditions are checked server-side for every request. Viewing another
// clinician's history is permitted only with an active visit or an established
// clinical relationship (own consultation or own observation).
$relationship = "(
    EXISTS (SELECT 1 FROM patient_visits v WHERE v.patient_id = p.patient_id AND v.visit_status IN ('Waiting','In Consultation'))
    OR EXISTS (SELECT 1 FROM consultations c WHERE c.patient_id = p.patient_id AND c.staff_id = ?)
    OR EXISTS (SELECT 1 FROM nursing_observations n WHERE n.patient_id = p.patient_id AND n.staff_id = ?)
)";

$patient = null;
$observations = [];
$activeVisits = [];
$patients = [];
$canViewHistory = false;
$flash = $_SESSION['clinical_observation_flash'] ?? null;
unset($_SESSION['clinical_observation_flash']);
$search = '';
$total = 0;
$page = 1;
$pages = 1;

if ($patientId !== null) {
    $s = $pdo->prepare("SELECT p.patient_id, p.first_name, p.last_name, p.date_of_birth
        FROM patients p WHERE p.patient_id = ? AND $relationship LIMIT 1");
    $s->execute([$patientId, $staffId, $staffId]);
    $patient = $s->fetch(PDO::FETCH_ASSOC);
    if (!$patient) {
        http_response_code(404);
        exit('Patient not found or no authorized clinical relationship.');
    }

    // Fail closed: do not reveal individual observations when the access audit fails.
    try {
        obAudit($pdo, (int)$userId, 'Viewed patient clinical observations', (int)$patientId);
    } catch (Throwable $e) {
        error_log('Observation history audit failed: ' . $e->getMessage());
        http_response_code(503);
        exit('Unable to log clinical record access. Please contact the administrator.');
    }

    $s = $pdo->prepare("SELECT n.observation_id, n.observation_date, n.observation_type,
        n.observation_value, n.notes, s.first_name AS staff_first, s.last_name AS staff_last,
        s.professional_role FROM nursing_observations n
        JOIN staff s ON s.staff_id = n.staff_id WHERE n.patient_id = ?
        ORDER BY n.observation_date DESC, n.observation_id DESC LIMIT 50");
    $s->execute([$patientId]);
    $observations = $s->fetchAll(PDO::FETCH_ASSOC);

    $s = $pdo->prepare("SELECT visit_id, check_in_at, visit_status FROM patient_visits
        WHERE patient_id = ? AND visit_status IN ('Waiting','In Consultation')
        ORDER BY check_in_at DESC, visit_id DESC LIMIT 5");
    $s->execute([$patientId]);
    $activeVisits = $s->fetchAll(PDO::FETCH_ASSOC);
    if ($activeVisits) {
        $canViewHistory = true;
    } else {
        $s = $pdo->prepare('SELECT 1 FROM consultations WHERE patient_id = ? AND staff_id = ? LIMIT 1');
        $s->execute([$patientId, $staffId]);
        $canViewHistory = (bool)$s->fetchColumn();
    }
} else {
    $search = $_GET['search'] ?? '';
    $search = is_string($search) ? trim($search) : '';
    $search = function_exists('mb_substr') ? mb_substr($search, 0, 80, 'UTF-8') : substr($search, 0, 80);
    $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
    $page = $page && $page > 0 ? min($page, 100000) : 1;
    $perPage = 10;
    $params = [$staffId, $staffId];
    $extra = '';
    if ($search !== '') {
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
        $lookups = ["p.first_name LIKE ? ESCAPE '!'", "p.last_name LIKE ? ESCAPE '!'",
                    "CONCAT(p.first_name, ' ', p.last_name) LIKE ? ESCAPE '!'"];
        array_push($params, $like, $like, $like);
        if (ctype_digit($search)) {
            $number = filter_var($search, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($number !== false) {
                $lookups[] = 'p.patient_id = ?';
                $params[] = $number;
            }
        }
        $extra = ' AND (' . implode(' OR ', $lookups) . ')';
    }
    $where = "WHERE $relationship $extra";
    $s = $pdo->prepare("SELECT COUNT(*) FROM patients p $where");
    $s->execute($params);
    $total = (int)$s->fetchColumn();
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;
    $s = $pdo->prepare("SELECT p.patient_id, p.first_name, p.last_name,
        (SELECT v.visit_status FROM patient_visits v WHERE v.patient_id = p.patient_id
            ORDER BY v.check_in_at DESC, v.visit_id DESC LIMIT 1) AS latest_visit_status
        FROM patients p $where ORDER BY p.last_name, p.first_name, p.patient_id
        LIMIT $perPage OFFSET $offset");
    $s->execute($params);
    $patients = $s->fetchAll(PDO::FETCH_ASSOC);

    // Each listed patient is an identifiable healthcare record. Log these
    // scoped lookup disclosures before rendering their names.
    try {
        foreach ($patients as $p) {
            obAudit($pdo, (int)$userId, 'Listed patient for clinical observations', (int)$p['patient_id']);
        }
    } catch (Throwable $e) {
        error_log('Observation patient search audit failed: ' . $e->getMessage());
        http_response_code(503);
        exit('Unable to log patient search. Please contact the administrator.');
    }
}
function obFindUrl(int $p, string $search): string
{
    return '/clinical/observations.php?' . http_build_query(['search' => $search, 'page' => $p]);
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>