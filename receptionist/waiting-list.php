<?php
require_once __DIR__ . '/databases/waiting-list-process.php';
?>

<main class="main-content">
    <div class="page-heading">
        <div>
            <h1>Today's Waiting List</h1>
            <p>View patient visits registered on <?= escapeWaiting($todayLabel) ?>.</p>
        </div></br>
        <a href="patients.php" class="primary-btn">Find Patient to Check In</a>
    </div>

    <?php if ($olderActiveVisits > 0): ?>
        <div class="form-message error-message" role="status">
            <?= $olderActiveVisits ?> active visit(s) from earlier dates still need review.
            These are not included in today's list and may prevent a new check-in.
            Ask an authorized clinician to resolve them; do not create duplicate visits.
        </div>
    <?php endif; ?>

    <div class="waiting-list-stats">
        <div class="waiting-list-stat-card">
            <span>Total Check-Ins Today</span>
            <strong><?= $todayCounts['total'] ?></strong>
        </div>
        <div class="waiting-list-stat-card">
            <span>Waiting</span>
            <strong><?= $todayCounts['waiting'] ?></strong>
        </div>
        <div class="waiting-list-stat-card">
            <span>In Consultation</span>
            <strong><?= $todayCounts['in_consultation'] ?></strong>
        </div>
        <div class="waiting-list-stat-card">
            <span>Completed</span>
            <strong><?= $todayCounts['completed'] ?></strong>
        </div>
    </div>

    <section class="content-card">
        <div class="card-heading">
            <div>
                <h3>Patient Visits</h3>
                <p class="receptionist-card-description">
                    <?= $matchingVisits ?> matching visit(s) today.
                    Cancelled visits also appear in the list when unfiltered.
                </p>
            </div>
        </div>

        <form class="waiting-list-filters" action="waiting-list.php" method="GET">
            <div class="form-group">
                <label for="search">Search patient or visit</label>
                <input id="search" type="search" name="search"
                       placeholder="Patient name, Patient ID, or Visit ID"
                       value="<?= escapeWaiting($search) ?>" maxlength="100">
            </div>
            <div class="form-group">
                <label for="status">Visit status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    <?php foreach ($allowedStatuses as $status): ?>
                        <option value="<?= escapeWaiting($status) ?>"
                            <?= $selectedStatus === $status ? 'selected' : '' ?>>
                            <?= escapeWaiting($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="primary-btn">Filter</button>
            <?php if ($search !== '' || $selectedStatus !== ''): ?>
                <a href="waiting-list.php" class="cancel-btn">Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-container">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Patient ID</th>
                        <th>Patient</th>
                        <th>Arrival</th>
                        <th>Visit Category</th>
                        <th>Status</th>
                        <th>Checked In By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($visits): ?>
                        <?php foreach ($visits as $visit): ?>
                            <?php
                            $statusCss = strtolower(str_replace(' ', '-', $visit['visit_status']));
                            ?>
                            <tr>
                                <td>#<?= (int) $visit['visit_id'] ?></td>
                                <td>#<?= (int) $visit['patient_id'] ?></td>
                                <td><?= escapeWaiting($visit['first_name'] . ' ' . $visit['last_name']) ?></td>
                                <td><?= escapeWaiting(date('h:i A', strtotime($visit['check_in_at']))) ?></td>
                                <td><?= escapeWaiting($visit['visit_type']) ?></td>
                                <td>
                                    <span class="visit-status-badge visit-<?= escapeWaiting($statusCss) ?>">
                                        <?= escapeWaiting($visit['visit_status']) ?>
                                    </span>
                                </td>
                                <td><?= escapeWaiting($visit['checked_in_by_username']) ?></td>
                                <td>
                                    <a class="patient-view-btn"
                                       href="patient-details.php?id=<?= (int) $visit['patient_id'] ?>">
                                        View Patient
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <?= ($search !== '' || $selectedStatus !== '')
                                    ? 'No visits match your search or selected status for today.'
                                    : 'No patients have checked in today.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="waiting-list-pagination">
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <div class="waiting-list-pagination-actions">
                    <?php if ($page > 1): ?>
                        <a class="cancel-btn"
                           href="<?= escapeWaiting(waitingPageUrl($page - 1, $search, $selectedStatus)) ?>">
                            Previous
                        </a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="primary-btn"
                           href="<?= escapeWaiting(waitingPageUrl($page + 1, $search, $selectedStatus)) ?>">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
