
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
        input[type="email"], textarea {
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
        }
        button:hover {
            background-color: #005a8b;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 EmailValidator Interactive Tool</h1>
        
        <form method="POST">
            <div class="form-section">
                <h3>Email Input</h3>
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
            
            echo '<div class="results">';
            
            if (isset($_POST['demo'])) {
                echo '<h3>Demo Results</h3>';
                runDemo($validator);
            } elseif (isset($_POST['validate'])) {
                echo '<h3>Validation Results</h3>';
                
                $emails = [];
                
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
                    echo '<p>Please enter at least one email address.</p>';
                } else {
                    $validations = $_POST['validations'] ?? ['rfc'];
                    validateEmails($validator, $emails, $validations);
                }
            }
            
            echo '</div>';
        }

        function validateEmails($validator, $emails, $validationTypes) {
            $validationStrategies = [];
            
            if (in_array('rfc', $validationTypes)) {
                $validationStrategies['RFC'] = new RFCValidation();
            }
            if (in_array('dns', $validationTypes)) {
                $validationStrategies['DNS Check'] = new DNSCheckValidation();
            }
            if (in_array('no_warnings', $validationTypes)) {
                $validationStrategies['No RFC Warnings'] = new NoRFCWarningsValidation();
            }
            
            // Multiple validation if more than one is selected
            if (count($validationStrategies) > 1) {
                $validationStrategies['Combined'] = new MultipleValidationWithAnd(array_values($validationStrategies));
            }
            
            foreach ($emails as $email) {
                echo "<h4>Email: <span class='email-display'>$email</span></h4>";
                
                foreach ($validationStrategies as $name => $validation) {
                    $isValid = $validator->isValid($email, $validation);
                    $status = $isValid ? 'valid' : 'invalid';
                    $icon = $isValid ? '✅' : '❌';
                    
                    echo "<div class='result-item $status'>";
                    echo "<div class='validation-type'>$name</div>";
                    echo "$icon " . ($isValid ? 'Valid' : 'Invalid');
                    
                    if (!$isValid && $validator->getError()) {
                        echo "<br><small>Error: " . $validator->getError()->description() . "</small>";
                    }
                    
                    if ($validator->hasWarnings()) {
                        echo "<div class='warning' style='margin-top: 10px; padding: 5px;'>";
                        echo "<strong>Warnings:</strong><br>";
                        foreach ($validator->getWarnings() as $warning) {
                            echo "• " . get_class($warning) . "<br>";
                        }
                        echo "</div>";
                    }
                    
                    echo "</div>";
                }
            }
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
            validateEmails($validator, $demoEmails, $validationTypes);
            
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
