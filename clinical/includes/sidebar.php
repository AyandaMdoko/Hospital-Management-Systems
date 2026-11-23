<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);

function clinicalNavActive($pages = []) {
    global $currentPage;
    return in_array($currentPage, $pages, true) ? 'active' : '';
}
?>

<aside class="clinical-sidebar" id="clinicalSidebar">

    <div class="sidebar-heading">
        <span>Clinical Care</span>
        <small>Doctor & Nurse Portal</small>
    </div>

    <nav class="sidebar-nav">

        <a href="dashboard.php" class="sidebar-link <?= clinicalNavActive(['dashboard.php']) ?>">
            <span class="nav-icon">⌂</span>
            <span class="nav-text">Dashboard</span>
        </a>

        <a href="waiting-list.php" class="sidebar-link <?= clinicalNavActive(['waiting-list.php']) ?>">
            <span class="nav-icon">☰</span>
            <span class="nav-text">Patient Waiting List</span>
        </a>

        <span class="sidebar-link disabled">
            <span class="nav-icon">🗂</span>
            <span class="nav-text">Patient Medical Records</span>
            <small>Soon</small>
        </span>

        <span class="sidebar-link disabled">
            <span class="nav-icon">✎</span>
            <span class="nav-text">Consultations</span>
            <small>Soon</small>
        </span>

        <span class="sidebar-link disabled">
            <span class="nav-icon">♡</span>
            <span class="nav-text">Clinical Observations</span>
            <small>Soon</small>
        </span>

        <span class="sidebar-link disabled">
            <span class="nav-icon">🕘</span>
            <span class="nav-text">Consultation History</span>
            <small>Soon</small>
        </span>

    </nav>

    <div class="sidebar-section-title">Account</div>

    <nav class="sidebar-nav">
        <a href="../change-password.php" class="sidebar-link">
            <span class="nav-icon">⚙</span>
            <span class="nav-text">Change Password</span>
        </a>

        <a href="../config/logout.php" class="sidebar-link logout-link">
            <span class="nav-icon">⇦</span>
            <span class="nav-text">Logout</span>
        </a>
    </nav>

</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>