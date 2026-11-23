
<?php

session_start();

require_once __DIR__ . '/../config/dbconnect.php';

$message = '';
$success = false;

// Check whether an Administrator already exists.
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'Administrator'
");

$stmt->execute();

$admin_exists = (int) $stmt->fetchColumn() > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$admin_exists) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '') {

        $message = "Please complete all fields.";

    } elseif (strlen($password) < 8) {

        $message = "Password must contain at least 8 characters.";

    } elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        $stmt = $pdo->prepare("
            SELECT user_id FROM users
            WHERE username = :username
        ");

        $stmt->execute(['username' => $username]);

        if ($stmt->fetch()) {

            $message = "This username is already in use.";

        } else {

            $password_hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO users
                (username, password_hash, role, account_status)
                VALUES
                (:username, :password_hash, 'Administrator', 'Active')
            ");

            $stmt->execute([
                'username' => $username,
                'password_hash' => $password_hash
            ]);

            $success = true;
            $admin_exists = true;

            $message = "Administrator account created successfully.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial Administrator Setup</title>
</head>

<body>

    <h2>Hospital Management System</h2>
    <h3>Initial Administrator Setup</h3>

    <?php if ($message): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>

        <p>You can now log in using your new Administrator credentials.</p>
        <a href="../user-login.php">Go to Login</a>

    <?php elseif ($admin_exists): ?>

        <p>An Administrator account already exists.</p>
        <p>For security, this setup page cannot create another one.</p>

    <?php else: ?>

        <form method="POST" action="">

            <div>
                <label>Username</label><br>
                <input type="text" name="username" required>
            </div>

            <br>

            <div>
                <label>Password</label><br>
                <input type="password" name="password" required>
            </div>

            <br>

            <div>
                <label>Confirm Password</label><br>
                <input type="password" name="confirm_password" required>
            </div>

            <br>

            <button type="submit">Create Administrator Account</button>

        </form>

    <?php endif; ?>

</body>
</html>
