
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = "Consultation Details";

// Safely display database values.
function escapeConsultationDetail($value) {
    if ($value === null || trim((string)$value) === '') {
        return 'N/A';
    }

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format consultation dates.
function formatDetailDate($date) {
    if (empty($date)) {
        return 'N/A';
    }

    $timestamp = strtotime($date);

    return $timestamp !== false
        ? date('d M Y, g:i A', $timestamp)
        : 'N/A';
}

// Validate the consultation ID.
$consultationId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$consultationId || $consultationId < 1) {
    http_response_code(400);
    exit('Invalid consultation ID.');
}

// Retrieve the consultation and related records.
$stmt = $pdo->prepare("
    SELECT
        c.consultation_id,
        c.patient_id,
        c.consultation_date,
        c.symptoms,
        c.diagnosis,
        c.treatment_plan,
        c.notes,
        c.consultation_status,

        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        p.date_of_birth,
        p.gender,

        s.first_name AS staff_first_name,
        s.last_name AS staff_last_name,
        s.professional_role,
        s.department,
        s.specialization

    FROM consultations c

    INNER JOIN patients p
        ON c.patient_id = p.patient_id

    INNER JOIN staff s
        ON c.staff_id = s.staff_id

    WHERE c.consultation_id = ?
    LIMIT 1
");

$stmt->execute([$consultationId]);
$consultation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$consultation) {
    http_response_code(404);
    exit('Consultation not found.');
}

$statusClass =
    $consultation['consultation_status'] === 'Completed'
    ? 'completed'
    : 'in-progress';
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>