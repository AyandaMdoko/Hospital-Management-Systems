
<?php
$page_title = $page_title ?? "Administrator Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($page_title); ?> | HMS</title>

    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/add-user&staff.css">
    <link rel="stylesheet" href="./assets/css/patient.css">
    <link rel="stylesheet" href="./assets/css/availability.css">
    <link rel="stylesheet" href="./assets/css/audit-logs.css">
    <link rel="stylesheet" href="./assets/css/profile.css">
</head>

<body>

<div class="dashboard-container">

    <header class="topbar">

        <div class="brand">
            <span class="brand-icon">✚</span>
            <span>Hospital Management System</span>
        </div>

        <div class="topbar-right">
            <span class="admin-label">Administrator</span>
            <div class="profile-avatar">A</div>
        </div>

    </header>
