
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

// Validate the staff ID.
$staffId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$staffId || $staffId < 1) {
    http_response_code(400);
    exit("Invalid staff member.");
}

// Load the existing staff record.
$stmt = $pdo->prepare("
    SELECT staff_id, first_name, last_name,
           professional_role, department,
           specialization, contact_number, address
    FROM staff
    WHERE staff_id = ?
");

$stmt->execute([$staffId]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    http_response_code(404);
    exit("Staff member not found.");
}

// Generate CSRF protection token.
if (empty($_SESSION['edit_staff_csrf'])) {
    $_SESSION['edit_staff_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];

$form = [
    'first_name' => $staff['first_name'],
    'last_name' => $staff['last_name'],
    'department' => $staff['department'] ?? '',
    'specialization' => $staff['specialization'] ?? '',
    'contact_number' => $staff['contact_number'] ?? '',
    'address' => $staff['address'] ?? ''
];

$success = $_SESSION['staff_update_success'] ?? '';
unset($_SESSION['staff_update_success']);

// Handle form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token.
    if (
        !is_string($token) ||
        !hash_equals($_SESSION['edit_staff_csrf'], $token)
    ) {
        $errors[] = "Invalid form submission. Please try again.";
    } else {
        
        // Sanitize and validate form inputs.
        foreach ($form as $field => $value) {
            $form[$field] = trim((string)($_POST[$field] ?? ''));
        }

        if ($form['first_name'] === '') {
            $errors[] = "First name is required.";
        }

        if ($form['last_name'] === '') {
            $errors[] = "Last name is required.";
        }

        $limits = [
            'first_name' => 100,
            'last_name' => 100,
            'department' => 100,
            'specialization' => 150,
            'contact_number' => 20
        ];

        // Check for maximum length violations.
        foreach ($limits as $field => $maxLength) {
            if (strlen($form[$field]) > $maxLength) {
                $errors[] = ucfirst(str_replace('_', ' ', $field))
                    . " is too long.";
            }
        }

        // If no validation errors, proceed to update the staff record.
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $update = $pdo->prepare("
                    UPDATE staff
                    SET first_name = ?,
                        last_name = ?,
                        department = ?,
                        specialization = ?,
                        contact_number = ?,
                        address = ?
                    WHERE staff_id = ?
                ");

                $update->execute([
                    $form['first_name'],
                    $form['last_name'],
                    $form['department'],
                    $form['specialization'],
                    $form['contact_number'],
                    $form['address'],
                    $staffId
                ]);

                // Record the update in audit logs.
                $log = $pdo->prepare("
                    INSERT INTO audit_logs
                        (user_id, action, entity_type, entity_id)
                    VALUES (?, ?, ?, ?)
                ");

                $log->execute([
                    $_SESSION['user_id'],
                    'Updated staff information',
                    'staff',
                    $staffId
                ]);

                $pdo->commit();

                $_SESSION['staff_update_success'] =
                    "Staff information updated successfully.";

                header("Location: edit-staff.php?id=" . $staffId);
                exit;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($e->getMessage());
                $errors[] = "Unable to update staff information.";
            }
        }
    }
}

function escapeValue($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>