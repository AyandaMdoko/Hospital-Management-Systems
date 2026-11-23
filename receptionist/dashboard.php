<?php include_once __DIR__ . '/databases/dashboard-process.php'; ?>

<main class="main-content">
    <div class="page-heading">
        <div>
            <h1>Receptionist Dashboard</h1>
            <p>Patient registration and clinic activity for <?= escapeReceptionistDashboard($todayLabel) ?>.</p>
        </div>
    </div>

    <div class="receptionist-dashboard-actions">
        <a class="primary-btn" href="patients.php">Find Patient / Check In</a>
        <a class="cancel-btn" href="waiting-list.php">View Today's Waiting List</a>
    </div>

    <?php if ($olderActiveVisits > 0): ?>
        <div class="form-message error-message" role="status">
            <strong><?= $olderActiveVisits ?> older active visit(s) require review.</strong>
            These visits began before today and are not included in today's counts.
            Please ask authorized clinical staff to resolve them before another check-in is attempted.
        </div>
    <?php endif; ?>

    <section aria-label="Patient and visit statistics" class="receptionist-dashboard-stats">
        <div class="receptionist-dashboard-stat-card">
            <span>Total Registered Patients</span>
            <strong><?= $totalPatients ?></strong>
            <small>Permanent patient profiles</small>
        </div>
        <div class="receptionist-dashboard-stat-card">
            <span>New Patients Today</span>
            <strong><?= $patientsRegisteredToday ?></strong>
            <small>New permanent registrations</small>
        </div>
        <div class="receptionist-dashboard-stat-card">
            <span>Today's Check-Ins</span>
            <strong><?= $visitCounts['total'] ?></strong>
            <small>Includes cancelled check-ins</small>
        </div>
        <div class="receptionist-dashboard-stat-card">
            <span>Waiting</span>
            <strong><?= $visitCounts['waiting'] ?></strong>
            <small>Today's visits awaiting care</small>
        </div>
        <div class="receptionist-dashboard-stat-card">
            <span>In Consultation</span>
            <strong><?= $visitCounts['in_consultation'] ?></strong>
            <small>Today's visits in progress</small>
        </div>
        <div class="receptionist-dashboard-stat-card">
            <span>Completed</span>
            <strong><?= $visitCounts['completed'] ?></strong>
            <small>Completed among today's check-ins</small>
        </div>
    </section>

    <section class="content-card">
        <div class="card-heading">
            <div>
                <h3>Recent Check-Ins Today</h3>
                <p class="receptionist-card-description">Latest eight arrivals; visit status is view-only for Receptionists.</p>
            </div>
            <a class="patient-view-btn" href="waiting-list.php">View All Visits</a>
        </div>

        <div class="table-container">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Visit ID</th>
                        <th>Patient</th>
                        <th>Arrival</th>
                        <th>Visit Category</th>
                        <th>Status</th>
                        <th>Checked In By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentVisits): ?>
                        <?php foreach ($recentVisits as $visit): ?>
                            <?php
                            $statusCss = strtolower(str_replace(' ', '-', $visit['visit_status']));
                            ?>
                            <tr>
                                <td>#<?= (int) $visit['visit_id'] ?></td>
                                <td>
                                    <strong><?= escapeReceptionistDashboard($visit['first_name'] . ' ' . $visit['last_name']) ?></strong>
                                    <span class="receptionist-dashboard-subtext">Patient #<?= (int) $visit['patient_id'] ?></span>
                                </td>
                                <td><?= escapeReceptionistDashboard(date('h:i A', strtotime($visit['check_in_at']))) ?></td>
                                <td><?= escapeReceptionistDashboard($visit['visit_type']) ?></td>
                                <td>
                                    <span class="visit-status-badge visit-<?= escapeReceptionistDashboard($statusCss) ?>">
                                        <?= escapeReceptionistDashboard($visit['visit_status']) ?>
                                    </span>
                                </td>
                                <td><?= escapeReceptionistDashboard($visit['checked_in_by_username']) ?></td>
                                <td>
                                    <a class="patient-view-btn" href="patient-details.php?id=<?= (int) $visit['patient_id'] ?>">
                                        View Patient
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No patients have checked in today.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="content-card receptionist-dashboard-secondary-card">
        <div class="card-heading">
            <div>
                <h3>Recent Patient Registrations</h3>
                <p class="receptionist-card-description">The five most recently created permanent patient profiles.</p>
            </div>
            <a class="patient-view-btn" href="patients.php">View All Patients</a>
        </div>
        <div class="table-container">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Patient Name</th>
                        <th>Gender</th>
                        <th>Date Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentPatients): ?>
                        <?php foreach ($recentPatients as $patient): ?>
                            <tr>
                                <td>#<?= (int) $patient['patient_id'] ?></td>
                                <td><?= escapeReceptionistDashboard($patient['first_name'] . ' ' . $patient['last_name']) ?></td>
                                <td><?= escapeReceptionistDashboard($patient['gender'] ?: 'N/A') ?></td>
                                <td><?= escapeReceptionistDashboard(date('d M Y', strtotime($patient['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No patients have been registered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
