
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . "/../../config/dbconnect.php";

$page_title = "Doctor Availability";

// Escape values before displaying them.
function escapeAvailability($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Format working hours.
function formatScheduleTime($time) {
    if (empty($time)) {
        return 'N/A';
    }

    $timestamp = strtotime($time);

    return $timestamp !== false
        ? date('g:i A', $timestamp)
        : 'N/A';
}

// Retrieve active doctors and their weekly schedules.
// LEFT JOIN also includes doctors without schedules.
$stmt = $pdo->query("
    SELECT
        s.staff_id,
        s.first_name,
        s.last_name,
        s.department,
        da.availability_id,
        da.day_of_week,
        da.start_time,
        da.end_time,
        da.schedule_status
    FROM staff s
    LEFT JOIN doctor_availability da
        ON s.staff_id = da.staff_id
    WHERE s.professional_role = 'Doctor'
      AND s.staff_status = 'Active'
    ORDER BY
        s.last_name ASC,
        s.first_name ASC,
        FIELD(
            da.day_of_week,
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday'
        )
");

$doctorSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate listing totals.
$doctorIds = [];
$totalSchedules = 0;

foreach ($doctorSchedules as $schedule) {
    $doctorIds[$schedule['staff_id']] = true;

    if ($schedule['availability_id'] !== null) {
        $totalSchedules++;
    }
}

$totalDoctors = count($doctorIds);
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>