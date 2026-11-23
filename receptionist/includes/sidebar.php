<?php
/** Shared Receptionist navigation; no Administrator or Clinical-only links. */
$currentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$patientPages = [
    'patients.php',
    'add-patient.php',
    'patient-details.php',
    'edit-patient.php',
    'check-in.php',
];
$isDashboard = $currentPage === 'dashboard.php';
$isPatients = in_array($currentPage, $patientPages, true);
$isWaitingList = $currentPage === 'waiting-list.php';
?>
<aside class="sidebar receptionist-sidebar">
    <div class="receptionist-sidebar-heading">RECEPTION</div>
    <nav class="receptionist-navigation" aria-label="Receptionist navigation">
        <a href="dashboard.php"
           class="receptionist-nav-link<?= $isDashboard ? ' active' : '' ?>"
           <?= $isDashboard ? 'aria-current="page"' : '' ?>>
            Dashboard
        </a>

        <a href="patients.php"
           class="receptionist-nav-link<?= $isPatients ? ' active' : '' ?>"
           <?= $isPatients ? 'aria-current="page"' : '' ?>>
            Patients
        </a>

        <a href="waiting-list.php"
           class="receptionist-nav-link<?= $isWaitingList ? ' active' : '' ?>"
           <?= $isWaitingList ? 'aria-current="page"' : '' ?>>
            Today's Waiting List
        </a>

        <a href="../change-password.php" class="receptionist-nav-link">
            Change Password
        </a>
        <a href="../config/logout.php" class="receptionist-nav-link receptionist-logout">
            Logout
        </a>
    </nav>
</aside>
