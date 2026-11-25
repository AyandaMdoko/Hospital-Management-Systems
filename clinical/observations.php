

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
