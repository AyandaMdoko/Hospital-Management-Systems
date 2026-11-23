<?php include_once __DIR__ . '/databases/add-availability-process.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <h1>Add Doctor Schedule</h1>
        <p>Assign weekly working hours to a doctor.</p>
    </div>

    <div class="content-card">

        <div class="card-heading">
            <h3>Schedule Information</h3>
        </div>

        <?php if ($success): ?>
            <div class="form-message success-message">
                <?= escapeSchedule($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="form-message error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?= escapeSchedule($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($doctors)): ?>

            <div class="form-message error-message">
                No active doctors are registered.
                Please activate or register a doctor first.
            </div>

        <?php else: ?>

        <form action="add-availability.php" method="POST">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapeSchedule(
                    $_SESSION['add_availability_csrf']
                ) ?>"
            >

            <!-- Doctor -->
            <div class="form-group">
                <label for="staff_id">Doctor *</label>

                <select name="staff_id" id="staff_id" required>
                    <option value="">Select Doctor</option>

                    <?php foreach ($doctors as $doctor): ?>
                        <option
                            value="<?= (int)$doctor['staff_id'] ?>"
                            <?= (string)$form['staff_id'] ===
                                (string)$doctor['staff_id']
                                ? 'selected' : '' ?>
                        >
                            <?= escapeSchedule(
                                'Dr. ' .
                                $doctor['first_name'] . ' ' .
                                $doctor['last_name']
                            ) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <!-- Day -->
            <div class="form-group">
                <label for="day_of_week">Day of Week *</label>

                <select
                    name="day_of_week"
                    id="day_of_week"
                    required
                >
                    <option value="">Select Day</option>

                    <?php foreach ($days as $day): ?>
                        <option
                            value="<?= escapeSchedule($day) ?>"
                            <?= $form['day_of_week'] === $day
                                ? 'selected' : '' ?>
                        >
                            <?= escapeSchedule($day) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <!-- Start Time -->
            <div class="form-group">
                <label for="start_time">Start Time *</label>

                <input
                    type="time"
                    name="start_time"
                    id="start_time"
                    value="<?= escapeSchedule(
                        $form['start_time']
                    ) ?>"
                    required
                >
            </div>

            <!-- End Time -->
            <div class="form-group">
                <label for="end_time">End Time *</label>

                <input
                    type="time"
                    name="end_time"
                    id="end_time"
                    value="<?= escapeSchedule(
                        $form['end_time']
                    ) ?>"
                    required
                >
            </div>

            <!-- Schedule Status -->
            <div class="form-group">
                <label for="schedule_status">
                    Schedule Status *
                </label>

                <select
                    name="schedule_status"
                    id="schedule_status"
                    required
                >
                    <?php foreach ($statuses as $status): ?>
                        <option
                            value="<?= escapeSchedule($status) ?>"
                            <?= $form['schedule_status'] === $status
                                ? 'selected' : '' ?>
                        >
                            <?= escapeSchedule($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Buttons -->
            <div class="form-actions">

                <button type="submit" class="primary-btn">
                    Save Schedule
                </button>

                <a href="availability.php" class="cancel-btn">
                    Cancel
                </a>

            </div>

        </form>

        <?php endif; ?>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
