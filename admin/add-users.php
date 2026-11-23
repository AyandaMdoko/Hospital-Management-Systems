<?php include __DIR__ . "/databases/add-user-process.php"; ?>

<div class="dashboard-container">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">

        <div class="page-heading">
            <h1>Add User</h1>
            <p>Create a new account for a receptionist, doctor or nurse.</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="form-message error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($generated_credentials): ?>

            <section class="content-card credential-card">

                <div class="card-heading">
                    <h3>Account Created Successfully</h3>
                </div>

                <p>
                    The account has been created. Give these credentials
                    to the user so they can log in.
                </p>

                <div class="credential-item">
                    <strong>Username:</strong>
                    <span>
                        <?php echo htmlspecialchars($generated_credentials['username']); ?>
                    </span>
                </div>

                <div class="credential-item">
                    <strong>Temporary Password:</strong>
                    <span>
                        <?php echo htmlspecialchars($generated_credentials['password']); ?>
                    </span>
                </div>

                <p class="credential-warning">
                    Important: Copy this temporary password now.
                    It will not be displayed again. The user must change
                    it when they first log in.
                </p>

                <button
                    type="button"
                    class="primary-btn"
                    onclick="copyCredentials()"
                >
                    Copy Credentials
                </button>

                <a href="users.php" class="cancel-btn">
                    Back to User Management
                </a>

            </section>

        <?php else: ?>

            <section class="content-card">

                <div class="card-heading">
                    <h3>User Account Information</h3>
                </div>

                <form method="POST" action="">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                    >

                    <div class="form-group">
                        <label for="role">User Role</label>

                        <select name="role" id="role" required>
                            <option value="">Select User Role</option>
                            <option value="Receptionist">Receptionist</option>
                            <option value="Doctor">Doctor</option>
                            <option value="Nurse">Nurse</option>
                        </select>
                    </div>

                    <div class="form-group" id="staff-group" style="display: none;">

                        <label for="staff_id">Select Staff Member</label>

                        <select name="staff_id" id="staff_id">
                            <option value="">Select Staff Member</option>

                            <?php foreach ($staff_members as $staff): ?>

                                <option
                                    value="<?php echo $staff['staff_id']; ?>"
                                    data-role="<?php echo htmlspecialchars($staff['professional_role']); ?>"
                                >
                                    <?php echo htmlspecialchars(
                                        $staff['first_name'] . ' ' .
                                        $staff['last_name'] . ' (' .
                                        $staff['professional_role'] . ')'
                                    ); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>

                        <input
                            type="text"
                            name="username"
                            id="username"
                            placeholder="Enter username"
                            maxlength="100"
                            required
                        >
                    </div>

                    <div class="form-actions">

                        <button type="submit" class="primary-btn">
                            Create Account
                        </button>

                        <a href="users.php" class="cancel-btn">
                            Cancel
                        </a>

                    </div>

                </form>

            </section>

        <?php endif; ?>

    </main>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src= "assets/js/script.js"></script>
<script>
    function copyCredentials() {

    const username = <?php echo json_encode($generated_credentials['username'] ?? ''); ?>;
    const password = <?php echo json_encode($generated_credentials['password'] ?? ''); ?>;

    const credentials =
        'Username: ' + username +
        '\nTemporary Password: ' + password;

    navigator.clipboard.writeText(credentials).then(() => {
        alert('Credentials copied successfully.');
    }).catch(() => {
        alert('Unable to copy credentials. Please copy them manually.');
    });
}
</script>