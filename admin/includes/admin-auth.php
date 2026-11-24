<?php

// Check login, account status, staff status,
// and password-change requirements.
require_once __DIR__ . '/../../includes/auth.php';

// Allow Administrators only.
if ($_SESSION['role'] !== 'Administrator') {
    http_response_code(403);

    exit(
        "Access denied. You are not authorized to access this page."
    );
}

?>

