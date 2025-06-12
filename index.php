<?php
session_start();

// Handle CSV download
if (isset($_GET['download']) && $_GET['download'] === 'csv' && isset($_SESSION['validation_results'])) {
    $results = $_SESSION['validation_results'];
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="email_validation_results_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, ['Email', 'Status', 'Errors']);
    
    // CSV data
    foreach ($results as $result) {
        fputcsv($output, [
            $result['email'],
            $result['is_valid'],
            $result['errors']
        ]);
    }
    
    fclose($output);
    exit;
}

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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: 300;
        }
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-control-file {
            width: 100%;
            padding: 12px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            background: white;
            transition: border-color 0.3s;
        }
        .form-control-file:hover {
            border-color: #667eea;
        }
        .validation-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .checkbox-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
            transition: all 0.3s;
        }
        .checkbox-item:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }
        .checkbox-item input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        .checkbox-item label {
            margin: 0;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.2s;
            display: block;
            margin: 20px auto;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .csv-info {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #dc3545;
            background-color: #f8d7da;
        }
        .csv-info.success {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
        .results-section {
            margin-top: 30px;
        }
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .results-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        .results-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .results-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .results-table tr:hover {
            background-color: #e3f2fd;
        }
        .valid-email {
            color: #28a745;
            font-weight: 600;
        }
        .invalid-email {
            color: #dc3545;
            font-weight: 600;
        }
        .download-section {
            text-align: center;
            margin-top: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .btn-download {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn-download:hover {
            background: #218838;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Validator Pro</h1>
        
        <div class="form-section">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csv_file">📁 Upload CSV File:</label>
                    <input type="file" class="form-control-file" id="csv_file" name="csv_file" accept=".csv" required>
                    <small class="text-muted">Upload a CSV file with an 'email' column for validation</small>
                </div>

                <div class="form-group">
                    <label>🔍 Validation Options:</label>
                    <div class="validation-options">
                        <div class="checkbox-item">
                            <input type="checkbox" id="dns_check" name="validations[]" value="dns_check" 
                                   <?= in_array('dns_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                            <label for="dns_check">🌐 DNS Check - Verify domain exists</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="spoof_check" name="validations[]" value="spoof_check" 
                                   <?= in_array('spoof_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                            <label for="spoof_check">🛡️ Spoof Check - Detect spoofed domains</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="length_check" name="validations[]" value="length_check" 
                                   <?= in_array('length_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                            <label for="length_check">📏 Length Validation - 320 character limit</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="pattern_check" name="validations[]" value="pattern_check" 
                                   <?= in_array('pattern_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                            <label for="pattern_check">🔍 Pattern Check - Detect malformed emails</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="disposable_check" name="validations[]" value="disposable_check" 
                                   <?= in_array('disposable_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                            <label for="disposable_check">🚫 Disposable Email Detection</label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">🚀 Validate Emails</button>
            </form>
        </div>

        <?php if (isset($results) && !empty($results)): ?>
            <div class="results-section">
                <h2>📊 Validation Results</h2>
                
                <?php 
                $validCount = count(array_filter($results, function($r) { return $r['is_valid'] === 'Valid'; }));
                $totalCount = count($results);
                $invalidCount = $totalCount - $validCount;
                ?>
                
                <div class="csv-info success">
                    <strong>Summary:</strong> 
                    ✅ <?= $validCount ?> valid emails | 
                    ❌ <?= $invalidCount ?> invalid emails | 
                    📈 <?= round(($validCount / $totalCount) * 100, 1) ?>% success rate
                </div>

                <table class="results-table">
                    <thead>
                        <tr>
                            <th>📧 Email Address</th>
                            <th>✅ Status</th>
                            <th>⚠️ Issues Found</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?= htmlspecialchars($result['email']) ?></td>
                                <td class="<?= $result['is_valid'] === 'Valid' ? 'valid-email' : 'invalid-email' ?>">
                                    <?= $result['is_valid'] === 'Valid' ? '✅ Valid' : '❌ Invalid' ?>
                                </td>
                                <td><?= htmlspecialchars($result['errors']) ?: '✅ No issues' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="download-section">
                    <h3>💾 Download Results</h3>
                    <p>Click below to download the validation results as a CSV file:</p>
                    <?php
                    // Store results in session for download
                    session_start();
                    $_SESSION['validation_results'] = $results;
                    ?>
                    <a href="?download=csv" class="btn-download">📥 Download CSV Results</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
```