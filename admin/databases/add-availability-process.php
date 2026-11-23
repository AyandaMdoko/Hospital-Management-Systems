
<?php
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = "Add Doctor Schedule";

// Escape function for schedule values.
function escapeSchedule($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// Protect the form against CSRF attacks.
if (empty($_SESSION['add_availability_csrf'])) {
    $_SESSION['add_availability_csrf'] = bin2hex(random_bytes(32));
}

$days = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday'
];

$statuses = ['Available', 'Unavailable'];

$errors = [];

$success = $_SESSION['add_availability_success'] ?? '';
unset($_SESSION['add_availability_success']);

$form = [
    'staff_id' => '',
    'day_of_week' => '',
    'start_time' => '',
    'end_time' => '',
    'schedule_status' => 'Available'
];

// Retrieve active doctors for the dropdown.
$doctorStmt = $pdo->query("
    SELECT
        staff_id,
        first_name,
        last_name,
        department
    FROM staff
    WHERE professional_role = 'Doctor'
      AND staff_status = 'Active'
    ORDER BY first_name, last_name
");

$doctors = $doctorStmt->fetchAll(PDO::FETCH_ASSOC);

// Process the form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($form as $field => $value) {
        $submitted = $_POST[$field] ?? '';

        $form[$field] = is_string($submitted)
            ? trim($submitted)
            : '';
    }

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION['add_availability_csrf'],
            $token
        )
    ) {
        $errors[] = 'Invalid form submission.';
    }

    $staffId = filter_var(
        $form['staff_id'],
        FILTER_VALIDATE_INT
    );

    if (!$staffId || $staffId < 1) {
        $errors[] = 'Please select a doctor.';
    }

    if (!in_array($form['day_of_week'], $days, true)) {
        $errors[] = 'Please select a valid day.';
    }

    if (!in_array(
        $form['schedule_status'],
        $statuses,
        true
    )) {
        $errors[] = 'Please select a valid status.';
    }

    $timePattern = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';

    if (
        !preg_match($timePattern, $form['start_time']) ||
        !preg_match($timePattern, $form['end_time'])
    ) {
        $errors[] = 'Please enter valid working hours.';
    } elseif ($form['end_time'] <= $form['start_time']) {
        $errors[] = 'End Time must be later than Start Time.';
    }

    if (empty($errors)) {

        try {
            $pdo->beginTransaction();

            // Confirm that the doctor is still active.
            $checkDoctor = $pdo->prepare("
                SELECT staff_id
                FROM staff
                WHERE staff_id = ?
                  AND professional_role = 'Doctor'
                  AND staff_status = 'Active'
                FOR UPDATE
            ");

            $checkDoctor->execute([$staffId]);

            if (!$checkDoctor->fetch()) {
                throw new RuntimeException(
                    'The selected doctor is not active.'
                );
            }

            // Check for an existing schedule on this day.
            $checkSchedule = $pdo->prepare("
                SELECT availability_id
                FROM doctor_availability
                WHERE staff_id = ?
                  AND day_of_week = ?
                LIMIT 1
            ");

            $checkSchedule->execute([
                $staffId,
                $form['day_of_week']
            ]);

            if ($checkSchedule->fetch()) {
                throw new RuntimeException(
                    'This doctor already has a schedule for ' .
                    $form['day_of_week'] . '.'
                );
            }

            // Insert the new schedule.
            $insert = $pdo->prepare("
                INSERT INTO doctor_availability
                (
                    staff_id,
                    day_of_week,
                    start_time,
                    end_time,
                    schedule_status
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $insert->execute([
                $staffId,
                $form['day_of_week'],
                $form['start_time'],
                $form['end_time'],
                $form['schedule_status']
            ]);

            $availabilityId = (int)$pdo->lastInsertId();

            // Record the action in the audit log.
            $audit = $pdo->prepare("
                INSERT INTO audit_logs
                (
                    user_id,
                    action,
                    entity_type,
                    entity_id
                )
                VALUES (?, ?, ?, ?)
            ");

            $audit->execute([
                $_SESSION['user_id'],
                'Created doctor availability schedule',
                'doctor_availability',
                $availabilityId
            ]);

            $pdo->commit();

            $_SESSION['add_availability_success'] =
                'Doctor schedule added successfully.';

            header('Location: add-availability.php', true, 303);
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
                ? 'This doctor already has a schedule for that day, or the record conflicts with an existing constraint.'
                : 'An error occurred while saving the schedule.';
        }
    }
}
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>