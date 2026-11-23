<?php
/**
 * Shared navigation: Doctor and Nurse see the same menu.
 * Unimplemented pages remain non-clickable until their auth and features exist.
 */
$clinicalCurrentPage = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'dashboard.php'));

if (!function_exists('clinicalNavIcon')) {
    function clinicalNavIcon(string $name): string
    {
        $paths = [
            'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'queue' => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>',
            'patients' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20v-1.2A6.3 6.3 0 0 1 8.8 12.5h.4a6.3 6.3 0 0 1 6.3 6.3V20"/><path d="M17 6.8a3.2 3.2 0 0 1 0 6.3M18 14.5a5.5 5.5 0 0 1 3.5 5.1"/>',
            'clipboard' => '<rect x="5" y="5" width="14" height="16" rx="2"/><path d="M9 5.5V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1.5M9 12h6M9 16h6"/>',
            'pulse' => '<path d="M3 12h4l3-6 4 12 3-6h4"/>',
            'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
            'key' => '<rect x="3" y="10" width="18" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
            'logout' => '<path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/><path d="M14 8l4 4-4 4M8 12h10"/>',
        ];
        $content = $paths[$name] ?? $paths['dashboard'];
        return '<svg class="clinical-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $content . '</svg>';
    }
}
?>
<aside id="clinical-sidebar" class="clinical-sidebar" aria-label="Clinical navigation">
    <div class="clinical-sidebar-intro">
        <span class="clinical-sidebar-intro-icon" aria-hidden="true">+</span>
        <div>
            <strong>Clinical Care</strong>
            <small>Doctor &amp; Nurse portal</small>
        </div>
    </div>

    <nav class="clinical-nav" aria-label="Primary clinical navigation">
        <div class="clinical-nav-section">WORKSPACE</div>
        <a href="/clinical/dashboard.php"
           class="clinical-nav-item <?= $clinicalCurrentPage === 'dashboard.php' ? 'clinical-active' : '' ?>"
           <?= $clinicalCurrentPage === 'dashboard.php' ? 'aria-current="page"' : '' ?>>
            <?= clinicalNavIcon('dashboard') ?><span class="clinical-nav-text">Dashboard</span>
        </a>

        <span class="clinical-nav-item clinical-disabled" aria-disabled="true">
            <?= clinicalNavIcon('queue') ?><span class="clinical-nav-text">Patient Waiting List</span><small class="clinical-nav-tag">Soon</small>
        </span>
        <span class="clinical-nav-item clinical-disabled" aria-disabled="true">
            <?= clinicalNavIcon('patients') ?><span class="clinical-nav-text">Patient Medical Records</span><small class="clinical-nav-tag">Soon</small>
        </span>
        <span class="clinical-nav-item clinical-disabled" aria-disabled="true">
            <?= clinicalNavIcon('clipboard') ?><span class="clinical-nav-text">Consultations</span><small class="clinical-nav-tag">Soon</small>
        </span>
        <span class="clinical-nav-item clinical-disabled" aria-disabled="true">
            <?= clinicalNavIcon('pulse') ?><span class="clinical-nav-text">Clinical Observations</span><small class="clinical-nav-tag">Soon</small>
        </span>
        <span class="clinical-nav-item clinical-disabled" aria-disabled="true">
            <?= clinicalNavIcon('history') ?><span class="clinical-nav-text">Consultation History</span><small class="clinical-nav-tag">Soon</small>
        </span>

        <div class="clinical-nav-section clinical-account-nav-title">ACCOUNT</div>
        <a href="/change-password.php" class="clinical-nav-item">
            <?= clinicalNavIcon('key') ?><span class="clinical-nav-text">Change Password</span>
        </a>
        <a href="/config/logout.php" class="clinical-nav-item clinical-signout">
            <?= clinicalNavIcon('logout') ?><span class="clinical-nav-text">Logout</span>
        </a>
    </nav>

    <div class="clinical-sidebar-bottom">
        <span class="clinical-sidebar-bottom-mark" aria-hidden="true">✚</span>
        <div>
            <strong>Patient information</strong>
            <p>Access records only for authorized patient care.</p>
        </div>
    </div>
</aside>
<button type="button" id="clinical-sidebar-backdrop" class="clinical-sidebar-backdrop" aria-label="Close navigation"></button>
