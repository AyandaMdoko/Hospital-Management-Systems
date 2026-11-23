
<?php
$page_title = $page_title ?? 'Receptionist Dashboard';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars(
            $page_title,
            ENT_QUOTES,
            'UTF-8'
        ) ?> | HMS
    </title>

    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/patients.css">
    <link rel="stylesheet" href="./assets/css/waiting-list.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
</head>

<body>

<div class="dashboard-container">

    <header class="topbar">

        <div class="receptionist-brand">
            Hospital Management System
        </div>

        <div class="receptionist-user">
            <span>
                <?= htmlspecialchars(
                    $_SESSION['username'] ?? 'Receptionist',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

            <span class="receptionist-role">
                Receptionist
            </span>
        </div>

    </header>
