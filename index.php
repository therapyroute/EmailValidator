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

require_once 'vendor/autoload.php';

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\Extra\SpoofCheckValidation;

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

    // Build validation array
    $validations = [new RFCValidation()];

    // Add validations based on selections
    if (in_array('dns_check', $validationTypes)) {
        $validations[] = new DNSCheckValidation();
    }

    if (in_array('no_warnings', $validationTypes)) {
        $validations[] = new NoRFCWarningsValidation();
    }

    if (in_array('spoof_check', $validationTypes)) {
        if (extension_loaded('intl')) {
            $validations[] = new SpoofCheckValidation();
        }
    }

    // Create multiple validation with all selected validations
    $multipleValidation = new MultipleValidationWithAnd($validations);

    foreach ($emails as $email) {
        $email = trim($email);
        if (empty($email)) continue;

        $isValid = $emailValidator->isValid($email, $multipleValidation);
        $errors = '';

        if (!$isValid) {
            $error = $emailValidator->getError();
            if ($error) {
                $errors = $error->description();
            }
        }

        $results[] = [
            'email' => $email,
            'is_valid' => $isValid ? 'Valid' : 'Invalid',
            'errors' => $errors
        ];
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

        $emails = array_column($emailsData, 'email');
        $results = validateEmails($emails, $validations);

        // Store results in session for download
        $_SESSION['validation_results'] = $results;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Validator Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            background-color: #fafafa;
        }
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
        }
        .checkbox-item input[type="checkbox"] {
            margin-right: 8px;
        }
        .checkbox-item label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .csv-info {
            margin: 20px 0;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #28a745;
            background-color: #d4edda;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .valid {
            color: #28a745;
            font-weight: bold;
        }
        .invalid {
            color: #dc3545;
            font-weight: bold;
        }
        .download-btn {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            margin-top: 15px;
        }
        .download-btn:hover {
            background-color: #218838;
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Email Validator Tool</h1>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="csv_file">Upload CSV File:</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                <small style="color: #666;">CSV file should have an 'email' column</small>
            </div>

            <div class="form-group">
                <label>Validation Options:</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="dns_check" name="validations[]" value="dns_check" 
                               <?= in_array('dns_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="dns_check">DNS Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="no_warnings" name="validations[]" value="no_warnings" 
                               <?= in_array('no_warnings', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="no_warnings">No RFC Warnings</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="spoof_check" name="validations[]" value="spoof_check" 
                               <?= in_array('spoof_check', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="spoof_check">Spoof Check</label>
                    </div>
                </div>
            </div>

            <button type="submit" name="validate">Validate Emails</button>
        </form>

        <?php if (isset($results) && !empty($results)): ?>
            <div class="csv-info">
                <?php 
                $validCount = count(array_filter($results, function($r) { return $r['is_valid'] === 'Valid'; }));
                $totalCount = count($results);
                ?>
                <strong>Results:</strong> <?= $validCount ?> valid out of <?= $totalCount ?> emails processed.

                <a href="?download=csv" class="download-btn">Download Results CSV</a>
            </div>

            <table>
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
                            <td class="<?= $result['is_valid'] === 'Valid' ? 'valid' : 'invalid' ?>">
                                <?= $result['is_valid'] ?>
                            </td>
                            <td><?= htmlspecialchars($result['errors']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>