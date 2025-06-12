<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EmailValidator - Interactive Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fafafa;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="email"], input[type="file"], textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .checkbox-item input[type="checkbox"] {
            margin: 0;
        }
        button {
            background-color: #007cba;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        button:hover {
            background-color: #005a8b;
        }
        .download-btn {
            background-color: #28a745;
        }
        .download-btn:hover {
            background-color: #218838;
        }
        .results {
            margin-top: 30px;
        }
        .result-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .valid {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .invalid {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .email-display {
            font-weight: bold;
            font-family: monospace;
        }
        .validation-type {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .demo-section {
            background-color: #e8f4fd;
            border-color: #bee5eb;
        }
        .csv-info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #0c5460;
        }
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            background-color: #fafafa;
            margin-bottom: 15px;
        }
        .file-upload-area:hover {
            border-color: #007cba;
            background-color: #f0f8ff;
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
        }
        .csv-results {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 EmailValidator Interactive Tool</h1>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-section">
                <h3>CSV File Upload</h3>
                <div class="csv-info">
                    <strong>Supported File Formats:</strong>
                    <ul style="margin: 5px 0; padding-left: 20px;">
                        <li><strong>CSV:</strong> Comma-separated values with headers</li>
                        <li><strong>TSV:</strong> Tab-separated values with headers</li>
                        <li><strong>TXT:</strong> Plain text, one email per line</li>
                    </ul>
                    <strong>Requirements:</strong> Max 50MB, up to 50,000 emails per file
                </div>
                <div class="form-group">
                    <label for="csv_file">Upload File:</label>
                    <div class="file-upload-area">
                        <input type="file" id="csv_file" name="csv_file" accept=".csv,.txt,.tsv" style="margin-bottom: 10px;">
                        <p>Choose a CSV, TXT, or TSV file (max 50MB) or drag and drop it here</p>
                        <small>Supported formats: CSV, TXT (one email per line), TSV</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email_column">Email Column Name/Index:</label>
                    <input type="text" id="email_column" name="email_column" 
                           value="<?= htmlspecialchars($_POST['email_column'] ?? 'email') ?>" 
                           placeholder="email (or column index like 0, 1, 2...)">
                </div>
            </div>

            <div class="form-section">
                <h3>Manual Email Input</h3>
                <div class="form-group">
                    <label for="single_email">Single Email:</label>
                    <input type="email" id="single_email" name="single_email" 
                           value="<?= htmlspecialchars($_POST['single_email'] ?? '') ?>" 
                           placeholder="example@domain.com">
                </div>

                <div class="form-group">
                    <label for="bulk_emails">Bulk Emails (one per line):</label>
                    <textarea id="bulk_emails" name="bulk_emails" 
                              placeholder="test@example.com&#10;user@domain.org&#10;invalid.email"><?= htmlspecialchars($_POST['bulk_emails'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>Validation Options</h3>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="rfc" name="validations[]" value="rfc" 
                               <?= in_array('rfc', $_POST['validations'] ?? ['rfc']) ? 'checked' : '' ?>>
                        <label for="rfc">RFC Validation</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="dns" name="validations[]" value="dns" 
                               <?= in_array('dns', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="dns">DNS Check</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="no_warnings" name="validations[]" value="no_warnings" 
                               <?= in_array('no_warnings', $_POST['validations'] ?? []) ? 'checked' : '' ?>>
                        <label for="no_warnings">No RFC Warnings</label>
                    </div>
                </div>
            </div>

            <button type="submit" name="validate">Validate Emails</button>
            <button type="submit" name="demo">Run Demo</button>

            <?php if (isset($_SESSION['last_results']) && !empty($_SESSION['last_results'])): ?>
            <div style="margin-top: 15px; padding: 15px; background-color: #d4edda; border: 1px solid #28a745; border-radius: 5px;">
                <h4 style="margin: 0 0 10px 0; color: #155724;">📁 Download Validation Results</h4>
                <button type="submit" name="download_csv" class="download-btn" style="font-size: 16px; padding: 12px 20px;">
                    📥 <?= isset($_SESSION['original_file_data']) ? 'Download Enhanced CSV (Original + Results)' : 'Download Results as CSV' ?>
                </button>
                <p style="margin: 10px 0 0 0; font-size: 14px; color: #155724;">
                    Results for <?= count($_SESSION['last_results']) ?> emails are ready for download.
                </p>
            </div>
            <?php endif; ?>
        </form>

        <?php
        require_once 'vendor/autoload.php';

        use Egulias\EmailValidator\EmailValidator;
        use Egulias\EmailValidator\Validation\RFCValidation;
        use Egulias\EmailValidator\Validation\DNSCheckValidation;
        use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
        use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $validator = new EmailValidator();

            // Handle CSV download
            if (isset($_POST['download_csv']) && isset($_SESSION['last_results'])) {
                downloadCSV($_SESSION['last_results']);
                exit;
            }

            echo '<div class="results">';

            if (isset($_POST['demo'])) {
                echo '<h3>Demo Results</h3>';
                $results = runDemo($validator);
                $_SESSION['last_results'] = $results;
            } elseif (isset($_POST['validate'])) {
                echo '<h3>Validation Results</h3>';

                $emails = [];

                // Handle CSV file upload
                if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $csvEmails = processCSVFile($_FILES['csv_file'], $_POST['email_column'] ?? 'email');
                        $emails = array_merge($emails, $csvEmails);

                        if (!empty($csvEmails)) {
                            echo '<div class="csv-info" style="background-color: #d4edda; border-color: #28a745;">';
                            echo '<strong>File processed successfully!</strong> Found ' . count($csvEmails) . ' email addresses.';
                            echo '</div>';
                        }
                    } catch (Exception $e) {
                        echo '<div class="csv-info" style="background-color: #f8d7da; border-color: #dc3545;">';
                        echo '<strong>File Processing Error:</strong> ' . htmlspecialchars($e->getMessage());
                        echo '</div>';
                    }
                }

                // Collect emails from single input
                if (!empty($_POST['single_email'])) {
                    $emails[] = trim($_POST['single_email']);
                }

                // Collect emails from bulk input
                if (!empty($_POST['bulk_emails'])) {
                    $bulkEmails = explode("\n", $_POST['bulk_emails']);
                    foreach ($bulkEmails as $email) {
                        $email = trim($email);
                        if (!empty($email)) {
                            $emails[] = $email;
                        }
                    }
                }

                if (empty($emails)) {
                    echo '<p>Please enter at least one email address or upload a CSV file.</p>';
                } else {
                    $validations = $_POST['validations'] ?? ['rfc'];
                    $results = validateEmails($validator, $emails, $validations);
                    $_SESSION['last_results'] = $results;
                }
            }

            echo '</div>';
        }

        function processCSVFile($file, $emailColumn) {
            $emails = [];
            $errors = [];
            $filePath = $file['tmp_name'];
            $originalData = [
                'headers' => [],
                'rows' => [],
                'email_column_index' => 0,
                'filename' => $file['name']
            ];

            // File validation
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload error: ' . $file['error']);
            }

            // File size validation (50MB max)
            if ($file['size'] > 50 * 1024 * 1024) {
                throw new Exception('File too large. Maximum size is 50MB.');
            }

            // File type validation
            $allowedTypes = ['text/csv', 'text/plain', 'text/tab-separated-values', 'application/csv'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($mimeType, $allowedTypes) && !in_array($fileExtension, ['csv', 'txt', 'tsv'])) {
                throw new Exception('Invalid file type. Please upload CSV, TXT, or TSV files only.');
            }

            if (($handle = fopen($filePath, "r")) !== FALSE) {
                // Determine delimiter
                $delimiter = ',';
                if ($fileExtension === 'tsv') {
                    $delimiter = "\t";
                } elseif ($fileExtension === 'txt') {
                    // For TXT files, treat each line as a single email
                    $delimiter = null;
                }

                $header = null;
                $emailIndex = 0;

                if ($delimiter) {
                    $header = fgetcsv($handle, 0, $delimiter);
                    $originalData['headers'] = $header ?: [];

                    // Try to find email column by name
                    if ($header) {
                        $emailIndex = array_search(strtolower($emailColumn), array_map('strtolower', $header));

                        // If not found by name, try as numeric index
                        if ($emailIndex === false && is_numeric($emailColumn)) {
                            $emailIndex = intval($emailColumn);
                            if ($emailIndex >= count($header)) {
                                $emailIndex = false;
                            }
                        }

                        // Default to first column if still not found
                        if ($emailIndex === false) {
                            $emailIndex = 0;
                        }
                    }
                } else {
                    // For TXT files, create a simple header
                    $originalData['headers'] = ['email'];
                    $emailIndex = 0;
                }

                $originalData['email_column_index'] = $emailIndex;

                // Read data rows with memory-efficient streaming
                $rowCount = 0;
                $lineNumber = 1;
                $batchSize = 1000; // Process in batches for memory efficiency
                while (($line = fgets($handle)) !== FALSE && $rowCount < 50000) { // Increased limit to 50000
                    $lineNumber++;

                    if ($delimiter) {
                        // Parse as CSV/TSV
                        $data = str_getcsv(trim($line), $delimiter);
                        $originalData['rows'][] = $data; // Store original row data

                        if (isset($data[$emailIndex]) && !empty(trim($data[$emailIndex]))) {
                            $email = trim($data[$emailIndex]);
                        } else {
                            continue;
                        }
                    } else {
                        // Parse as plain text (one email per line)
                        $email = trim($line);
                        $originalData['rows'][] = [$email]; // Store as single-column row
                    }

                    if (!empty($email)) {
                        // Basic email format check
                        if (strpos($email, '@') !== false) {
                            $emails[] = $email;
                        } else {
                            $errors[] = "Line $lineNumber: '$email' doesn't appear to be an email";
                        }
                    }
                    $rowCount++;
                }
                fclose($handle);

                // Store original file data in session for enhanced download
                $_SESSION['original_file_data'] = $originalData;

                // Display processing summary
                if (!empty($errors) && count($errors) <= 10) {
                    echo '<div class="csv-info" style="background-color: #fff3cd; border-color: #ffc107;">';
                    echo '<strong>File Processing Warnings:</strong><br>';
                    foreach (array_slice($errors, 0, 10) as $error) {
                        echo '• ' . htmlspecialchars($error) . '<br>';
                    }
                    if (count($errors) > 10) {
                        echo '• ... and ' . (count($errors) - 10) . ' more warnings<br>';
                    }
                    echo '</div>';
                }
            }

            return array_unique($emails); // Remove duplicates
        }

        function validateEmails($validator, $emails, $validationTypes) {
            $validationStrategies = [];
            $results = [];

            // Always include individual validations for comprehensive results
            if (in_array('rfc', $validationTypes)) {
                $validationStrategies['RFC'] = new RFCValidation();
            }
            if (in_array('dns', $validationTypes)) {
                $validationStrategies['DNS Check'] = new DNSCheckValidation();
            }
            if (in_array('no_warnings', $validationTypes)) {
                $validationStrategies['No RFC Warnings'] = new NoRFCWarningsValidation();
            }

            // Add combined validation only if multiple strategies exist, but keep individual ones
            if (count($validationStrategies) > 1) {
                $validationStrategies['Overall Result'] = new MultipleValidationWithAnd(array_values($validationStrategies));
            }

            echo '<div class="csv-results">';

            if (count($emails) > 100) {
                echo '<div class="csv-info">';
                echo '<strong>Processing ' . count($emails) . ' emails...</strong> This may take a moment for DNS validation.';
                if (count($emails) > 5000) {
                    echo '<br><strong>Large file detected:</strong> Processing in batches for optimal performance.';
                }
                echo '</div>';
                flush(); // Send output immediately

                // Increase memory limit and execution time for large files
                ini_set('memory_limit', '512M');
                ini_set('max_execution_time', 300); // 5 minutes
            }

            echo '<table>';
            echo '<thead><tr><th>Email</th>';
            foreach ($validationStrategies as $name => $validation) {
                echo "<th>$name</th>";
            }
            echo '<th>Warnings</th></tr></thead>';
            echo '<tbody>';

            $processedCount = 0;
            $batchSize = 500; // Process in smaller batches for UI updates

            foreach ($emails as $email) {
                $processedCount++;

                // Flush output periodically for large datasets
                if ($processedCount % $batchSize === 0) {
                    echo "<tr><td colspan='" . (count($validationStrategies) + 2) . "'>";
                    echo "<em>Processed $processedCount/" . count($emails) . " emails...</em>";
                    echo "</td></tr>";
                    flush();

                    // Clear some memory periodically
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }

                echo "<tr>";
                echo "<td class='email-display'>$email</td>";

                $emailResult = ['email' => $email];

                foreach ($validationStrategies as $name => $validation) {
                    $isValid = $validator->isValid($email, $validation);
                    $status = $isValid ? 'valid' : 'invalid';
                    $icon = $isValid ? '✅' : '❌';

                    echo "<td class='$status'>$icon " . ($isValid ? 'Valid' : 'Invalid');

                    $errorMsg = '';
                    $errorDetails = '';
                    if (!$isValid && $validator->getError()) {
                        $error = $validator->getError();
                        $errorMsg = $error->description();
                        $errorDetails = get_class($error->reason());
                        echo "<br><small><strong>Error:</strong> $errorMsg</small>";
                        if ($errorDetails !== $errorMsg) {
                            echo "<br><small><strong>Type:</strong> " . basename($errorDetails) . "</small>";
                        }
                    }

                    // Also show warnings for this specific validation
                    if ($validator->hasWarnings()) {
                        $warnings = [];
                        foreach ($validator->getWarnings() as $warning) {
                            $warnings[] = basename(get_class($warning));
                        }
                        if (!empty($warnings)) {
                            echo "<br><small><strong>Warnings:</strong> " . implode(', ', $warnings) . "</small>";
                        }
                    }

                    echo "</td>";

                    $emailResult[$name] = $isValid ? 'Valid' : 'Invalid';
                    $emailResult[$name . '_error'] = $errorMsg;
                    $emailResult[$name . '_error_type'] = basename($errorDetails);
                }

                // Warnings column
                $warningsText = '';
                if ($validator->hasWarnings()) {
                    $warnings = [];
                    foreach ($validator->getWarnings() as $warning) {
                        $warnings[] = basename(get_class($warning));
                    }
                    $warningsText = implode(', ', $warnings);
                }
                echo "<td><small>$warningsText</small></td>";
                $emailResult['warnings'] = $warningsText;

                echo "</tr>";
                $results[] = $emailResult;
            }

            echo '</tbody></table>';
            echo '</div>';

            // Calculate summary statistics
            $totalEmails = count($emails);
            $validCount = 0;
            $invalidCount = 0;
            $errorTypes = [];

            foreach ($results as $result) {
                $hasValidResult = false;
                foreach ($result as $key => $value) {
                    if (strpos($key, '_error') === false && strpos($key, '_type') === false && $key !== 'email' && $key !== 'warnings') {
                        if ($value === 'Valid') {
                            $hasValidResult = true;
                            break;
                        }
                    }
                }
                if ($hasValidResult) {
                    $validCount++;
                } else {
                    $invalidCount++;
                    // Collect error types
                    foreach ($result as $key => $value) {
                        if (strpos($key, '_error_type') !== false && !empty($value)) {
                            $errorTypes[$value] = ($errorTypes[$value] ?? 0) + 1;
                        }
                    }
                }
            }

            echo '</tbody></table>';
            echo '</div>';

            echo '<div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;">';
            echo '<h4>📊 Validation Summary</h4>';
            echo "<p><strong>Total emails processed:</strong> $totalEmails</p>";
            echo "<p><strong>Valid emails:</strong> <span style='color: #28a745;'>$validCount (" . round(($validCount/$totalEmails)*100, 1) . "%)</span></p>";
            echo "<p><strong>Invalid emails:</strong> <span style='color: #dc3545;'>$invalidCount (" . round(($invalidCount/$totalEmails)*100, 1) . "%)</span></p>";
            
            if (!empty($errorTypes)) {
                echo '<p><strong>Common error types:</strong></p>';
                echo '<ul>';
                arsort($errorTypes);
                foreach (array_slice($errorTypes, 0, 5) as $errorType => $count) {
                    echo "<li>$errorType: $count occurrences</li>";
                }
                echo '</ul>';
            }
            echo '</div>';

            return $results;
        }

        function runDemo($validator) {
            $demoEmails = [
                'valid@example.com',
                'test@gmail.com',
                'user.name+tag@domain.co.uk',
                'invalid.email',
                'missing@domain',
                'test@invalid-domain-xyz.fake',
                'user@[127.0.0.1]',
                '"quoted string"@example.com'
            ];

            echo '<div class="demo-section form-section">';
            echo '<h4>Demo with various email formats:</h4>';

            $validationTypes = ['rfc', 'dns', 'no_warnings'];
            $results = validateEmails($validator, $demoEmails, $validationTypes);

            echo '</div>';

            return $results;
        }

        function downloadCSV($results) {
            if (empty($results)) {
                return;
            }

            $filename = 'email_validation_results_' . date('Y-m-d_H-i-s') . '.csv';

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, must-revalidate');

            $output = fopen('php://output', 'w');

            // Check if we have original file data stored
            $originalData = $_SESSION['original_file_data'] ?? null;

            if ($originalData && !empty($originalData['headers']) && !empty($originalData['rows'])) {
                // Enhanced download: merge original data with validation results
                downloadEnhancedCSV($output, $results, $originalData);
            } else {
                // Simple download: validation results only
                downloadSimpleCSV($output, $results);
            }

            fclose($output);
        }

        function downloadEnhancedCSV($output, $results, $originalData) {
            // Create a mapping of emails to validation results
            $validationMap = [];
            foreach ($results as $result) {
                $validationMap[$result['email']] = $result;
            }

            // Create enhanced headers
            $enhancedHeaders = $originalData['headers'];

            // Add validation result columns
            $validationColumns = [];
            if (!empty($results)) {
                $sampleResult = reset($results);
                foreach ($sampleResult as $key => $value) {
                    if ($key !== 'email') {
                        $validationColumns[] = 'validation_' . $key;
                    }
                }
            }
            $enhancedHeaders = array_merge($enhancedHeaders, $validationColumns);

            // Write enhanced header
            fputcsv($output, $enhancedHeaders);

            // Write enhanced data rows
            foreach ($originalData['rows'] as $originalRow) {
                $emailColumnIndex = $originalData['email_column_index'];
                $email = isset($originalRow[$emailColumnIndex]) ? trim($originalRow[$emailColumnIndex]) : '';

                // Start with original row data
                $enhancedRow = $originalRow;

                // Add validation results if email was validated
                if (!empty($email) && isset($validationMap[$email])) {
                    $validationResult = $validationMap[$email];
                    foreach ($validationResult as $key => $value) {
                        if ($key !== 'email') {
                            $enhancedRow[] = $value;
                        }
                    }
                } else {
                    // Fill with empty validation columns if no validation result
                    foreach ($validationColumns as $col) {
                        $enhancedRow[] = 'Not Validated';
                    }
                }

                fputcsv($output, $enhancedRow);
            }
        }

        function downloadSimpleCSV($output, $results) {
            // Write header
            $headers = array_keys($results[0]);
            fputcsv($output, $headers);

            // Write data
            foreach ($results as $row) {
                fputcsv($output, $row);
            }
        }
        ?>
    </div>
</body>
</html>