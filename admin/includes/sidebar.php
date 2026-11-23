
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">

    <div class="sidebar-heading">
        ADMINISTRATION
    </div>

    <nav class="sidebar-nav">

        <a href="dashboard.php"
           class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <span>▦</span> Dashboard
        </a>

        <a href="users.php"
           class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <span>♙</span> User Management
        </a>

        <a href="staff.php"
           class="<?php echo $current_page == 'staff.php' ? 'active' : ''; ?>">
            <span>♧</span> Staff Management
        </a>

        <a href="patients.php"
           class="<?php echo $current_page == 'patients.php' ? 'active' : ''; ?>">
            <span>♡</span> Patients
        </a>

        <a href="availability.php"
           class="<?php echo $current_page == 'availability.php' ? 'active' : ''; ?>">
            <span>◷</span> Availability
        </a>

        <a href="consultations.php"
           class="<?php echo $current_page == 'consultations.php' ? 'active' : ''; ?>">
            <span>▤</span> Consultations
        </a>

        <a href="audit_logs.php"
           class="<?php echo $current_page == 'audit_logs.php' ? 'active' : ''; ?>">
            <span>◉</span> Audit Logs
        </a>

        <a href="profile.php"
           class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <span>⚙</span> My Profile
        </a>

        <div class="sidebar-divider"></div>

        <a href="../config/logout.php" class="logout-link">
            <span>⇥</span> Logout
        </a>

    </nav>

</aside>
