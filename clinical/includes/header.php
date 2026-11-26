<?php
$page_title = $page_title ?? 'Clinical Dashboard';
$current_user = $_SESSION['username'] ?? 'Clinical User';
$current_role = $_SESSION['role'] ?? 'Clinical Staff';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Hospital Management System</title>

    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/clinical-dashboard.css">
    <link rel="stylesheet" href="./assets/css/clinical-ui-polish.css"> 
    <link rel="stylesheet" href="./assets/css/clinical-waiting-list-additions.css">
    <link rel="stylesheet" href="./assets/css/clinical-consultation.css"> 
    <link rel="stylesheet" href="./assets/css/clinical-patient-records.css?">
    <link rel="stylesheet" href="./assets/css/clinical-observations.css?">
    <link rel="stylesheet" href="./assets/css/clinical-observation-validation.css?">


</head>
<body>

<div class="clinical-layout">

    <header class="clinical-topbar">
        <div class="topbar-left">
            <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle navigation">
                ☰
            </button>

            <div class="clinical-brand">
                <div class="brand-icon">✚</div>
                <div class="brand-text">
                    <h1>Hospital Management System</h1>
                    <p>Clinical workspace</p>
                </div>
            </div>
        </div>

        <div class="topbar-right">
            <div class="clinical-user">
                <div class="user-avatar">
                    <?= strtoupper(substr($current_role, 0, 1)) ?>
                </div>
                <div class="user-meta">
                   <strong><?= htmlspecialchars(
                            $current_user,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></strong>
                    <span><?= htmlspecialchars($current_role) ?></span>
                </div>
            </div>
        </div>
    </header>