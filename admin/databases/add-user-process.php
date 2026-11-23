
<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . "/../../config/dbconnect.php";

$error = '';
$generated_credentials = $_SESSION['generated_credentials'] ?? null;

// Remove the credentials from the session so they are displayed only once.
unset($_SESSION['generated_credentials']);

// Retrieve existing doctors and nurses.
$stmt = $pdo->prepare("
    SELECT staff_id, first_name, last_name, professional_role
    FROM staff
    WHERE professional_role IN ('Doctor', 'Nurse', 'Receptionist')
    ORDER BY first_name ASC
");
$stmt->execute();
$staff_members = $stmt->fetchAll();

// Create a CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process the form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $staff_id = $_POST['staff_id'] ?? '';

    // Validate the CSRF token.
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid form submission. Please try again.';
    }

    // Validate the selected role.
    elseif (!in_array($role, ['Receptionist', 'Doctor', 'Nurse'], true)) {
        $error = 'Please select a valid user role.';
    }

    // Validate the username.
    elseif ($username === '' || strlen($username) > 100) {
        $error = 'Please enter a username of up to 100 characters.';
    }

    else {
        try {

            // Check whether the username already exists.
            $stmt = $pdo->prepare("
                SELECT user_id
                FROM users
                WHERE username = ?
            ");
            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                $error = 'This username already exists. Please choose another one.';
            }

            $staff_id_to_save = null;

            // user must be linked to an existing staff profile.
            if ($error === '' && in_array($role, ['Doctor', 'Nurse', 'Receptionist'], true)) {

                if (!filter_var($staff_id, FILTER_VALIDATE_INT)) {
                    $error = 'Please select a staff member.';
                } else {

                    // Confirm that the selected staff member has the correct role.
                    $stmt = $pdo->prepare("
                        SELECT staff_id
                        FROM staff
                        WHERE staff_id = ?
                        AND professional_role = ?
                    ");
                    $stmt->execute([$staff_id, $role]);

                    if (!$stmt->fetch()) {
                        $error = 'The selected staff member does not match the chosen role.';
                    } else {

                        // Check whether this staff member already has an account.
                        $stmt = $pdo->prepare("
                            SELECT user_id
                            FROM users
                            WHERE staff_id = ?
                        ");
                        $stmt->execute([$staff_id]);

                        if ($stmt->fetch()) {
                            $error = 'This staff member already has a user account.';
                        } else {
                            $staff_id_to_save = (int) $staff_id;
                        }
                    }
                }
            }

            if ($error === '') {

                // Generate a secure, random 20-character temporary password.
                $temporary_password = bin2hex(random_bytes(10));

                // Hash the password before storing it.
                $password_hash = password_hash(
                    $temporary_password,
                    PASSWORD_DEFAULT
                );

                // Insert the new account.
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO users
                    (staff_id, username, password_hash, role, must_change_password)
                    VALUES (?, ?, ?, ?, 1)
                ");

                $stmt->execute([
                    $staff_id_to_save,
                    $username,
                    $password_hash,
                    $role
                ]);

                $new_user_id = $pdo->lastInsertId();

                // Record the account creation in the audit log.
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs
                    (user_id, action, entity_type, entity_id)
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $_SESSION['user_id'],
                    'Created user account',
                    'User',
                    $new_user_id
                ]);

                $pdo->commit();

                // Store the temporary credentials for one-time display.
                $_SESSION['generated_credentials'] = [
                    'username' => $username,
                    'password' => $temporary_password
                ];

                // Generate a new CSRF token.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Redirect to prevent duplicate form submissions.
                header('Location: add-users.php');
                exit;
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            if ($e->getCode() === '23000') {
                $error = 'The username or staff member is already linked to an account.';
            } else {
                $error = 'Unable to create the account. Please try again.';
            }
        }
    }
}

?>

<?php include __DIR__ . '/../includes/header.php'; ?>
