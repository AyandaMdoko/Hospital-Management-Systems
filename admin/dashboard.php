<?php require_once __DIR__ . '/includes/admin-auth.php'; ?>
<?php include __DIR__ . "/databases/dashboardprocesses.php"; ?>

<main class="main-content">

    <div class="page-heading">
        <div>
            <h1>Dashboard</h1>
            <p>Welcome to the Hospital Management System.</p>
        </div>
    </div>

    <!-- Statistics Cards -->

    <section class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon blue">♙</div>
            <div>
                <p>Total Patients</p>
                 <h2><?php echo $total_patients; ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon purple">♧</div>
            <div>
                <p>Total Staff</p>
                <h2><?php echo $total_staff; ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green">◷</div>
            <div>
                <p>Available Doctors</p>
                <h2><?php echo $available_doctors; ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon orange">▤</div>
            <div>
                <p>Completed Consultations</p>
                <h2><?php echo $completed_consultations; ?></h2>
            </div>
        </div>

    </section>

    <!-- Dashboard Overview -->

    
<section class="dashboard-grid">

    <!-- DOCTOR AVAILABILITY -->

    <div class="content-card">

        <div class="card-heading">
            <h3>Doctor Availability</h3>
            <a href="availability.php">View All</a>
        </div>

        <?php if (!empty($doctor_schedules)): ?>

            <div class="table-container">

                <table class="dashboard-table">

                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Day</th>
                            <th>Working Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($doctor_schedules as $doctor): ?>

                            <tr>
                                <td>
                                    <?php echo htmlspecialchars(
                                        $doctor['first_name'] . ' ' .
                                        $doctor['last_name']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(
                                        $doctor['day_of_week']
                                    ); ?>
                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        'g:i A',
                                        strtotime($doctor['start_time'])
                                    );
                                    ?>
                                    -
                                    <?php
                                    echo date(
                                        'g:i A',
                                        strtotime($doctor['end_time'])
                                    );
                                    ?>
                                </td>

                                <td>
                                    <span class="status-badge <?php echo strtolower($doctor['schedule_status']); ?>">
                                        <?php echo htmlspecialchars(
                                            $doctor['schedule_status']
                                        ); ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p class="empty-message">
                No doctor availability records found.
            </p>

        <?php endif; ?>

    </div>

    <!-- RECENT CONSULTATIONS -->

    <div class="content-card">

        <div class="card-heading">
            <h3>Recent Consultations</h3>
            <a href="consultations.php">View All</a>
        </div>

        <?php if (!empty($recent_consultations)): ?>

            <div class="table-container">

                <table class="dashboard-table">

                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Attending Staff</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- Display recent consultations if available -->
                        <?php foreach ($recent_consultations as $consultation): ?>

                            <tr>
                                <td>
                                    <?php echo htmlspecialchars(
                                        $consultation['patient_first_name'] . ' ' .
                                        $consultation['patient_last_name']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars(
                                        $consultation['staff_first_name'] . ' ' .
                                        $consultation['staff_last_name']
                                    ); ?>
                                </td>

                                <td>
                                    <?php echo date(
                                        'd M Y',
                                        strtotime($consultation['consultation_date'])
                                    ); ?>
                                </td>

                                <td>
                                    <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $consultation['consultation_status'])); ?>">
                                        <?php echo htmlspecialchars(
                                            $consultation['consultation_status']
                                        ); ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p class="empty-message">
                No consultation records found.
            </p>

        <?php endif; ?>

    </div>

</section>


</main>

<?php include __DIR__ . "/includes/footer.php"; ?>
