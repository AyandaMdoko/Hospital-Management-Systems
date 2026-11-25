<?php
/**
 * Shared Clinical Observations: scoped patient finder and read-only observations.
 * Recording takes place separately, and only Professional Nurses may record.
 */
require_once __DIR__ . '/../includes/clinical-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';
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
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<main class="clinical-main ob-page">
  <div class="clinical-page-heading">
    <div>
      <div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
      <h1>
        <?= $patient
          ? obH($patient['first_name'] . ' ' . $patient['last_name']) . ' — Observations'
          : 'Clinical Observations'
        ?>
      </h1>
      <p>
        <?= $patient
          ? 'Patient #' . (int)$patientId . ' · Read-only observation history'
          : 'Review observations for patients under your authorized care.'
        ?>
      </p>
    </div>
    <?php if ($patient): ?>
      <a class="ob-btn ob-btn-outline" href="/clinical/observations.php">Back to Observation Search</a>
    <?php endif; ?>
  </div>

  <?php if ($patient && is_string($flash) && $flash !== ''): ?>
    <div class="ob-alert ob-alert-success" role="status"><?= obH($flash) ?></div>
  <?php endif; ?>

  <?php if (!$patient): ?>
    <section class="clinical-card ob-card">
      <div class="clinical-card-heading">
        <div>
          <h2>Find Patient</h2>
          <p>Search by name or internal Patient ID. Results require an active visit or a documented clinical-care relationship.</p>
        </div>
      </div>
      <form method="get" action="/clinical/observations.php" class="ob-search">
        <div>
          <label for="ob-search-name">Patient name or ID</label>
          <input id="ob-search-name" type="search" name="search" maxlength="80" value="<?= obH($search) ?>" placeholder="e.g. Demo ClinicalTest or 12">
        </div>
        <button type="submit" class="ob-btn ob-btn-primary">Search</button>
        <?php if ($search !== ''): ?>
          <a class="ob-btn ob-btn-outline" href="/clinical/observations.php">Clear</a>
        <?php endif; ?>
      </form>
      <p class="ob-muted" role="status"><?= $total ?> accessible patient<?= $total === 1 ? '' : 's' ?> found.</p>
      <div class="clinical-table-scroll">
        <table class="clinical-table ob-table">
          <thead>
            <tr>
              <th>Patient ID</th>
              <th>Patient</th>
              <th>Most Recent Visit Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$patients): ?>
              <tr>
                <td colspan="4">No authorized patients matched your search.</td>
              </tr>
            <?php endif; ?>
            <?php foreach ($patients as $p): ?>
              <tr>
                <td>#<?= (int)$p['patient_id'] ?></td>
                <td><strong><?= obH($p['first_name'] . ' ' . $p['last_name']) ?></strong></td>
                <td><?= obH($p['latest_visit_status'] ?? 'No visit') ?></td>
                <td>
                  <a class="ob-btn ob-btn-outline" href="/clinical/observations.php?patient_id=<?= (int)$p['patient_id'] ?>">View Observations</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($pages > 1): ?>
        <nav class="ob-pages" aria-label="Observation patient search pages">
          <?php if ($page > 1): ?>
            <a href="<?= obH(obFindUrl($page - 1, $search)) ?>">Previous</a>
          <?php endif; ?>
          <span>Page <?= $page ?> of <?= $pages ?></span>
          <?php if ($page < $pages): ?>
            <a href="<?= obH(obFindUrl($page + 1, $search)) ?>">Next</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </section>
    <p class="ob-footnote">Both clinical roles can read authorized observation histories.
      Only Professional Nurses can create new observations during an active visit.</p>
  <?php else: ?>
    <section class="clinical-card ob-card">
      <div class="clinical-card-heading">
        <div>
          <h2>Patient Observation Record</h2>
          <p>
            Patient #<?= (int)$patientId ?> ·
            Date of birth: <?= obH($patient['date_of_birth'] ?: 'Not recorded') ?>
          </p>
        </div>
      </div>

      <?php if ($isNurse && $activeVisits): ?>
        <div class="ob-visit-box">
          <strong>Record a new observation</strong>
          <p>Choose the active check-in for this patient. Completed visits cannot receive new entries.</p>
          <div class="ob-visit-links">
            <?php foreach ($activeVisits as $v): ?>
              <a class="ob-btn ob-btn-primary" href="/clinical/record-observation.php?visit_id=<?= (int)$v['visit_id'] ?>">
                Record for Visit #<?= (int)$v['visit_id'] ?> · <?= obH($v['visit_status']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php elseif ($isNurse): ?>
        <p class="ob-notice">No active visit exists for this patient. Ask a Receptionist to complete a new check-in before recording an observation.</p>
      <?php else: ?>
        <p class="ob-notice">Read-only access. Observation entry is reserved for the Professional Nurse role.</p>
      <?php endif; ?>

      <div class="clinical-table-scroll">
        <table class="clinical-table ob-table">
          <thead>
            <tr>
              <th>Recorded</th>
              <th>Observation</th>
              <th>Value</th>
              <th>Notes</th>
              <th>Recorded by</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$observations): ?>
              <tr>
                <td colspan="5">No observations have been recorded for this patient.</td>
              </tr>
            <?php endif; ?>
            <?php foreach ($observations as $o): ?>
              <tr>
                <td><?= obDate($o['observation_date']) ?></td>
                <td><?= obH($o['observation_type']) ?></td>
                <td><strong><?= obH($o['observation_value']) ?></strong></td>
                <td class="ob-notes"><?= nl2br(obH($o['notes'] ?: '—')) ?></td>
                <td>
                  <?= obH($o['staff_first'] . ' ' . $o['staff_last']) ?>
                  (<?= obH($o['professional_role']) ?>)
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php if ($canViewHistory): ?>
      <div class="ob-actions-row">
        <a class="ob-btn ob-btn-outline" href="/clinical/patient-details.php?patient_id=<?= (int)$patientId ?>">View Full Medical History</a>
      </div>
    <?php endif; ?>
    <p class="ob-footnote">All observations are read-only after recording. If a correction is needed, use the clinic's authorized amendment process; do not silently overwrite clinical information.</p>
  <?php endif; ?>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
