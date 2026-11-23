<?php include_once __DIR__ . '/databases/availability-process.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <h1>Doctor Availability</h1>
        <p>
            View and manage doctors' weekly working schedules.
        </p>
    </div>

    <!-- AVAILABILITY STATISTICS -->
    <div class="availability-summary">

        <div class="availability-stat-card">
            <span>Active Doctors</span>
            <h2><?= $totalDoctors ?></h2>
        </div>

        <div class="availability-stat-card">
            <span>Weekly Schedule Records</span>
            <h2><?= $totalSchedules ?></h2>
        </div>

    </div>

    <!-- DOCTOR SCHEDULES -->
    <div class="content-card">

       
        <div class="card-heading">

            <div>
                <h3>Doctor Schedules</h3>
                <p class="availability-description">
                    Weekly working hours for active doctors.
                </p>
            </div>

            <a href="add-availability.php" class="primary-btn" style="color: whitesmoke;">
                + Add Schedule
            </a>

        </div>


        <div class="table-container">

            <table class="dashboard-table">

                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Department</th>
                        <th>Day</th>
                        <th>Working Hours</th>
                        <th>Schedule Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($doctorSchedules)): ?>

                        <?php foreach ($doctorSchedules as $schedule): ?>

                            <tr>

                                <td>
                                    <strong>
                                        Dr.
                                        <?= escapeAvailability(
                                            $schedule['first_name'] . ' ' .
                                            $schedule['last_name']
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= escapeAvailability(
                                        $schedule['department'] ?: 'N/A'
                                    ) ?>
                                </td>

                                <td>
                                    <?= escapeAvailability(
                                        $schedule['day_of_week'] ?: 'Not scheduled'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if ($schedule['availability_id'] !== null): ?>

                                        <?= formatScheduleTime(
                                            $schedule['start_time']
                                        ) ?>

                                        -

                                        <?= formatScheduleTime(
                                            $schedule['end_time']
                                        ) ?>

                                    <?php else: ?>

                                        N/A

                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($schedule['availability_id'] === null): ?>

                                        <span class="status-badge unscheduled">
                                            Not Scheduled
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge <?= strtolower($schedule['schedule_status']) ?>">

                                            <?= escapeAvailability(
                                                $schedule['schedule_status']
                                            ) ?>

                                        </span>

                                    <?php endif; ?>
                                </td>


                                <td>
                                    <?php if ($schedule['availability_id'] !== null): ?>

                                        <a
                                            href="edit-availability.php?id=<?= (int)$schedule['availability_id'] ?>"
                                            class="primary-btn"
                                        >
                                            Edit
                                        </a>

                                    <?php else: ?>

                                        <span>N/A</span>

                                    <?php endif; ?>
                                </td>


                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="5" class="empty-table-message">
                                No active doctors are currently registered.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
