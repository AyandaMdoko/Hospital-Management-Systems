<?php
/**
 * Shared Clinical workspace: read-only completed consultation history.
 * Access matches clinical/patient-details.php: active visit or clinician's
 * earlier consultation. No data changes and no access to draft notes.
 */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
$page_title = 'Consultation History';
function chH($v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function chDate($v): string
{
    return $v ? chH(date('d M Y, h:i A', strtotime((string)$v))) : 'Not recorded';
}

$staffId = filter_var($_SESSION['staff_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$staffId || !$userId) {
    http_response_code(403);
    exit('An active clinical account is required.');
}

$search = $_GET['search'] ?? '';
$search = is_string($search) ? trim($search) : '';
$search = function_exists('mb_substr') ? mb_substr($search, 0, 80, 'UTF-8') : substr($search, 0, 80);
$role = $_GET['role'] ?? '';
$role = is_string($role) && in_array($role, ['', 'Doctor', 'Nurse'], true) ? $role : '';
$mine = (isset($_GET['mine']) && $_GET['mine'] === '1') ? true : false;
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? min($page, 100000) : 1;
$perPage = 10;

// Every displayed consultation belongs to a patient whom this clinician is
// currently caring for (active visit) or has personally treated before.
$conditions = ["c.consultation_status = 'Completed'",
    "(EXISTS (SELECT 1 FROM patient_visits av WHERE av.patient_id = p.patient_id AND av.visit_status IN ('Waiting', 'In Consultation'))
      OR EXISTS (SELECT 1 FROM consultations myc WHERE myc.patient_id = p.patient_id AND myc.staff_id = ?))"];
$params = [(int)$staffId];
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
    $conditions[] = '(' . implode(' OR ', $lookups) . ')';
}
if ($role !== '') {
    $conditions[] = 's.professional_role = ?';
    $params[] = $role;
}
if ($mine) {
    $conditions[] = 'c.staff_id = ?';
    $params[] = (int)$staffId;
}
$where = 'WHERE ' . implode(' AND ', $conditions);
$join = 'FROM consultations c JOIN patients p ON p.patient_id = c.patient_id JOIN staff s ON s.staff_id = c.staff_id';

$countStmt = $pdo->prepare("SELECT COUNT(*) $join $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare("SELECT c.consultation_id, c.patient_id, c.visit_id, c.consultation_date,
    p.first_name AS patient_first, p.last_name AS patient_last,
    s.first_name AS clinician_first, s.last_name AS clinician_last, s.professional_role
    $join $where ORDER BY c.consultation_date DESC, c.consultation_id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Log each disclosed consultation to provide patient-level traceability. Fail
// closed if auditing is unavailable; no unlogged clinical metadata disclosure.
try {
    $audit = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)');
    foreach ($consultations as $c) {
        $audit->execute([(int)$userId, 'Listed completed consultation in clinical history', 'Consultation', (int)$c['consultation_id']]);
    }
} catch (Throwable $e) {
    error_log('Consultation history list audit failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Unable to log consultation history access. Please contact the administrator.');
}
function chUrl(int $p, string $search, string $role, bool $mine): string {
    return '/clinical/consultation-history.php?' . http_build_query(['search' => $search, 'role' => $role, 'mine' => $mine ? '1' : '0', 'page' => $p]);
}
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="clinical-main ch-page">
  <div class="clinical-page-heading">
    <div>
      <div class="clinical-eyebrow">
        SHARED CLINICAL WORKSPACE
      </div>
      <h1>Consultation History</h1>
      <p>Read-only completed consultations for patients under your authorized care.</p>
    </div>
  </div>
  <section class="clinical-card ch-card">
    <div class="clinical-card-heading">
      <div>
        <h2>Find Consultations</h2>
        <p>Search patient name or ID, filter by attending professional, or show only consultations you performed.</p>
      </div>
    </div>
    <form method="get" action="/clinical/consultation-history.php" class="ch-search">
      <div class="ch-search-text">
        <label for="ch-search">Patient name or ID</label>
        <input id="ch-search" type="search" name="search" maxlength="80" placeholder="e.g. Demo ClinicalTest or 12" value="<?= chH($search) ?>">
      </div>
      <div>
        <label for="ch-role">Attending professional</label>
        <select id="ch-role" name="role">
          <option value=""<?= $role === '' ? ' selected' : '' ?>>Doctor and Nurse</option>
          <option value="Doctor"<?= $role === 'Doctor' ? ' selected' : '' ?>>Doctor</option>
          <option value="Nurse"<?= $role === 'Nurse' ? ' selected' : '' ?>>Nurse</option>
        </select>
      </div>
      <label class="ch-mine"><input type="checkbox" name="mine" value="1"<?= $mine ? ' checked' : '' ?>> My consultations only</label>
      <button class="ch-btn ch-primary" type="submit">Search</button>
      <?php if ($search !== '' || $role !== '' || $mine): ?>
        <a class="ch-btn ch-secondary" href="/clinical/consultation-history.php">Clear</a>
      <?php endif; ?>
    </form>
    <div class="ch-total" role="status">
      <?= $total ?> completed consultation<?= $total === 1 ? '' : 's' ?> found.
    </div>
    <div class="clinical-table-scroll">
      <table class="clinical-table ch-table">
        <thead>
          <tr>
            <th>Consultation</th>
            <th>Patient</th>
            <th>Date</th>
            <th>Professional</th>
            <th>Visit</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$consultations): ?>
            <tr>
              <td colspan="6">No completed consultations matched your authorized search.</td>
            </tr>
          <?php endif; ?>
          <?php foreach ($consultations as $c): ?>
            <tr>
              <td><strong>#<?= (int)$c['consultation_id'] ?></strong></td>
              <td><?= chH($c['patient_first'] . ' ' . $c['patient_last']) ?><span class="ch-subline">Patient #<?= (int)$c['patient_id'] ?></span></td>
              <td><?= chDate($c['consultation_date']) ?></td>
              <td><?= chH($c['clinician_first'] . ' ' . $c['clinician_last']) ?><span class="ch-subline"><?= chH($c['professional_role']) ?></span></td>
              <td><?= $c['visit_id'] ? '#' . (int)$c['visit_id'] : 'Legacy record' ?></td>
              <td><a class="ch-btn ch-secondary" href="/clinical/consultation-record.php?consultation_id=<?= (int)$c['consultation_id'] ?>">View Record</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
      <nav class="ch-pagination" aria-label="Consultation history pages">
        <?php if ($page > 1): ?>
          <a href="<?= chH(chUrl($page - 1, $search, $role, $mine)) ?>">Previous</a>
        <?php endif; ?>
        <span>Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?>
          <a href="<?= chH(chUrl($page + 1, $search, $role, $mine)) ?>">Next</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </section>
  <p class="ch-footnote">This list contains completed consultations only. In-progress notes remain in the current visit workflow and are not exposed here. Consultation history access is logged.</p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
