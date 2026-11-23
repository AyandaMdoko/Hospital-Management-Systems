

<?php

// Reuse shared authentication and account-status checks.
require_once __DIR__ . '/../../includes/auth.php';

// Only Receptionists may access these pages.
if (($_SESSION['role'] ?? '') !== 'Receptionist') {

    http_response_code(403);

    exit(
        'Access denied. This page is only available to Receptionists.'
    );
}

