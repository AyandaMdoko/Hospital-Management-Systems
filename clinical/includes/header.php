<?php
/**
 * Shared Doctor / Professional Nurse layout header.
 * Every clinical page must require includes/clinical-auth.php before this file.
 */
$page_title = $page_title ?? 'Clinical Dashboard';
$clinicalUsername = (string) ($_SESSION['username'] ?? 'Clinical Staff');
$clinicalRoleRaw = (string) ($_SESSION['role'] ?? '');
$clinicalRoleLabel = $clinicalRoleRaw === 'Nurse' ? 'Professional Nurse' : ($clinicalRoleRaw === 'Doctor' ? 'Doctor' : 'Clinical Staff');
$clinicalInitial = strtoupper(substr(ltrim($clinicalUsername, '@'), 0, 1)) ?: 'C';

if (!function_exists('clinicalLayoutEscape')) {
    function clinicalLayoutEscape($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#102b46">
    <title><?= clinicalLayoutEscape($page_title) ?> | Hospital Management System</title>
    <!-- Clinical-only stylesheet avoids Administrator navigation styles. -->
    <link rel="stylesheet" href="/assets/css/clinical-dashboard.css?v=2">
</head>
<body class="clinical-app">
<div class="clinical-shell">
    <header class="clinical-topbar">
        <div class="clinical-topbar-left">
            <button
                type="button"
                id="clinical-menu-button"
                class="clinical-menu-button"
                aria-controls="clinical-sidebar"
                aria-expanded="false"
                aria-label="Open clinical navigation"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a class="clinical-brand" href="/clinical/dashboard.php" aria-label="HMS Clinical Dashboard">
                <span class="clinical-brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="1" y="1" width="34" height="34" rx="10" fill="#E5F4FF"/>
                        <path d="M18 9v18M9 18h18" stroke="#1C70B3" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="clinical-brand-copy">
                    <strong>Hospital Management System</strong>
                    <small>Clinical workspace</small>
                </span>
            </a>
        </div>

        <div class="clinical-account" aria-label="Signed-in clinical professional">
            <span class="clinical-account-avatar" aria-hidden="true"><?= clinicalLayoutEscape($clinicalInitial) ?></span>
            <span class="clinical-account-details">
                <strong><?= clinicalLayoutEscape($clinicalUsername) ?></strong>
                <small><?= clinicalLayoutEscape($clinicalRoleLabel) ?></small>
            </span>
            <span class="clinical-account-status"><span class="clinical-online-dot" aria-hidden="true"></span> Signed in</span>
        </div>
    </header>

    <div class="clinical-frame">
