<?php include __DIR__ . '/databases/edit-staff-process.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <h1>Edit Staff Member</h1>
        <p>Update the information of an existing staff member.</p>
    </div>

    <div class="content-card">

        <?php if ($success): ?>
            <div class="form-message success-message">
                <?= escapeValue($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="form-message error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?= escapeValue($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form
            method="POST"
            action="edit-staff.php?id=<?= (int)$staffId ?>"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= escapeValue($_SESSION['edit_staff_csrf']) ?>"
            >

            <div class="form-group">
                <label for="first_name">First Name *</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    maxlength="100"
                    value="<?= escapeValue($form['first_name']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    maxlength="100"
                    value="<?= escapeValue($form['last_name']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="professional_role">Professional Role</label>
                <input
                    type="text"
                    id="professional_role"
                    value="<?= escapeValue($staff['professional_role']) ?>"
                    readonly
                >
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <input
                    type="text"
                    id="department"
                    name="department"
                    maxlength="100"
                    value="<?= escapeValue($form['department']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="specialization">Specialization</label>
                <input
                    type="text"
                    id="specialization"
                    name="specialization"
                    maxlength="150"
                    value="<?= escapeValue($form['specialization']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input
                    type="tel"
                    id="contact_number"
                    name="contact_number"
                    maxlength="20"
                    value="<?= escapeValue($form['contact_number']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea
                    id="address"
                    name="address"
                    rows="4"
                ><?= escapeValue($form['address']) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">
                    Save Changes
                </button>

                <a href="staff.php" class="cancel-btn">
                    Cancel
                </a>
            </div>

        </form>
    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
