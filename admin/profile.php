
<?php
require_once __DIR__ . '/includes/admin-auth.php';
require_once __DIR__ . '/../config/dbconnect.php';

$page_title = "My Profile";

// Escape values before displaying them.
function escapeProfile($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format registration date.
function formatProfileDate($date) {
    if (empty($date)) {
        return 'N/A';
    }

    $timestamp = strtotime($date);

    return $timestamp !== false
        ? date('d M Y, g:i A', $timestamp)
        : 'N/A';
}

// Retrieve the currently logged-in Administrator.
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        user_id,
        username,
        role,
        account_status,
        created_at
    FROM users
    WHERE user_id = ?
      AND role = 'Administrator'
    LIMIT 1
");

$stmt->execute([$userId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure the account exists.
if (!$admin) {
    http_response_code(404);
    exit('Administrator account not found.');
}

// Account initials for profile avatar.
$initials = strtoupper(
    substr($admin['username'], 0, 1)
);

$statusClass = $admin['account_status'] === 'Active'
    ? 'active'
    : 'inactive';
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="page-heading">
        <h1>My Profile</h1>
        <p>
            View your account information and manage
            your account security.
        </p>
    </div>

    <div class="profile-container">

        <!-- PROFILE OVERVIEW -->
        <section class="content-card">

            <div class="profile-header">

                <div class="profile-avatar">
                    <?= escapeProfile($initials) ?>
                </div>

                <div class="profile-identity">

                    <h2>
                        <?= escapeProfile($admin['username']) ?>
                    </h2>

                    <p>
                        <?= escapeProfile($admin['role']) ?>
                    </p>

                    <span class="status-badge <?= $statusClass ?>">
                        <?= escapeProfile($admin['account_status']) ?>
                    </span>

                </div>

            </div>

        </section>

        <!-- ACCOUNT INFORMATION -->
        <section class="content-card">

            <div class="card-heading">
                <h3>Account Information</h3>
            </div>

            <div class="profile-information">

                <div class="profile-field">
                    <label>User ID</label>
                    <p>
                        <?= (int)$admin['user_id'] ?>
                    </p>
                </div>

                <div class="profile-field">
                    <label>Username</label>
                    <p>
                        <?= escapeProfile($admin['username']) ?>
                    </p>
                </div>

                <div class="profile-field">
                    <label>Account Role</label>
                    <p>
                        <?= escapeProfile($admin['role']) ?>
                    </p>
                </div>

                <div class="profile-field">
                    <label>Account Status</label>

                    <p>
                        <span class="status-badge <?= $statusClass ?>">
                            <?= escapeProfile($admin['account_status']) ?>
                        </span>
                    </p>
                </div>

                <div class="profile-field">
                    <label>Date Created</label>
                    <p>
                        <?= formatProfileDate($admin['created_at']) ?>
                    </p>
                </div>

            </div>

        </section>

        <!-- ACCOUNT SECURITY -->
        <section class="content-card">

            <div class="card-heading">
                <h3>Account Security</h3>
            </div>

            <div class="profile-security">

                <div>
                    <h4>Password Management</h4>

                    <p>
                        Keep your account secure by changing
                        your password regularly and using
                        a strong, unique password.
                    </p>
                </div>

                <a
                    href="../change-password.php"
                    class="primary-btn"
                >
                    Change Password
                </a>

            </div>

        </section>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
