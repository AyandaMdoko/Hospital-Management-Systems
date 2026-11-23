
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = "Edit Doctor Schedule";

function escapeSchedule($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function scheduleInput($field) {
    return isset($_POST[$field]) && is_string($_POST[$field])
        ? trim($_POST[$field])
        : '';
}

$days = [
    'Monday', 'Tuesday', 'Wednesday',
    'Thursday', 'Friday', 'Saturday', 'Sunday'
];

$statuses = ['Available', 'Unavailable'];

// Validate the schedule ID.
$availabilityId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$availabilityId || $availabilityId < 1) {
    http_response_code(400);
    exit("Invalid schedule ID.");
}

// Retrieve the existing schedule.
$stmt = $pdo->prepare("
    SELECT
        da.availability_id,
        da.staff_id,
        da.day_of_week,
        da.start_time,
        da.end_time,
        da.schedule_status,
        s.first_name,
        s.last_name,
        s.department
    FROM doctor_availability da
    INNER JOIN staff s
        ON da.staff_id = s.staff_id
    WHERE da.availability_id = ?
      AND s.professional_role = 'Doctor'
      AND s.staff_status = 'Active'
    LIMIT 1
");

$stmt->execute([$availabilityId]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$schedule) {
    http_response_code(404);
    exit("Schedule not found or doctor is inactive.");
}

// CSRF protection.
if (empty($_SESSION['edit_availability_csrf'])) {
    $_SESSION['edit_availability_csrf'] =
        bin2hex(random_bytes(32));
}

$errors = [];

$success = $_SESSION['edit_availability_success'] ?? '';
unset($_SESSION['edit_availability_success']);

$form = [
    'day_of_week' => $schedule['day_of_week'],
    'start_time' => substr($schedule['start_time'], 0, 5),
    'end_time' => substr($schedule['end_time'], 0, 5),
    'schedule_status' => $schedule['schedule_status']
];

// Handle form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($form as $field => $value) {
        $form[$field] = scheduleInput($field);
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION['edit_availability_csrf'],
            $token
        )
    ) {
        $errors[] = "Invalid form submission.";
    }

    if (!in_array($form['day_of_week'], $days, true)) {
        $errors[] = "Please select a valid weekday.";
    }

    if (!in_array(
        $form['schedule_status'],
        $statuses,
        true
    )) {
        $errors[] = "Please select a valid schedule status.";
    }

    $timePattern = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';

    if (
        !preg_match($timePattern, $form['start_time']) ||
        !preg_match($timePattern, $form['end_time'])
    ) {
        $errors[] = "Please enter valid working hours.";

    } elseif ($form['end_time'] <= $form['start_time']) {
        $errors[] = "End Time must be later than Start Time.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Lock the existing schedule and linked doctor.
            $check = $pdo->prepare("
                SELECT
                    da.staff_id
                FROM doctor_availability da
                INNER JOIN staff s
                    ON da.staff_id = s.staff_id
                WHERE da.availability_id = ?
                  AND s.professional_role = 'Doctor'
                  AND s.staff_status = 'Active'
                FOR UPDATE
            ");

            $check->execute([$availabilityId]);
            $current = $check->fetch(PDO::FETCH_ASSOC);

            if (!$current) {
                throw new RuntimeException(
                    "The schedule is no longer editable."
                );
            }

            // Prevent duplicate weekdays for the same doctor.
            $duplicate = $pdo->prepare("
                SELECT availability_id
                FROM doctor_availability
                WHERE staff_id = ?
                  AND day_of_week = ?
                  AND availability_id <> ?
                LIMIT 1
            ");

            $duplicate->execute([
                $current['staff_id'],
                $form['day_of_week'],
                $availabilityId
            ]);

            if ($duplicate->fetch()) {
                throw new RuntimeException(
                    "This doctor already has a schedule for " .
                    $form['day_of_week'] . "."
                );
            }

            // Update the existing schedule.
            $update = $pdo->prepare("
                UPDATE doctor_availability
                SET day_of_week = ?,
                    start_time = ?,
                    end_time = ?,
                    schedule_status = ?
                WHERE availability_id = ?
            ");

            $update->execute([
                $form['day_of_week'],
                $form['start_time'],
                $form['end_time'],
                $form['schedule_status'],
                $availabilityId
            ]);

            // Record the action.
            $log = $pdo->prepare("
                INSERT INTO audit_logs
                    (user_id, action, entity_type, entity_id)
                VALUES (?, ?, ?, ?)
            ");

            $log->execute([
                $_SESSION['user_id'],
                'Updated doctor availability schedule',
                'doctor_availability',
                $availabilityId
            ]);

            $pdo->commit();

            $_SESSION['edit_availability_success'] =
                "Doctor schedule updated successfully.";

            header(
                "Location: edit-availability.php?id=" .
                $availabilityId
            );
            exit;

        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $e->getMessage();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] = $e->getCode() === '23000'
                ? "This doctor already has a schedule for that day."
                : "Unable to update the schedule.";
        }
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>