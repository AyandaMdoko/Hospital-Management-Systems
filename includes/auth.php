
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine the application URL.
// Supports both localhost and a project subfolder.
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDirectory = dirname($scriptPath);

$roleFolders = [
    'admin',
    'doctor',
    'nurse',
    'receptionist'
];

if (in_array(basename($scriptDirectory), $roleFolders, true)) {
    $appBase = dirname($scriptDirectory);
} else {
    $appBase = $scriptDirectory;
}

$appBase = ($appBase === '/' || $appBase === '.')
    ? ''
    : rtrim($appBase, '/');

$loginUrl = $appBase . '/user-login.php';
$passwordUrl = $appBase . '/change-password.php';

// Ensure the user is logged in.
if (empty($_SESSION['user_id'])) {
    header('Location: ' . $loginUrl);
    exit;
}

// Connect to the database.
require_once __DIR__ . '/../config/dbconnect.php';

// Retrieve current user and staff statuses.
$stmt = $pdo->prepare("
    SELECT
        u.user_id,
        u.staff_id,
        u.role,
        u.account_status,
        u.must_change_password,
        s.staff_status
    FROM users u
    LEFT JOIN staff s
        ON u.staff_id = s.staff_id
    WHERE u.user_id = ?
    LIMIT 1
");

$stmt->execute([$_SESSION['user_id']]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check whether the linked staff member is inactive
// or missing (for Doctor and Nurse accounts).
$staffInvalid = false;

if ($user && in_array($user['role'], ['Doctor', 'Nurse'], true)) {
    $staffInvalid =
        $user['staff_id'] === null ||
        $user['staff_status'] !== 'Active';
}

// Reject deleted, inactive, or invalid accounts.
if (
    !$user ||
    $user['account_status'] !== 'Active' ||
    $staffInvalid
) {
    // Clear the session.
    $_SESSION = [];

    // Remove the session cookie.
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header('Location: ' . $loginUrl);
    exit;
}

// Refresh session information from the database.
$_SESSION['role'] = $user['role'];
$_SESSION['staff_id'] = $user['staff_id'];
$_SESSION['must_change_password'] =
    (int)$user['must_change_password'];

// Require a password change when necessary.
// Do not redirect if already on the password-change page.
if (
    $_SESSION['must_change_password'] === 1 &&
    basename($scriptPath) !== 'change-password.php'
) {
    header('Location: ' . $passwordUrl);
    exit;
}