
<?php
$page_title = "Administrator Dashboard";

require_once __DIR__ . "/../../config/dbconnect.php";

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

// Total registered patients
$stmt = $pdo->query("SELECT COUNT(*) FROM patients");
$total_patients = $stmt->fetchColumn();

// Total staff members
$stmt = $pdo->query("SELECT COUNT(*) FROM staff");
$total_staff = $stmt->fetchColumn();


// Count only active doctors with available schedules.
$stmt = $pdo->query("
    SELECT COUNT(DISTINCT s.staff_id)
    FROM staff s
    INNER JOIN doctor_availability da
        ON s.staff_id = da.staff_id
    WHERE s.professional_role = 'Doctor'
    AND s.staff_status = 'Active'
    AND da.schedule_status = 'Available'
");

 $available_doctors = $stmt->fetchColumn();


// Completed consultations
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM consultations
    WHERE consultation_status = 'Completed'
");
$completed_consultations = $stmt->fetchColumn();


 // Doctor availability overview - active doctors only.
 $stmt = $pdo->query("
     SELECT
         s.first_name,
         s.last_name,
         da.day_of_week,
         da.start_time,
         da.end_time,
         da.schedule_status
     FROM doctor_availability da
     INNER JOIN staff s
         ON da.staff_id = s.staff_id
     WHERE s.professional_role = 'Doctor'
       AND s.staff_status = 'Active'
     ORDER BY FIELD(
         da.day_of_week,
         'Monday', 'Tuesday', 'Wednesday',
         'Thursday', 'Friday', 'Saturday', 'Sunday'
     )
     LIMIT 5
 ");

 $doctor_schedules = $stmt->fetchAll();

// Recent consultations overview
$stmt = $pdo->query("
    SELECT
        c.consultation_id,
        c.consultation_date,
        c.diagnosis,
        c.consultation_status,
        p.first_name AS patient_first_name,
        p.last_name AS patient_last_name,
        s.first_name AS staff_first_name,
        s.last_name AS staff_last_name
    FROM consultations c
    INNER JOIN patients p
        ON c.patient_id = p.patient_id
    INNER JOIN staff s
        ON c.staff_id = s.staff_id
    ORDER BY c.consultation_date DESC,
             c.consultation_id DESC
    LIMIT 5
");

$recent_consultations = $stmt->fetchAll();


?>

<?php include __DIR__ . "/../includes/header.php"; ?>

<?php include __DIR__ . "/../includes/sidebar.php"; ?>