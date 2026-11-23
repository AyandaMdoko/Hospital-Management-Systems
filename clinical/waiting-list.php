

<main class="clinical-main">
    <div class="clinical-page-heading">
        <div>
            <div class="clinical-eyebrow">SHARED CLINICAL WORKSPACE</div>
            <h1>Patient Waiting List</h1>
            <p>Patients checked in by Receptionists who are waiting for care or currently in consultation.</p>
        </div>
        <span class="clinical-read-only">Read-only overview</span>
    </div>

    <section class="clinical-stats" aria-label="Active visit statistics">
        <div class="clinical-stat">
            <span>Active Visits</span>
            <strong><?= (int) ($summary['active_total'] ?? 0) ?></strong>
            <small>Waiting or in consultation</small>
        </div>
        <div class="clinical-stat">
            <span>Waiting</span>
            <strong><?= (int) ($summary['waiting_total'] ?? 0) ?></strong>
            <small>Not yet being attended to</small>
        </div>
        <div class="clinical-stat">
            <span>In Consultation</span>
            <strong><?= (int) ($summary['consultation_total'] ?? 0) ?></strong>
            <small>Care currently in progress</small>
        </div>
        <div class="clinical-stat">
            <span>Older Active Visits</span>
            <strong><?= (int) ($summary['older_active_total'] ?? 0) ?></strong>
            <small>Checked in before today</small>
        </div>
    </section>

    <?php if ((int) ($summary['older_active_total'] ?? 0) > 0): ?>
        <div class="clinical-notice" role="status">
            <strong>Earlier visits still need review.</strong>
            Some patients checked in before today and remain Waiting or In Consultation.
            Their visits are shown below; clinical staff will resolve their statuses in the consultation module.
        </div>
    <?php endif; ?>

    <section class="clinical-card">
        <div class="clinical-card-heading">
            <div>
                <h2>Active Patient Visits</h2>
                <p><?= $matchingVisits ?> matching visit<?= $matchingVisits === 1 ? '' : 's' ?>.
                    Oldest waiting patients appear first.</p>
            </div>
            <span class="clinical-note-pill">Read-only</span>
        </div>

        <form class="clinical-queue-filters" method="get" action="/clinical/waiting-list.php" role="search">
            <div class="clinical-queue-field">
                <label for="clinical-visit-search">Search patient or visit</label>
                <input type="search" name="search" id="clinical-visit-search"
                       placeholder="Patient name, Patient ID or Visit ID"
                       value="<?= clinicalQueueEscape($search) ?>" maxlength="100">
            </div>
            <div class="clinical-queue-field">
                <label for="clinical-status-filter">Visit status</label>
                <select name="status" id="clinical-status-filter">
                    <option value="" <?= $status === '' ? 'selected' : '' ?>>All active statuses</option>
                    <option value="Waiting" <?= $status === 'Waiting' ? 'selected' : '' ?>>Waiting</option>
                    <option value="In Consultation" <?= $status === 'In Consultation' ? 'selected' : '' ?>>In Consultation</option>
                </select>
            </div>
            <div class="clinical-queue-controls">
                <button type="submit" class="clinical-queue-button">Filter</button>
                <?php if ($search !== '' || $status !== ''): ?>
                    <a href="/clinical/waiting-list.php" class="clinical-queue-clear">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="clinical-table-scroll">
            <table class="clinical-table">
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Patient</th>
                        <th>Patient ID</th>
                        <th>Checked In</th>
                        <th>Visit Category</th>
                        <th>Visit Status</th>
                        <th>Checked In By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($visits): ?>
                        <?php foreach ($visits as $visit): ?>
                            <tr>
                                <td>#<?= (int) $visit['visit_id'] ?></td>
                                <td><strong><?= clinicalQueueEscape($visit['first_name'] . ' ' . $visit['last_name']) ?></strong></td>
                                <td>#<?= (int) $visit['patient_id'] ?></td>
                                <td><?= clinicalQueueEscape(date('d M Y, h:i A', strtotime($visit['check_in_at']))) ?></td>
                                <td><?= clinicalQueueEscape($visit['visit_type']) ?></td>
                                <td>
                                    <span class="clinical-status <?= $visit['visit_status'] === 'Waiting' ? 'clinical-badge-waiting' : 'clinical-badge-progress' ?>">
                                        <?= clinicalQueueEscape($visit['visit_status']) ?>
                                    </span>
                                </td>
                                <td><?= clinicalQueueEscape($visit['checked_in_by']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td class="clinical-empty" colspan="7">No active visits match the selected filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="clinical-queue-pages" aria-label="Waiting list pages">
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <div>
                    <?php if ($page > 1): ?>
                        <a href="<?= clinicalQueueEscape(clinicalQueueLink($page - 1, $search, $status)) ?>" class="clinical-queue-clear">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= clinicalQueueEscape(clinicalQueueLink($page + 1, $search, $status)) ?>" class="clinical-queue-button">Next</a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </section>

    <p class="clinical-bottom-note">
        Clinical actions will be added in the next stage. Starting a consultation must link the
        consultation to the correct Visit ID and update the visit status securely.
    </p>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
