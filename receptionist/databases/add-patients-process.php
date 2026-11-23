<?php
// This file is included by receptionist/add-patient.php BEFORE HTML output.
require_once __DIR__ . '/../includes/receptionist-auth.php';
require_once __DIR__ . '/../../config/dbconnect.php';

$page_title = 'Register Patient';
header('Cache-Control: no-store, private');

if (empty($_SESSION['patient_registration_csrf'])) {
    $_SESSION['patient_registration_csrf'] = bin2hex(random_bytes(32));
}

function escapeRegistration($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function getPatientInput(string $field): string
{
    return isset($_POST[$field]) && is_string($_POST[$field])
        ? trim($_POST[$field])
        : '';
}

$raceOptions = [
    'Black African',
    'Coloured',
    'Indian/Asian',
    'White',
    'Other',
    'Prefer not to say',
];
$citizenshipOptions = ['South African', 'Non-South African', 'Not confirmed'];
$documentOptions = ['South African ID', 'Passport', 'No document'];
$genderOptions = ['Male', 'Female', 'Other', 'Prefer not to say'];

$patient = [
    'first_name' => '',
    'last_name' => '',
    'date_of_birth' => '',
    'gender' => '',
    'race' => '',
    'citizenship_status' => '',
    'document_type' => '',
    'id_number' => '',
    'passport_number' => '',
    'passport_issuing_country' => '',
    'contact_number' => '',
    'email' => '',
    'address' => '',
];

$errors = [];
$generalError = '';

// Handle form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($patient) as $field) {
        $patient[$field] = getPatientInput($field);
    }

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['patient_registration_csrf'], $submittedToken)
    ) {
        $generalError = 'Invalid request. Please refresh the page and try again.';
    } else {
        // Required names.
        foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $field => $label) {
            if ($patient[$field] === '') {
                $errors[$field] = "$label is required.";
            } elseif (strlen($patient[$field]) > 100) {
                $errors[$field] = "$label cannot exceed 100 characters.";
            }
        }

        if ($patient['date_of_birth'] !== '') {
            $dob = DateTimeImmutable::createFromFormat('!Y-m-d', $patient['date_of_birth']);
            if (!$dob || $dob->format('Y-m-d') !== $patient['date_of_birth']) {
                $errors['date_of_birth'] = 'Enter a valid date of birth.';
            } elseif ($dob > new DateTimeImmutable('today')) {
                $errors['date_of_birth'] = 'Date of birth cannot be in the future.';
            }
        }

        if ($patient['gender'] !== '' && !in_array($patient['gender'], $genderOptions, true)) {
            $errors['gender'] = 'Select a valid gender option.';
        }
        if ($patient['race'] !== '' && !in_array($patient['race'], $raceOptions, true)) {
            $errors['race'] = 'Select a valid population group.';
        }
        if (!in_array($patient['citizenship_status'], $citizenshipOptions, true)) {
            $errors['citizenship_status'] = 'Select a valid citizenship status.';
        }
        if (!in_array($patient['document_type'], $documentOptions, true)) {
            $errors['document_type'] = 'Select a valid identification document type.';
        }

        if ($patient['email'] !== '') {
            if (strlen($patient['email']) > 150 ||
                !filter_var($patient['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Enter a valid email address (150 characters maximum).';
            }
        }

        if ($patient['contact_number'] !== '' &&
            (strlen($patient['contact_number']) > 20 ||
             !preg_match('/\A\+?[0-9 ()-]{7,20}\z/', $patient['contact_number']))) {
            $errors['contact_number'] = 'Enter a valid contact number.';
        }
        if (strlen($patient['address']) > 1000) {
            $errors['address'] = 'Address cannot exceed 1000 characters.';
        }

        // Citizenship and document type are separate. Only the selected
        // identification document may be stored for this registration.
        if ($patient['document_type'] === 'South African ID') {
            $patient['passport_number'] = '';
            $patient['passport_issuing_country'] = '';
            if (!preg_match('/\A[0-9]{13}\z/', $patient['id_number'])) {
                $errors['id_number'] = 'South African ID number must contain exactly 13 digits.';
            }
        } elseif ($patient['document_type'] === 'Passport') {
            $patient['id_number'] = '';
            $patient['passport_number'] = strtoupper($patient['passport_number']);
            if (!preg_match('/\A[A-Z0-9\/\- ]{3,50}\z/', $patient['passport_number'])) {
                $errors['passport_number'] =
                    'Enter a passport number (3–50 letters, digits, spaces, / or -).';
            }
            if ($patient['passport_issuing_country'] === '' ||
                strlen($patient['passport_issuing_country']) > 100) {
                $errors['passport_issuing_country'] =
                    'Enter the passport issuing country (100 characters maximum).';
            }
        } elseif ($patient['document_type'] === 'No document') {
            $patient['id_number'] = '';
            $patient['passport_number'] = '';
            $patient['passport_issuing_country'] = '';
        }

        // Preliminary duplicate check for a clearer form error.
        // Database UNIQUE constraints remain the final safeguard against races.
        if (empty($errors)) {
            try {
                if ($patient['document_type'] === 'South African ID') {
                    $check = $pdo->prepare(
                        'SELECT patient_id FROM patients WHERE id_number = ? LIMIT 1'
                    );
                    $check->execute([$patient['id_number']]);
                    if ($check->fetchColumn()) {
                        $errors['id_number'] = 'A patient with this ID number is already registered.';
                    }
                } elseif ($patient['document_type'] === 'Passport') {
                    $check = $pdo->prepare(
                        'SELECT patient_id FROM patients
                         WHERE passport_number = ? AND passport_issuing_country = ? LIMIT 1'
                    );
                    $check->execute([
                        $patient['passport_number'],
                        $patient['passport_issuing_country'],
                    ]);
                    if ($check->fetchColumn()) {
                        $errors['passport_number'] =
                            'A patient with this passport and issuing country is already registered.';
                    }
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $generalError = 'Unable to verify patient information. Please try again.';
            }
        }

        // If no validation errors, attempt to insert the new patient record.
        if (!$errors && $generalError === '') {
            try {
                $pdo->beginTransaction();

                $insert = $pdo->prepare(<<<SQL
                    INSERT INTO patients (
                        first_name, last_name, date_of_birth, gender, race,
                        citizenship_status, document_type,
                        id_number, passport_number, passport_issuing_country,
                        contact_number, email, address
                    ) VALUES (
                        :first_name, :last_name, :date_of_birth, :gender, :race,
                        :citizenship_status, :document_type,
                        :id_number, :passport_number, :passport_issuing_country,
                        :contact_number, :email, :address
                    )
                    SQL
                );

                $nullable = static function (string $value): ?string {
                    return $value === '' ? null : $value;
                };

                $insert->execute([
                    ':first_name' => $patient['first_name'],
                    ':last_name' => $patient['last_name'],
                    ':date_of_birth' => $nullable($patient['date_of_birth']),
                    ':gender' => $nullable($patient['gender']),
                    ':race' => $nullable($patient['race']),
                    ':citizenship_status' => $patient['citizenship_status'],
                    ':document_type' => $patient['document_type'],
                    ':id_number' => $nullable($patient['id_number']),
                    ':passport_number' => $nullable($patient['passport_number']),
                    ':passport_issuing_country' => $nullable($patient['passport_issuing_country']),
                    ':contact_number' => $nullable($patient['contact_number']),
                    ':email' => $nullable($patient['email']),
                    ':address' => $nullable($patient['address']),
                ]);

                $newPatientId = (int) $pdo->lastInsertId();

                $audit = $pdo->prepare(
                    'INSERT INTO audit_logs (user_id, action, entity_type, entity_id)
                     VALUES (?, ?, ?, ?)'
                );
                $audit->execute([
                    (int) $_SESSION['user_id'],
                    'Registered new patient',
                    'Patient',
                    $newPatientId,
                ]);

                $pdo->commit();

                $_SESSION['patient_registration_success'] = 'Patient registered successfully.';
                $_SESSION['patient_registration_csrf'] = bin2hex(random_bytes(32));
                header('Location: patients.php', true, 303);
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log($e->getMessage());

                if ($e->getCode() === '23000' &&
                    (int) ($e->errorInfo[1] ?? 0) === 1062) {
                    $keyMessage = (string) ($e->errorInfo[2] ?? '');
                    if (stripos($keyMessage, 'uq_patients_passport') !== false) {
                        $errors['passport_number'] = 'This passport is already registered for that country.';
                    } elseif (stripos($keyMessage, 'id_number') !== false) {
                        $errors['id_number'] = 'This South African ID number is already registered.';
                    } else {
                        $generalError = 'An existing record uses the same identity document.';
                    }
                } else {
                    $generalError = 'Unable to register patient. Please try again.';
                }
            }
        }
    }
}

// Your existing project includes these shared layout files here.
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
