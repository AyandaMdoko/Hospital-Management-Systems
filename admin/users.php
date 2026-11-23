
<?php
require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';

$page_title = "User Management";

// ==========================================
// PREVENT CACHING OF SENSITIVE INFORMATION
// ==========================================

header('Cache-Control: no-store, private');
header('Pragma: no-cache');

// ==========================================
// ESCAPE OUTPUT
// ==========================================

function escapeUser($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// ==========================================
// ACCOUNT STATUS CSRF PROTECTION
// ==========================================

if (empty($_SESSION['user_status_csrf'])) {
    $_SESSION['user_status_csrf'] =
        bin2hex(random_bytes(32));
}

// Retrieve account status messages.
$statusSuccess =
    $_SESSION['user_status_success'] ?? '';

$statusError =
    $_SESSION['user_status_error'] ?? '';

// Clear messages after reading them.
unset(
    $_SESSION['user_status_success'],
    $_SESSION['user_status_error']
);

// ==========================================
// PASSWORD RESET CSRF PROTECTION
// ==========================================

if (empty($_SESSION['reset_user_csrf'])) {
    $_SESSION['reset_user_csrf'] =
        bin2hex(random_bytes(32));
}

// Retrieve password reset feedback.
$resetCredentials =
    $_SESSION['password_reset_credentials'] ?? null;

$resetError =
    $_SESSION['password_reset_error'] ?? '';

// Temporary credentials must be displayed once.
unset(
    $_SESSION['password_reset_credentials'],
    $_SESSION['password_reset_error']
);

// ==========================================
// RETRIEVE REGISTERED USERS
// ==========================================

$sql = "
    SELECT
        users.user_id,
        users.username,
        users.role,
        users.account_status,
        users.created_at,
        staff.first_name,
        staff.last_name
    FROM users
    LEFT JOIN staff
        ON users.staff_id = staff.staff_id
    ORDER BY
        users.created_at DESC,
        users.user_id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="dashboard-container">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">

        <!-- PAGE HEADING -->
        <div class="page-heading">

            <h1>User Management</h1>

            <p>
                Manage system users and their account access.
            </p>

        </div>

        <!-- ==================================
             ACCOUNT STATUS FEEDBACK
        ================================== -->

        <?php if ($statusSuccess): ?>

            <div class="form-message success-message">
                <?= escapeUser($statusSuccess) ?>
            </div>

        <?php endif; ?>

        <?php if ($statusError): ?>

            <div class="form-message error-message">
                <?= escapeUser($statusError) ?>
            </div>

        <?php endif; ?>

        <!-- ==================================
             PASSWORD RESET ERROR
        ================================== -->

        <?php if ($resetError): ?>

            <div class="form-message error-message">
                <?= escapeUser($resetError) ?>
            </div>

        <?php endif; ?>

        <!-- ==================================
             TEMPORARY PASSWORD DISPLAY
        ================================== -->

        <?php if (
            is_array($resetCredentials) &&
            isset(
                $resetCredentials['username'],
                $resetCredentials['password']
            )
        ): ?>

            <div class="reset-credentials-card">

                <h3>Password Reset Successful</h3>

                <p>
                    A new temporary password has been
                    generated for this user account.

                    Copy the credentials below and provide
                    them securely to the account holder.

                    The temporary password will not be
                    displayed again after leaving this page.
                </p>

                <!-- USERNAME -->
                <div class="reset-credential-field">

                    <label for="reset_username">
                        Username
                    </label>

                    <input
                        type="text"
                        id="reset_username"
                        value="<?= escapeUser(
                            $resetCredentials['username']
                        ) ?>"
                        readonly
                        autocomplete="off"
                    >

                </div>

                <!-- TEMPORARY PASSWORD -->
                <div class="reset-credential-field">

                    <label for="reset_password">
                        Temporary Password
                    </label>

                    <input
                        type="text"
                        id="reset_password"
                        value="<?= escapeUser(
                            $resetCredentials['password']
                        ) ?>"
                        readonly
                        autocomplete="off"
                    >

                </div>

                <p class="reset-warning">
                    Important: The user must change this
                    password at their next login.
                </p>

            </div>

        <?php endif; ?>

        <!-- ==================================
             USER MANAGEMENT TABLE
        ================================== -->

        <section class="content-card">

            <div class="card-heading">

                <h3>Registered Users</h3>

                <a
                    href="add-users.php"
                    class="primary-btn"
                    style="color: aliceblue;"
                >
                    + Add User
                </a>

            </div>

            <div class="table-container">

                <table class="dashboard-table">

                    <thead>

                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Staff Member</th>
                            <th>Account Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!empty($users)): ?>

                            <?php foreach ($users as $user): ?>

                                <tr>

                                    <!-- USERNAME -->
                                    <td>
                                        <?= escapeUser(
                                            $user['username']
                                        ) ?>
                                    </td>

                                    <!-- ROLE -->
                                    <td>
                                        <?= escapeUser(
                                            $user['role']
                                        ) ?>
                                    </td>

                                    <!-- STAFF MEMBER -->
                                    <td>

                                        <?php if (
                                            !empty($user['first_name'])
                                        ): ?>

                                            <?= escapeUser(
                                                $user['first_name'] .
                                                ' ' .
                                                $user['last_name']
                                            ) ?>

                                        <?php else: ?>

                                            N/A

                                        <?php endif; ?>

                                    </td>

                                    <!-- ACCOUNT STATUS -->
                                    <td>

                                        <span
                                            class="status-badge <?= escapeUser(
                                                strtolower(
                                                    $user['account_status']
                                                )
                                            ) ?>"
                                        >
                                            <?= escapeUser(
                                                $user['account_status']
                                            ) ?>
                                        </span>

                                    </td>

                                    <!-- DATE CREATED -->
                                    <td>

                                        <?= date(
                                            'd M Y',
                                            strtotime(
                                                $user['created_at']
                                            )
                                        ) ?>

                                    </td>

                                    <!-- ==========================
                                         USER ACTIONS
                                    =========================== -->
                                    <td>

                                        <?php if (
                                            $user['role'] === 'Administrator'
                                        ): ?>

                                            <!-- Administrator -->
                                            <span
                                                class="user-action-protected"
                                            >
                                                Protected Account
                                            </span>

                                        <?php elseif (
                                            in_array(
                                                $user['role'],
                                                ['Doctor', 'Nurse', 'Receptionist'],
                                                true
                                            )
                                        ): ?>

                                            <!-- Doctors and Nurses -->
                                            <a
                                                href="staff.php"
                                                class="user-manage-staff-link"
                                            >
                                                Manage Staff
                                            </a>

                                        <?php elseif (
                                            $user['role'] === 'Receptionist'
                                        ): ?>

                                            <!-- Receptionist status -->

                                            <?php
                                            $isActive =
                                                $user['account_status']
                                                === 'Active';

                                            $newStatus = $isActive
                                                ? 'Inactive'
                                                : 'Active';

                                            $buttonLabel = $isActive
                                                ? 'Deactivate'
                                                : 'Activate';
                                            ?>

                                            <form
                                                action="databases/change-user-status.php"
                                                method="POST"
                                                class="user-status-form"
                                                onsubmit="return confirm('Are you sure you want to <?= strtolower($buttonLabel) ?> this Receptionist account?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= escapeUser(
                                                        $_SESSION[
                                                            'user_status_csrf'
                                                        ]
                                                    ) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="user_id"
                                                    value="<?= (int)$user[
                                                        'user_id'
                                                    ] ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="expected_status"
                                                    value="<?= escapeUser(
                                                        $user[
                                                            'account_status'
                                                        ]
                                                    ) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="new_status"
                                                    value="<?= escapeUser(
                                                        $newStatus
                                                    ) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="<?= $isActive
                                                        ? 'deactivate-btn'
                                                        : 'activate-btn' ?>"
                                                >
                                                    <?= $buttonLabel ?>
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                        <!-- ==========================
                                             PASSWORD RESET
                                        =========================== -->

                                        <?php if (
                                            in_array(
                                                $user['role'],
                                                [
                                                    'Receptionist',
                                                    'Doctor',
                                                    'Nurse'
                                                ],
                                                true
                                            )
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="databases/reset-user-password.php"
                                                class="reset-password-form"
                                                onsubmit="return confirm('Reset this user password? The current password will stop working.');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= escapeUser(
                                                        $_SESSION[
                                                            'reset_user_csrf'
                                                        ]
                                                    ) ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="user_id"
                                                    value="<?= (int)$user[
                                                        'user_id'
                                                    ] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="reset-password-btn"
                                                >
                                                    Reset Password
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="6">
                                    No user accounts have
                                    been registered yet.
                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
