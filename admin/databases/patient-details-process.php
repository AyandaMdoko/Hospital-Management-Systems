
<?php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . "/../../config/dbconnect.php";

$page_title = "Patient Details";

// Escape database content before displaying it.
function patientText($value) {
    if ($value === null || trim((string)$value) === '') {
        return 'N/A';
    }

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format database dates.
function patientDate($value, $includeTime = false) {
    if (empty($value)) {
        return 'N/A';
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return 'N/A';
    }

    return date(
        $includeTime ? 'd M Y, h:i A' : 'd M Y',
        $timestamp
    );
}

// Validate the selected patient ID.
$patientId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$patientId || $patientId < 1) {
    http_response_code(400);
    exit('Invalid patient ID.');
}

// Retrieve the selected patient.
$stmt = $pdo->prepare("
    SELECT
        patient_id,
        id_number,
        first_name,
        last_name,
        date_of_birth,
        gender,
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
    exit('Patient not found.');
}

// Retrieve the patient's consultation history.
$stmt = $pdo->prepare("
    SELECT
        c.consultation_id,
        c.consultation_date,
        c.symptoms,
        c.diagnosis,
        c.treatment_plan,
        c.notes,
        c.consultation_status,
        s.first_name AS staff_first_name,
        s.last_name AS staff_last_name,
        s.professional_role
    FROM consultations c
    INNER JOIN staff s
        ON c.staff_id = s.staff_id
    WHERE c.patient_id = ?
    ORDER BY
        c.consultation_date DESC,
        c.consultation_id DESC
");

$stmt->execute([$patientId]);
$consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Retrieve the patient's nursing observations.
$stmt = $pdo->prepare("
    SELECT
        no.observation_id,
        no.observation_date,
        no.observation_type,
        no.observation_value,
        no.notes,
        s.first_name AS nurse_first_name,
        s.last_name AS nurse_last_name
    FROM nursing_observations no
    INNER JOIN staff s
        ON no.staff_id = s.staff_id
    WHERE no.patient_id = ?
    ORDER BY
        no.observation_date DESC,
        no.observation_id DESC
");

$stmt->execute([$patientId]);
$observations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>