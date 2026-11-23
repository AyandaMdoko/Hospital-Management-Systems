<?php include __DIR__ . '/databases/edit-availability-process.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <h1>Edit Doctor Schedule</h1>
        <p>Update an existing doctor's weekly working schedule.</p>
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

        <form
            method="POST"
            action="edit-availability.php?id=<?= (int)$availabilityId ?>"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapeSchedule(
                    $_SESSION['edit_availability_csrf']
                ) ?>"
            >

            <div class="form-group">
                <label for="doctor_name">Doctor</label>

                <input
                    type="text"
                    id="doctor_name"
                    value="<?= escapeSchedule(
                        'Dr. ' .
                        $schedule['first_name'] . ' ' .
                        $schedule['last_name']
                    ) ?>"
                    readonly
                >
            </div>

            <div class="form-group">
                <label for="day_of_week">Day of Week *</label>

                <select
                    id="day_of_week"
                    name="day_of_week"
                    required
                >
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

            <div class="form-group">
                <label for="start_time">Start Time *</label>

                <input
                    type="time"
                    id="start_time"
                    name="start_time"
                    value="<?= escapeSchedule($form['start_time']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="end_time">End Time *</label>

                <input
                    type="time"
                    id="end_time"
                    name="end_time"
                    value="<?= escapeSchedule($form['end_time']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="schedule_status">Schedule Status *</label>

                <select
                    id="schedule_status"
                    name="schedule_status"
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

            <div class="form-actions">

                <button type="submit" class="primary-btn">
                    Save Changes
                </button>

                <a href="availability.php" class="cancel-btn">
                    Cancel
                </a>

            </div>

        </form>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
