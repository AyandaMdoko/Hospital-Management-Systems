
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

// Only allow POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$staffId = filter_input(INPUT_POST, 'staff_id', FILTER_VALIDATE_INT);
$newStatus = $_POST['new_status'] ?? null;
$token = $_POST['csrf_token'] ?? null;

// Validate the request.
if (
    !$staffId ||
    $staffId < 1 ||
    !in_array($newStatus, ['Active', 'Inactive'], true) ||
    !is_string($token) ||
    empty($_SESSION['staff_status_csrf']) ||
    !hash_equals($_SESSION['staff_status_csrf'], $token)
) {
    $_SESSION['staff_status_error'] = 'Invalid status change request.';
    header('Location: ../staff.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Lock and retrieve the staff record.
    $stmt = $pdo->prepare("
        SELECT staff_status
        FROM staff
        WHERE staff_id = ?
        FOR UPDATE
    ");

    $stmt->execute([$staffId]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        throw new RuntimeException('Staff member not found.');
    }

    if ($staff['staff_status'] === $newStatus) {
        $pdo->rollBack();

        $_SESSION['staff_status_success'] =
            'The staff member already has this status.';

        header('Location: ../staff.php');
        exit;
    }

    // Update staff status.
    $update = $pdo->prepare("
        UPDATE staff
        SET staff_status = ?
        WHERE staff_id = ?
    ");

    $update->execute([$newStatus, $staffId]);

    
    // Synchronize the linked user account with the staff status.
    $accountUpdate = $pdo->prepare("
        UPDATE users
        SET account_status = ?
        WHERE staff_id = ?
        AND role IN ('Doctor', 'Nurse', 'Receptionist')
    ");

    $accountUpdate->execute([
        $newStatus,
        $staffId
    ]);


    // Record the action in audit logs.
    $action = $newStatus === 'Inactive'
        ? 'Deactivated staff member'
        : 'Activated staff member';

    $log = $pdo->prepare("
        INSERT INTO audit_logs
            (user_id, action, entity_type, entity_id)
        VALUES (?, ?, ?, ?)
    ");

    $log->execute([
        $_SESSION['user_id'],
        $action,
        'staff',
        $staffId
    ]);

    $pdo->commit();

    $_SESSION['staff_status_success'] =
        $newStatus === 'Inactive'
        ? 'Staff member deactivated successfully. Any linked user account has also been deactivated.'
        : 'Staff member activated successfully. A linked user account must be reactivated separately.';

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    $_SESSION['staff_status_error'] =
        'Unable to update staff status. Please try again.';
}

header('Location: ../staff.php');
exit;
?>
