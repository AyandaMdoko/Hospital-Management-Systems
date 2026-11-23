
<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . "/../../config/dbconnect.php";

$error = '';
$success = '';

// Form values
$first_name = '';
$last_name = '';
$professional_role = '';
$department = '';
$specialization = '';
$contact_number = '';
$address = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $professional_role = $_POST['professional_role'] ?? '';
    $department = trim($_POST['department'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid form submission. Please try again.';

    } elseif ($first_name === '' || $last_name === '') {
        $error = 'First name and last name are required.';

    } elseif (!in_array($professional_role, ['Doctor', 'Nurse', 'Receptionist'], true)) {
        $error = 'Please select a valid professional role.';

    } elseif (strlen($first_name) > 100 || strlen($last_name) > 100) {
        $error = 'Names cannot exceed 100 characters.';

    } elseif (strlen($department) > 100) {
        $error = 'Department cannot exceed 100 characters.';

    } elseif (strlen($specialization) > 150) {
        $error = 'Specialization cannot exceed 150 characters.';

    } elseif (strlen($contact_number) > 20) {
        $error = 'Contact number cannot exceed 20 characters.';

    } else {

        try {

            $pdo->beginTransaction();

            // Insert staff member
            $stmt = $pdo->prepare("
                INSERT INTO staff (
                    first_name,
                    last_name,
                    professional_role,
                    department,
                    specialization,
                    contact_number,
                    address
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $first_name,
                $last_name,
                $professional_role,
                $department ?: null,
                $specialization ?: null,
                $contact_number ?: null,
                $address ?: null
            ]);

            $new_staff_id = $pdo->lastInsertId();

            // Record the action in the audit log
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    user_id,
                    action,
                    entity_type,
                    entity_id
                )
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION['user_id'],
                'Registered new staff member',
                'Staff',
                $new_staff_id
            ]);

            $pdo->commit();

            $success = 'Staff member registered successfully.';

            // Clear form values after successful registration
            $first_name = '';
            $last_name = '';
            $professional_role = '';
            $department = '';
            $specialization = '';
            $contact_number = '';
            $address = '';

            // Generate a new CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $error = 'Unable to register staff member. Please try again.';
        }
    }
}

?>

<?php include __DIR__ . '/../includes/header.php'; ?>