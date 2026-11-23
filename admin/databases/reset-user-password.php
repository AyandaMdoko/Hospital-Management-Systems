
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

// Only accept POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$userId = filter_input(
    INPUT_POST,
    'user_id',
    FILTER_VALIDATE_INT
);

$token = $_POST['csrf_token'] ?? '';

if (
    !$userId ||
    $userId < 1 ||
    !is_string($token) ||
    empty($_SESSION['reset_user_csrf']) ||
    !hash_equals($_SESSION['reset_user_csrf'], $token)
) {
    $_SESSION['password_reset_error'] =
        'Invalid password reset request.';

    header('Location: ../users.php', true, 303);
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock the account while processing the reset.
    $stmt = $pdo->prepare("
        SELECT
            user_id,
            username,
            role
        FROM users
        WHERE user_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new RuntimeException(
            'The selected user account was not found.'
        );
    }

    // Only these account roles may be reset by Admin.
    if (!in_array(
        $user['role'],
        ['Receptionist', 'Doctor', 'Nurse'],
        true
    )) {
        throw new RuntimeException(
            'Password resets for Administrator accounts ' .
            'are not permitted through User Management.'
        );
    }

    // Generate a cryptographically secure
    // temporary password.
    $temporaryPassword = bin2hex(random_bytes(16));

    // Store only the password hash.
    $passwordHash = password_hash(
        $temporaryPassword,
        PASSWORD_DEFAULT
    );

    $update = $pdo->prepare("
        UPDATE users
        SET
            password_hash = ?,
            must_change_password = 1
        WHERE user_id = ?
    ");

    $update->execute([
        $passwordHash,
        $userId
    ]);

    // Record the reset without storing the password.
    $audit = $pdo->prepare("
        INSERT INTO audit_logs
            (user_id, action, entity_type, entity_id)
        VALUES (?, ?, ?, ?)
    ");

    $audit->execute([
        $_SESSION['user_id'],
        'Reset user account password',
        'User',
        $userId
    ]);

    $pdo->commit();

    // Display temporary credentials only once
    // after redirecting to User Management.
    $_SESSION['password_reset_credentials'] = [
        'username' => $user['username'],
        'password' => $temporaryPassword
    ];

    header('Location: ../users.php', true, 303);
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    $_SESSION['password_reset_error'] =
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Unable to reset the account password.';

    header('Location: ../users.php', true, 303);
    exit;
}
