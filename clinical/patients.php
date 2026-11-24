<?php
/** Clinical patient finder: active visits or clinician's own previous consultations only. */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';

header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

$page_title = 'Patient Medical Records';

function pmH($v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$staffId = filter_var($_SESSION['staff_id'] ?? null, FILTER_VALIDATE_INT);

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

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="clinical-main pm-page">
    <div class="clinical-page-heading">
        <div>
            <div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
            <h1>Patient Medical Records</h1>
            <p>Find patients for whom you have an active clinical-care relationship.</p>
        </div>
    </div>

    <section class="clinical-card pm-card">
        <div class="clinical-card-heading">
            <div>
                <h2>Find Patient</h2>
                <p>Search by patient name or internal Patient ID. Results are limited to active clinic visits and patients you previously treated.</p>
            </div>
        </div>

        <form method="get" action="/clinical/patients.php" class="pm-search">
            <div>
                <label for="pm-search">Patient name or ID</label>
                <input
                    id="pm-search"
                    type="search"
                    name="search"
                    maxlength="80"
                    placeholder="e.g. Demo ClinicalTest or 12"
                    value="<?= pmH($search) ?>"
                >
            </div>
            <button class="pm-btn pm-primary" type="submit">Search</button>
            <?php if ($search !== ''): ?>
                <a class="pm-btn pm-secondary" href="/clinical/patients.php">Clear</a>
            <?php endif; ?>
        </form>

        <div class="pm-result-count" role="status">
            <?= $total ?> accessible patient<?= $total === 1 ? '' : 's' ?> found.
        </div>

        <div class="clinical-table-scroll">
            <table class="clinical-table pm-table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Patient</th>
                        <th>Most Recent Visit</th>
                        <th>Latest Visit Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$patients): ?>
                        <tr>
                            <td colspan="5">No matching patients with authorized clinical access were found.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($patients as $p): ?>
                        <tr>
                            <td>#<?= (int) $p['patient_id'] ?></td>
                            <td><strong><?= pmH($p['first_name'] . ' ' . $p['last_name']) ?></strong></td>
                            <td><?= $p['last_visit'] ? pmH(date('d M Y, h:i A', strtotime($p['last_visit']))) : 'No visit' ?></td>
                            <td><?= pmH($p['last_status'] ?? 'No visit') ?></td>
                            <td>
                                <a
                                    class="pm-btn pm-secondary"
                                    href="/clinical/patient-details.php?patient_id=<?= (int) $p['patient_id'] ?>"
                                >
                                    View Medical History
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="pm-pages" aria-label="Patient results pages">
                <?php if ($page > 1): ?>
                    <a href="<?= pmH(pmUrl($page - 1, $search)) ?>">Previous</a>
                <?php endif; ?>
                <span>Page <?= $page ?> of <?= $pages ?></span>
                <?php if ($page < $pages): ?>
                    <a href="<?= pmH(pmUrl($page + 1, $search)) ?>">Next</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>

    <p class="pm-footnote">
        Clinical records are confidential. Every medical-history page view is recorded in the audit log.
        If a patient is not listed, use the established check-in workflow rather than accessing their record without a care relationship.
    </p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
