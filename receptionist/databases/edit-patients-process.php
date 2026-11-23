<?php
// Included by receptionist/edit-patient.php before any HTML output.
// These paths follow your working add-patients-process.php structure.
require_once __DIR__ . '/../includes/receptionist-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = 'Edit Patient';
header('Cache-Control: no-store, private');
header('Pragma: no-cache');

function escapeEditPatient($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function displayEditPatient($value): string
{
    return trim((string) ($value ?? '')) === '' ? 'Not provided' : escapeEditPatient($value);
}

function maskEditDocument($value): string
{
    $value = (string) ($value ?? '');
    return $value === '' ? 'Not provided' : escapeEditPatient(
        str_repeat('*', max(0, strlen($value) - 4)) . substr($value, -4)
    );
}

function postedEditValue(string $field): string
{
    return isset($_POST[$field]) && is_string($_POST[$field])
        ? trim($_POST[$field])
        : '';
}

function nullableEditValue(string $value): ?string
{
    return $value === '' ? null : $value;
}

$patientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$patientId || $patientId < 1) {
    http_response_code(400);
    exit('Invalid patient ID.');
}

if (empty($_SESSION['patient_edit_csrf'])) {
    $_SESSION['patient_edit_csrf'] = bin2hex(random_bytes(32));
}

$citizenshipOptions = ['South African', 'Non-South African', 'Not confirmed'];
$documentOptions = ['South African ID', 'Passport', 'No document'];

// Read registered values, including fields that must NEVER be edited here.
$selectSql = <<<'SQL'
    SELECT patient_id, first_name, last_name, date_of_birth, gender, race,
           citizenship_status, document_type, id_number,
           passport_number, passport_issuing_country,
           contact_number, email, address, created_at
    FROM patients
    WHERE patient_id = ?
    LIMIT 1
SQL;
$stmt = $pdo->prepare($selectSql);
$stmt->execute([$patientId]);
$storedPatient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$storedPatient) {
    http_response_code(404);
    exit('Patient record not found.');
}

// The form includes ONLY editable fields. The protected values are shown
// separately as text and never appear in an UPDATE statement.
$form = [
    'first_name' => (string) $storedPatient['first_name'],
    'last_name' => (string) $storedPatient['last_name'],
    'citizenship_status' => (string) $storedPatient['citizenship_status'],
    'document_type' => (string) $storedPatient['document_type'],
    'id_number' => '', // Never expose stored identity document numbers on GET.
    'passport_number' => '',
    'passport_issuing_country' => (string) ($storedPatient['passport_issuing_country'] ?? ''),
    'contact_number' => (string) ($storedPatient['contact_number'] ?? ''),
    'email' => (string) ($storedPatient['email'] ?? ''),
    'address' => (string) ($storedPatient['address'] ?? ''),
];

$errors = [];
$generalError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $key) {
        $form[$key] = postedEditValue($key);
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['patient_edit_csrf'], $token)) {
        $generalError = 'Invalid request. Refresh the page and try again.';
    } elseif (
        array_key_exists('date_of_birth', $_POST) ||
        array_key_exists('gender', $_POST) ||
        array_key_exists('race', $_POST)
    ) {
        // Refuse attempted modifications, even though these fields are
        // excluded from the UPDATE query in all cases.
        $generalError = 'Date of birth, gender and population group cannot be edited.';
    } else {
        foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
            if ($form[$field] === '') {
                $errors[$field] = "$label is required.";
            } elseif (strlen($form[$field]) > 100) {
                $errors[$field] = "$label cannot exceed 100 characters.";
            }
        }

        if (!in_array($form['citizenship_status'], $citizenshipOptions, true)) {
            $errors['citizenship_status'] = 'Select a valid citizenship status.';
        }
        if (!in_array($form['document_type'], $documentOptions, true)) {
            $errors['document_type'] = 'Select a valid identification document type.';
        }

        if ($form['email'] !== '' &&
            (strlen($form['email']) > 150 || !filter_var($form['email'], FILTER_VALIDATE_EMAIL))) {
            $errors['email'] = 'Enter a valid email address (150 characters maximum).';
        }
        if ($form['contact_number'] !== '' &&
            (strlen($form['contact_number']) > 20 ||
             !preg_match('/\A\+?[0-9 ()-]{7,20}\z/', $form['contact_number']))) {
            $errors['contact_number'] = 'Enter a valid contact number.';
        }
        if (strlen($form['address']) > 1000) {
            $errors['address'] = 'Address cannot exceed 1000 characters.';
        }

        // Resolved document values are calculated after locking the patient,
        // so an unchanged document number can be retained without being
        // displayed in full in the edit form.
        if (!$errors) {
            try {
                $pdo->beginTransaction();

                $lock = $pdo->prepare(
                    'SELECT patient_id, first_name, last_name, citizenship_status,
                            document_type, id_number, passport_number,
                            passport_issuing_country, contact_number, email, address
                     FROM patients WHERE patient_id = ? FOR UPDATE'
                );
                $lock->execute([$patientId]);
                $current = $lock->fetch(PDO::FETCH_ASSOC);
                if (!$current) {
                    throw new RuntimeException('Patient record no longer exists.');
                }

                $idNumber = null;
                $passportNumber = null;
                $issuingCountry = null;

                if ($form['document_type'] === 'South African ID') {
                    $candidate = $form['id_number'];
                    if ($candidate === '' && $current['document_type'] === 'South African ID') {
                        $candidate = (string) ($current['id_number'] ?? '');
                    }
                    if (!preg_match('/\A[0-9]{13}\z/', $candidate)) {
                        $errors['id_number'] = 'Enter exactly 13 digits for the South African ID.';
                    } else {
                        $idNumber = $candidate;
                    }
                } elseif ($form['document_type'] === 'Passport') {
                    $candidate = strtoupper($form['passport_number']);
                    if ($candidate === '' && $current['document_type'] === 'Passport') {
                        $candidate = (string) ($current['passport_number'] ?? '');
                    }
                    $country = $form['passport_issuing_country'];
                    if ($country === '' && $current['document_type'] === 'Passport') {
                        $country = (string) ($current['passport_issuing_country'] ?? '');
                    }
                    if (!preg_match('/\A[A-Z0-9\/\- ]{3,50}\z/', $candidate)) {
                        $errors['passport_number'] =
                            'Passport number must have 3–50 letters, digits, spaces, / or -.';
                    } else {
                        $passportNumber = $candidate;
                    }
                    if ($country === '' || strlen($country) > 100) {
                        $errors['passport_issuing_country'] =
                            'Passport issuing country is required (100 characters maximum).';
                    } else {
                        $issuingCountry = $country;
                    }
                }
                // 'No document' intentionally clears both identity document types.

                if (!$errors && $idNumber !== null) {
                    $check = $pdo->prepare(
                        'SELECT patient_id FROM patients
                         WHERE id_number = ? AND patient_id <> ? LIMIT 1'
                    );
                    $check->execute([$idNumber, $patientId]);
                    if ($check->fetchColumn()) {
                        $errors['id_number'] = 'Another patient already has this ID number.';
                    }
                }

                if (!$errors && $passportNumber !== null) {
                    $check = $pdo->prepare(
                        'SELECT patient_id FROM patients
                         WHERE passport_number = ? AND passport_issuing_country = ?
                           AND patient_id <> ? LIMIT 1'
                    );
                    $check->execute([$passportNumber, $issuingCountry, $patientId]);
                    if ($check->fetchColumn()) {
                        $errors['passport_number'] =
                            'Another patient already has this passport and issuing country.';
                    }
                }

                if ($errors) {
                    $pdo->rollBack();
                } else {
                    $updated = [
                        'first_name' => $form['first_name'],
                        'last_name' => $form['last_name'],
                        'citizenship_status' => $form['citizenship_status'],
                        'document_type' => $form['document_type'],
                        'id_number' => $idNumber,
                        'passport_number' => $passportNumber,
                        'passport_issuing_country' => $issuingCountry,
                        'contact_number' => nullableEditValue($form['contact_number']),
                        'email' => nullableEditValue($form['email']),
                        'address' => nullableEditValue($form['address']),
                    ];

                    $changed = false;
                    foreach ($updated as $field => $value) {
                        if (($current[$field] ?? null) !== $value) {
                            $changed = true;
                            break;
                        }
                    }

                    if ($changed) {
                        // Deliberately exclude date_of_birth, gender and race.
                        $update = $pdo->prepare(<<<'SQL'
                            UPDATE patients SET
                                first_name = :first_name,
                                last_name = :last_name,
                                citizenship_status = :citizenship_status,
                                document_type = :document_type,
                                id_number = :id_number,
                                passport_number = :passport_number,
                                passport_issuing_country = :passport_issuing_country,
                                contact_number = :contact_number,
                                email = :email,
                                address = :address
                            WHERE patient_id = :patient_id
                            SQL
                        );
                        $update->execute([
                            ':first_name' => $updated['first_name'],
                            ':last_name' => $updated['last_name'],
                            ':citizenship_status' => $updated['citizenship_status'],
                            ':document_type' => $updated['document_type'],
                            ':id_number' => $updated['id_number'],
                            ':passport_number' => $updated['passport_number'],
                            ':passport_issuing_country' => $updated['passport_issuing_country'],
                            ':contact_number' => $updated['contact_number'],
                            ':email' => $updated['email'],
                            ':address' => $updated['address'],
                            ':patient_id' => $patientId,
                        ]);

                        $audit = $pdo->prepare(
                            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id)
                             VALUES (?, ?, ?, ?)'
                        );
                        $audit->execute([
                            (int) $_SESSION['user_id'],
                            'Updated patient demographic information',
                            'Patient',
                            $patientId,
                        ]);
                    }

                    $pdo->commit();
                    $_SESSION['patient_edit_success'] = $changed
                        ? 'Patient information updated successfully.'
                        : 'No changes were needed.';
                    $_SESSION['patient_edit_csrf'] = bin2hex(random_bytes(32));
                    header('Location: patient-details.php?id=' . $patientId, true, 303);
                    exit;
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                // PDOException extends RuntimeException, so check it first.
                if ($e instanceof PDOException) {
                    error_log($e->getMessage());
                    if ($e->getCode() === '23000' &&
                        (int) ($e->errorInfo[1] ?? 0) === 1062) {
                        $generalError = 'That identity document already belongs to another patient.';
                    } else {
                        $generalError = 'Unable to update patient information. Please try again.';
                    }
                } elseif ($e instanceof RuntimeException) {
                    $generalError = $e->getMessage();
                } else {
                    error_log($e->getMessage());
                    $generalError = 'Unable to update patient information. Please try again.';
                }
            }
        }
    }
}

// Follow the same shared layout convention as add-patients-process.php.
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
