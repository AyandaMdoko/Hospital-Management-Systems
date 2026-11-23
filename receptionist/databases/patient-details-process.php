
<?php

require_once __DIR__ . '/../includes/receptionist-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = "Patient Details";

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function escapeDetails($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function displayPatientValue($value) {
    if ($value === null || trim((string)$value) === '') {
        return 'N/A';
    }

    return escapeDetails($value);
}

function maskPatientDocument($value) {
    if ($value === null || trim((string)$value) === '') {
        return 'N/A';
    }

    $value = (string)$value;

    return escapeDetails(
        str_repeat(
            '*',
            max(0, strlen($value) - 4)
        ) . substr($value, -4)
    );
}

// ==========================================
// VALIDATE PATIENT ID
// ==========================================

$patientId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$patientId || $patientId < 1) {
    http_response_code(400);
    exit('Invalid patient ID.');
}

// ==========================================
// RETRIEVE PATIENT DETAILS
// ==========================================

$stmt = $pdo->prepare("
    SELECT
        patient_id,
        id_number,
        passport_number,
        passport_issuing_country,
        first_name,
        last_name,
        date_of_birth,
        gender,
        race,
        citizenship_status,
        document_type,
        email,
        contact_number,
        address,
        created_at
    FROM patients
    WHERE patient_id = ?
    LIMIT 1
");

$stmt->execute([$patientId]);

$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    http_response_code(404);
    exit('Patient record not found.');
}

$fullName = trim(
    $patient['first_name'] . ' ' .
    $patient['last_name']
);

$registrationDate = date(
    'd M Y, h:i A',
    strtotime($patient['created_at'])
);

$dateOfBirth = !empty($patient['date_of_birth'])
    ? date(
        'd M Y',
        strtotime($patient['date_of_birth'])
    )
    : 'N/A';


$editSuccess =
    $_SESSION['patient_edit_success'] ?? '';

unset($_SESSION['patient_edit_success']);


?>



<?php require_once __DIR__ . '/../includes/header.php'; ?>

<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>