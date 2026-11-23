
<?php

session_start();

require_once __DIR__ . '/config/dbconnect.php';
require_once __DIR__ . '/includes/auth.php';

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: user-login.php');
    exit;
}

$error = '';
$success = '';

// Create a CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process password change.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid form submission. Please try again.';

    } elseif (
        $currentPassword === '' ||
        $newPassword === '' ||
        $confirmPassword === ''
    ) {
        $error = 'Please complete all password fields.';

    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New password and confirmation password do not match.';

    } elseif (strlen($newPassword) < 8) {
        $error = 'Your new password must contain at least 8 characters.';

    } elseif ($currentPassword === $newPassword) {
        $error = 'Your new password must be different from your current password.';

    } else {

        try {

            // Retrieve the current password hash.
            $stmt = $pdo->prepare("
                SELECT password_hash
                FROM users
                WHERE user_id = ?
                AND account_status = 'Active'
                LIMIT 1
            ");

            $stmt->execute([$_SESSION['user_id']]);

            $user = $stmt->fetch();

            if (!$user) {

                $error = 'Your account could not be found or is inactive.';

            } elseif (!password_verify($currentPassword, $user['password_hash'])) {

                $error = 'Your current password is incorrect.';

            } else {

                // Hash the new password.
                $newPasswordHash = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                // Update the password and remove the temporary-password requirement.
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET password_hash = ?,
                        must_change_password = 0
                    WHERE user_id = ?
                ");

                $stmt->execute([
                    $newPasswordHash,
                    $_SESSION['user_id']
                ]);

                // Update the session.
                $_SESSION['must_change_password'] = 0;

                // Regenerate the session ID.
                session_regenerate_id(true);

                // Redirect according to the user's role.
                $dashboards = [
                    'Administrator' => 'admin/dashboard.php',
                    'Receptionist' => 'receptionist/dashboard.php',
                    'Doctor' => 'doctor/dashboard.php',
                    'Nurse' => 'nurse/dashboard.php'
                ];

                $destination = $dashboards[$_SESSION['role']] ?? null;

                if (
                    $destination &&
                    file_exists(__DIR__ . '/' . $destination)
                ) {

                    header('Location: ' . $destination);
                    exit;

                } else {

                    $_SESSION['login_error'] =
                        'Your password was changed successfully, but your dashboard is not yet available.';

                    header('Location: user-login.php');
                    exit;
                }
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $error = 'Unable to update your password. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="assets/css/authentication-styles.css">

    <title>Change Password | Hospital Management System</title>

    <style>

        .password-message {
            width: 80%;
            max-width: 350px;
            margin: 12px auto;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
        }

        .password-error {
            background: #fdeaea;
            color: #c03939;
        }

        .password-instructions {
            width: 80%;
            max-width: 350px;
            margin: 0 auto 15px;
            color: #7b8999;
            font-size: 12px;
            line-height: 1.5;
        }

    </style>

</head>

<body>

<!-- Header and return-home navigation -->
    <header class="login-header">
        <div class="login-header-content">
            <a class="login-brand" href="index.html" aria-label="Hospital Management System home">
                <span class="login-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 48 48"><path d="M24 42S6 31.2 6 17.8C6 10.7 14.8 6.4 21 12l3 3 3-3c6.2-5.6 15-1.3 15 5.8C42 31.2 24 42 24 42Z"/><path class="pulse" d="M9 24h9l4-8 6.2 16 4.1-8H39"/></svg>
                </span>
                <span class="login-brand-name">Hospital Management<span>System</span></span>
            </a>
            <a class="back-home" href="index.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to home</a>
        </div>
    </header>

    <!-- Welcome message shown above the change-password form -->
    <section class="login-welcome">
        <span class="welcome-badge">Account Security</span>

        <h1>Change your password.</h1>

        <p>
            Choose a new password to secure your Hospital Management System account.
        </p>
    </section>

    <section class="login-form">

        <div class="login-container">

            <div class="logo">
                <h2>
                    <i class="bi bi-shield-lock"></i>
                    HMS | Change Password
                </h2>
            </div>

            <div class="login-box">

                <?php if ($error !== ''): ?>

                    <div class="password-message password-error" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>

                <?php endif; ?>

                <form method="POST" action="" class="change-password-form">

                    <fieldset>

                        <legend>
                            <i class="bi bi-key-fill"></i>
                            Update Password
                        </legend>

                        <p class="password-instructions">
                            Enter your current password, then choose a new password
                            containing at least 8 characters.
                        </p>

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
                        >

                        <div class="form-group">

                            <input
                                type="password"
                                name="current_password"
                                id="current_password"
                                placeholder="Current Password"
                                required
                            >

                        </div></br>

                        <div class="form-group">

                            <input
                                type="password"
                                name="new_password"
                                id="new_password"
                                placeholder="New Password"
                                required
                            >

                        </div></br>

                        <div class="form-group">

                            <input
                                type="password"
                                name="confirm_password"
                                id="confirm_password"
                                placeholder="Confirm New Password"
                                required
                            >

                        </div></br>

                        <label class="show-password-control" for="change-show-passwords">
                            <input
                                type="checkbox"
                                class="show-password-checkbox"
                                id="change-show-passwords"
                            >
                            <span>Show passwords</span>
                        </label>

                        <div class="form-actions">

                            <button
                                type="submit"
                                class="login-button"
                                name="submit"
                            >
                                <i class="bi bi-check2-circle"></i>
                                Change Password
                            </button>

                        </div>

                    </fieldset>

                </form>

            </div>

        </div>

    </section>

    <script src="assets/js/script.js"></script>

</body>

</html>
