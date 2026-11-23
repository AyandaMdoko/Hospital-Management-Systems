<?php
/**
 * Shared Doctor / Professional Nurse access guard.
 * Depends on the project's existing includes/auth.php session and account checks.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$clinicalRole = (string) ($_SESSION['role'] ?? '');
$clinicalStaffId = filter_var(
    $_SESSION['staff_id'] ?? null,
    FILTER_VALIDATE_INT
);

// A shared screen does not mean every logged-in role is permitted to use it.
if (!in_array($clinicalRole, ['Doctor', 'Nurse'], true) || !$clinicalStaffId) {
    http_response_code(403);
    exit('Access denied. This area is for authorized Doctors and Nurses only.');
}

// Double-check that the user is linked to an active staff profile of the
// matching professional role. The central auth.php still handles disabled accounts.
$clinicalStaffStatement = $pdo->prepare("
    SELECT staff_id
    FROM staff
    WHERE staff_id = :staff_id
      AND professional_role = :professional_role
      AND staff_status = 'Active'
    LIMIT 1
");
$clinicalStaffStatement->execute([
    ':staff_id' => $clinicalStaffId,
    ':professional_role' => $clinicalRole,
]);

if (!$clinicalStaffStatement->fetchColumn()) {
    http_response_code(403);
    exit('Access denied. No matching active clinical staff profile was found.');
}
