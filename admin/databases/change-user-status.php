
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

// Accept POST requests only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

// Retrieve submitted values.
$userId = filter_input(
    INPUT_POST,
    'user_id',
    FILTER_VALIDATE_INT
);

$expectedStatus = $_POST['expected_status'] ?? '';
$newStatus = $_POST['new_status'] ?? '';
$token = $_POST['csrf_token'] ?? '';

$validStatuses = ['Active', 'Inactive'];

// Validate the request.
if (
    !$userId ||
    $userId < 1 ||
    !is_string($expectedStatus) ||
    !is_string($newStatus) ||
    !in_array($expectedStatus, $validStatuses, true) ||
    !in_array($newStatus, $validStatuses, true) ||
    $expectedStatus === $newStatus ||
    !is_string($token) ||
    empty($_SESSION['user_status_csrf']) ||
    !hash_equals(
        $_SESSION['user_status_csrf'],
        $token
    )
) {
    $_SESSION['user_status_error'] =
        'Invalid account status request.';

    header('Location: users.php', true, 303);
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock and retrieve the selected user account.
    $stmt = $pdo->prepare("
        SELECT
            user_id,
            username,
            role,
            account_status
        FROM users
        WHERE user_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new RuntimeException(
            'The selected user account does not exist.'
        );
    }

    // Only Receptionist accounts can be
    // activated or deactivated directly here.
    if ($user['role'] !== 'Receptionist') {
        throw new RuntimeException(
            'Doctor and Nurse accounts must be managed ' .
            'through Staff Management. ' .
            'Administrator accounts are protected.'
        );
    }

    // Prevent changes based on outdated page information.
    if ($user['account_status'] !== $expectedStatus) {
        throw new RuntimeException(
            'This account status has already changed. ' .
            'Refresh the page and try again.'
        );
    }

    // Update the account status.
    $update = $pdo->prepare("
        UPDATE users
        SET account_status = ?
        WHERE user_id = ?
          AND role = 'Receptionist'
    ");

    $update->execute([
        $newStatus,
        $userId
    ]);

    // Record the action in the audit log.
    $action = $newStatus === 'Inactive'
        ? 'Deactivated receptionist account'
        : 'Activated receptionist account';

    $audit = $pdo->prepare("
        INSERT INTO audit_logs
            (user_id, action, entity_type, entity_id)
        VALUES (?, ?, ?, ?)
    ");

    $audit->execute([
        $_SESSION['user_id'],
        $action,
        'User',
        $userId
    ]);

    $pdo->commit();

    $_SESSION['user_status_success'] =
        'Receptionist account ' .
        strtolower($newStatus === 'Active'
            ? 'activated'
            : 'deactivated') .
        ' successfully.';

} catch (RuntimeException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['user_status_error'] = $e->getMessage();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    $_SESSION['user_status_error'] =
        'Unable to update the account status.';
}

header('Location: ../users.php', true, 303);
exit;
?>

