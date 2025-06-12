<?php

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;

require 'vendor/autoload.php';

// Function to process CSV file
function processCSV($filename, $delimiter = ",", $enclosure = '"', $escape = "\\") {
    $results = [];
    $header = NULL;
    $row_count = 0;

    if (($handle = fopen($filename, "r")) !== FALSE) {
        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== FALSE) {
            $row_count++;
            if(!$header) {
                $header = $row;
            } else {
                $results[] = array_combine($header, $row);
            }
        }
        fclose($handle);
    }

    return $results;
}

// Function to validate emails
function validateEmails($emails, $validationTypes = []) {
    $results = [];

    $emailValidator = new EmailValidator();
    $multipleValidation = new MultipleValidationWithAnd([
        new RFCValidation(),
    ]);

    $validationStrategies = [];

    // Add DNS Check if selected
    if (in_array('dns_check', $validationTypes)) {
        $validationStrategies['DNS Check'] = new DNSCheckValidation();
        $multipleValidation->addValidation(new DNSCheckValidation());
    }

    // Add Spoof Check if selected
    if (in_array('spoof_check', $validationTypes)) {
        if (extension_loaded('intl')) {
            $validationStrategies['Spoof Check'] = new Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation();
        } else {
            echo '<div class="csv-info" style="background-color: #f8d7da; border-color: #dc3545;">';
            echo '<strong>Spoof Check Error:</strong> The intl extension is not loaded. Please install and enable it for Spoof Check to work.';
            echo '</div>';
        }
    }

    // Additional validation functions
    function validateEmailLength($email) {
        return strlen($email) <= 320;
    }

    function validateEmailPattern($email) {
        // Check for suspicious patterns
        if (strpos($email, '..') !== false) return false; // consecutive dots
        if (substr_count($email, '@') != 1) return false; // multiple @ symbols
        if (strpos($email, ' ') !== false) return false; // whitespace
        if (preg_match('/^[.\-_]|[.\-_]@/', $email)) return false; // starts with dot/dash
        if (preg_match('/@[.\-_]|[.\-_]$/', $email)) return false; // ends with dot/dash
        return true;
    }

    function isDisposableEmail($email) {
        $disposableDomains = [
            '10minutemail.com', 'tempmail.org', 'guerrillamail.com', 
            'mailinator.com', 'yopmail.com', 'temp-mail.org'
        ];
        $domain = substr(strrchr($email, "@"), 1);
        return in_array(strtolower($domain), $disposableDomains);
    }


    foreach ($emails as $email) {
        $email = trim($email);
        if (empty($email)) continue;

        $isValid = $emailValidator->isValid($email, $multipleValidation);
        $errors = [];

        // Apply additional validations if selected
        if (in_array('length_check', $validationTypes) && !validateEmailLength($email)) {
            $isValid = false;
            $errors[] = 'Email exceeds 320 character limit';
        }

        if (in_array('pattern_check', $validationTypes) && !validateEmailPattern($email)) {
            $isValid = false;
            $errors[] = 'Suspicious email pattern detected';
        }

        if (in_array('disposable_check', $validationTypes) && isDisposableEmail($email)) {
            $isValid = false;
            $errors[] = 'Disposable email domain detected';
        }

        $validationResult = [
            'email' => $email,
            'is_valid' => $isValid ? 'Valid' : 'Invalid',
            'errors' => $isValid ? '' : (!empty($errors) ? implode('; ', $errors) : 'Validation failed')
        ];

        if (!$isValid && empty($errors)) {
            $error = $emailValidator->getError();
            if ($error) {
                $validationResult['errors'] = $error->description();
            }
        }

        $results[] = $validationResult;
    }

    return $results;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $validations = $_POST['validations'] ?? [];
    $results = [];

    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $filename = $_FILES['csv_file']['tmp_name'];
        $emailsData = processCSV($filename);

        // Extract emails from CSV data
        $emails = array_column($emailsData, 'email');

        // Validate emails
        $results = validateEmails($emails, $validations);
    } else {
        echo '<div class="csv-info" style="background-color: #f8d7da; border-color: #dc3545;">';
        echo '<strong>Error:</strong> Please upload a CSV file.';
        echo '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Validator</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .csv-info {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .csv-info strong {
            font-weight: bold;
        }
        .results-table {
            margin-top: 20px;
        }
        .checkbox-group {
            display: flex;
            flex-direction: column;
        }

        .checkbox-item {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Email Validator</h1>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="csv_file">Upload CSV File:</label>
                <input type="file" class="form-control-file" id="csv_file" name="csv_file" accept=".csv">
            </div>

            <div class="form-group">
                <label>Validations:</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="dns_check" name="validations[]" value="dns_check" 
                               <?= in_array('dns_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="dns_check">DNS Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="spoof_check" name="validations[]" value="spoof_check" 
                               <?= in_array('spoof_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="spoof_check">Spoof Check (requires PHP intl)</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="length_check" name="validations[]" value="length_check" 
                               <?= in_array('length_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="length_check">Length Validation (320 char limit)</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="pattern_check" name="validations[]" value="pattern_check" 
                               <?= in_array('pattern_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="pattern_check">Pattern Validation</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="disposable_check" name="validations[]" value="disposable_check" 
                               <?= in_array('disposable_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="disposable_check">Disposable Email Check</label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Validate Emails</button>
        </form>

        <?php if (isset($results) && !empty($results)): ?>
            <h2 class="mt-4">Validation Results:</h2>
            <table class="table results-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Errors</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                        <tr>
                            <td><?= htmlspecialchars($result['email']) ?></td>
                            <td><?= htmlspecialchars($result['is_valid']) ?></td>
                            <td><?= htmlspecialchars($result['errors']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
```