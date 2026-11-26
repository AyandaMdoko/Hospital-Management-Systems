<?php
/**
 * HMS Clinical Observation Measurement Validation — academic prototype.
 * Reusable, side-effect-free checks. Does not access the database or session.
 *
 * Review thresholds are deliberately broad SOFTWARE FLAGS, NOT clinical
 * reference ranges or diagnostic advice. Clinical staff must approve and
 * maintain local measurement policy before real healthcare deployment.
 */
declare(strict_types=1);

function hmsObservationTypes(): array
{
    return [
        'Blood Pressure' => ['unit' => 'mmHg', 'example' => '120/80'],
        'Temperature' => ['unit' => '°C', 'example' => '36.8'],
        'Pulse Rate' => ['unit' => 'bpm', 'example' => '74'],
        'Respiratory Rate' => ['unit' => 'breaths/min', 'example' => '18'],
        'Oxygen Saturation' => ['unit' => '%', 'example' => '98'],
        'Weight' => ['unit' => 'kg', 'example' => '70.5'],
        'Height' => ['unit' => 'cm', 'example' => '172'],
        'Blood Glucose' => ['unit' => 'mmol/L', 'example' => '5.4'],
    ];
}

/**
 * Returns ['valid'=>bool, 'normalized'=>string, 'stored'=>string,
 *          'error'=>string, 'warnings'=>string[]].
 * Use for POST after checking supplied field types are strings.
 */
function hmsValidateObservation(string $type, string $input): array
{
    $result = ['valid' => false, 'normalized' => '', 'stored' => '', 'error' => '', 'warnings' => []];
    $types = hmsObservationTypes();
    if (!isset($types[$type])) {
        $result['error'] = 'Select a supported observation type.';
        return $result;
    }
    $input = trim($input);
    if ($input === '' || strlen($input) > 30) {
        $result['error'] = 'Enter a measured value of 30 characters or fewer, without a unit.';
        return $result;
    }

    if ($type === 'Blood Pressure') {
        if (!preg_match('/\A([0-9]{1,3})\s*\/\s*([0-9]{1,3})\z/D', $input, $matches)) {
            $result['error'] = 'Blood pressure must be written as systolic/diastolic, e.g. 120/80, without mmHg.';
            return $result;
        }
        $sys = (int)$matches[1];
        $dia = (int)$matches[2];
        if ($sys <= 0 || $dia <= 0 || $sys > 400 || $dia > 300 || $sys <= $dia) {
            $result['error'] = 'Check both blood-pressure numbers. They must be positive, with systolic greater than diastolic, and within the supported numeric limits.';
            return $result;
        }
        $result['normalized'] = "$sys/$dia";
        if ($sys < 60 || $sys > 250 || $dia < 25 || $dia > 160) {
            $result['warnings'][] = 'Blood pressure is outside the broad review range. Check the cuff, reading, patient and units; document verification.';
        }
    } else {
        $integerOnly = in_array($type, ['Pulse Rate', 'Respiratory Rate', 'Oxygen Saturation'], true);
        $pattern = $integerOnly
            ? '/\A[0-9]{1,3}\z/D'
            : '/\A[0-9]{1,3}(?:\.[0-9]{1,2})?\z/D';
        if (!preg_match($pattern, $input)) {
            $result['error'] = $integerOnly
                ? 'Enter a whole number without a unit (for example 74).'
                : 'Enter a number with up to two decimal places, without a unit (for example 36.8).';
            return $result;
        }
        $number = (float)$input;
        // The hard limits below are technical input sanity checks, not diagnoses.
        $bounds = [
            'Temperature' => [10.0, 50.0],
            'Pulse Rate' => [1.0, 400.0],
            'Respiratory Rate' => [1.0, 120.0],
            'Oxygen Saturation' => [1.0, 100.0],
            'Weight' => [0.1, 700.0],
            'Height' => [1.0, 300.0],
            'Blood Glucose' => [0.1, 150.0],
        ];
        [$min, $max] = $bounds[$type];
        if ($number < $min || $number > $max) {
            $result['error'] = 'Measurement is outside supported input limits for ' . $type
                . '. Check the measurement and its unit; do not enter a placeholder or estimated value.';
            return $result;
        }
        // Normalize leading/trailing zeros; preserve up to 2 decimal places.
        $result['normalized'] = $integerOnly
            ? (string)(int)$number
            : rtrim(rtrim(sprintf('%.2f', $number), '0'), '.');

        // Broad review flags: unusual is NOT equivalent to incorrect or unsafe.
        // Pediatric, adult and specialist settings need local clinical sign-off.
        $reviewLimits = [
            'Temperature' => [34.0, 42.0],
            'Pulse Rate' => [30.0, 220.0],
            'Respiratory Rate' => [6.0, 65.0],
            'Oxygen Saturation' => [85.0, 100.0],
            'Weight' => [0.5, 350.0],
            'Height' => [20.0, 230.0],
            'Blood Glucose' => [2.5, 25.0],
        ];
        [$reviewMin, $reviewMax] = $reviewLimits[$type];
        if ($number < $reviewMin || $number > $reviewMax) {
            $message = $type . ' is outside the configured review range ('
                . $reviewMin . '–' . $reviewMax . ' ' . $types[$type]['unit'] . '). Check the recorded measurement and unit.';
            if ($type === 'Blood Glucose') {
                $message .= ' In particular, confirm that the meter displayed mmol/L rather than mg/dL.';
            }
            $result['warnings'][] = $message;
        }
    }

    $result['valid'] = true;
    $result['stored'] = $result['normalized'] . ' ' . $types[$type]['unit'];
    return $result;
}

/**
 * Read-only display helper for EXISTING stored entries. Never edits data.
 * Returns warning text for recognizable values that fall outside current
 * review thresholds. Unknown legacy types are left unchanged/unclassified.
 */
function hmsStoredObservationWarnings(string $type, string $storedValue): array
{
    $types = hmsObservationTypes();
    if (!isset($types[$type])) {
        return [];
    }
    $unit = ' ' . $types[$type]['unit'];
    $storedValue = trim($storedValue);
    if ($unit === '' || !str_ends_with($storedValue, $unit)) {
        return ['Check the recorded unit or format before using this measurement.'];
    }
    $raw = substr($storedValue, 0, -strlen($unit));
    $check = hmsValidateObservation($type, $raw);
    if (!$check['valid']) {
        return ['Check the recorded measurement format or unit before using this value.'];
    }
    return $check['warnings'];
}
