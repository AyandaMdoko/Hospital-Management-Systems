
<?php

session_start();

require_once __DIR__ . '/../config/dbconnect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = "Please enter your username and password.";

    } else {

        $stmt = $pdo->prepare("
            SELECT
                user_id,
                staff_id,
                username,
                password_hash,
                role,
                account_status,
                must_change_password
            FROM users
            WHERE username = :username
            LIMIT 1
        ");

        $stmt->execute(['username' => $username]);

        $user = $stmt->fetch();

        if (
            $user &&
            $user['account_status'] === 'Active' &&
            password_verify($password, $user['password_hash'])
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['staff_id'] = $user['staff_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['must_change_password'] =
                (int) $user['must_change_password']; 

            // Force users with temporary passwords to change them first.
            if ($_SESSION['must_change_password'] === 1) {
                header("Location: ../change-password.php");
                exit;
            }

            // Redirect according to the user's role.
            $dashboards = [
                'Administrator' => 'admin/dashboard.php',
                'Receptionist' => 'receptionist/dashboard.php',
                'Doctor' => 'clinical/dashboard.php',
                'Nurse' => 'clinical/dashboard.php'
            ];

            $destination = $dashboards[$user['role']] ?? null;

            if (
                $destination &&
                file_exists(__DIR__ . '/../' . $destination)
            ) {

                header("Location: ../" . $destination);
                exit;

            } else {

                $error = "Your login was successful, but your dashboard is not yet available.";

            }

        } else {

            $error = "Invalid username or password, or your account is inactive.";

        }
    }
}

if ($error !== '') {

    $_SESSION['login_error'] = $error;

    header('Location: ../user-login.php');
    exit;
}

?>