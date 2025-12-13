<?php
session_start();

require_once 'lib/auth.php';
require_once 'lib/config.php';
require_once 'lib/functions.php';

requireAuth();

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
$errors = [];
$request_method = strtoupper($_SERVER['REQUEST_METHOD']);

// Docker detection
$isDocker = isDockerEnvironment();
$dockerHosts = $isDocker ? getDockerDatabaseHosts('mysql') : [];

// Default values
$defaultHost = $isDocker && !empty($dockerHosts) ? $dockerHosts[0] : '127.0.0.1';
$defaultPort = '3306';
$selectedDbType = $_POST['db_type'] ?? $_SESSION['db_type'] ?? 'auto';

if ($request_method === 'POST') {
    // Handle test connection request
    if (isset($_POST['test_connection'])) {
        $dbType = $_POST['db_type'] ?? 'auto';
        $host = $_POST['db_host'] ?? '';
        $port = $_POST['db_port'] ?? '';
        $dbname = $_POST['db_name'] ?? '';
        $username = $_POST['db_username'] ?? '';
        $password = $_POST['db_pass'] ?? '';
        
        if (empty($host) || empty($username)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Host and username are required']);
                exit;
            }
        }
        
        // Auto-detect if needed
        if ($dbType === 'auto') {
            $dbType = detectDatabaseType($host, $port, $username, $password, $dbname);
            if ($dbType === 'unknown') {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Could not detect database type. Please select manually.']);
                    exit;
                }
            }
        }
        
        // Set default port if not provided
        if (empty($port)) {
            $adapter = DatabaseFactory::createAdapter($dbType);
            $port = $adapter->getDefaultPort();
        }
        
        // Test connection
        $testResult = testDatabaseConnection($dbType, $host, $port, $dbname, $username, $password);
        
        if ($isAjax) {
            header('Content-Type: application/json');
            if ($testResult) {
                echo json_encode(['success' => true, 'message' => 'Connection successful!', 'detected_type' => $dbType]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Connection failed. Please check your credentials.']);
            }
            exit;
        }
    }
    
    // Handle installation request
    $inputArray = [
        'purchase_code' => 'Purchase code Field Is Required',
        'url' => 'Url Field Is Required',
        'db_name' => 'Database Name Field Is Required',
        'db_host' => 'Database Host Field Is Required',
        'db_username' => 'Database Username Field Is Required',
        'username' => 'Username Field Is Required',
        'password' => 'Password Field Is Required',
        'email' => 'Email Field Is Required',
    ];

    $filteredArray = array_filter($_POST);

    $result = array_diff($_POST, $filteredArray);

    foreach ($result as $key => $message) {
        if (array_key_exists($key, $inputArray)) {
            $errors[$key] = $inputArray[$key];
        }
    }

    // Set default port if not provided
    if (empty($_POST['db_port'])) {
        $dbType = $_POST['db_type'] ?? 'auto';
        if ($dbType === 'auto') {
            $_POST['db_port'] = '3306'; // Default to MySQL port
        } else {
            $adapter = DatabaseFactory::createAdapter($dbType);
            $_POST['db_port'] = $adapter->getDefaultPort();
        }
    }

    $_SESSION['errors'] = $errors;
    $_SESSION['db_type'] = $_POST['db_type'] ?? 'auto';

    if (count($_SESSION['errors']) > 0) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        header('Location: database.php');
        exit;
    }

    $sale = getPurchaseCode($_POST['purchase_code']);

    if($sale == 'invalid_type'){
        $errorMsg = 'Invalid Purchase Code';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        $_SESSION['singleError'] = $errorMsg;
        header('Location: database.php');
        exit;
    }
    
    if($sale == 'not_200'){
        $errorMsg = 'Got status 502, try again shortly';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        $_SESSION['singleError'] = $errorMsg;
        header('Location: database.php');
        exit;
    }

    if($sale->item->id != '43684285'){
        $errorMsg = 'Invalid Purchase Code';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        $_SESSION['singleError'] = $errorMsg;
        header('Location: database.php');
        exit;
    }

    $importResult = importDatabase($_POST);
    
    if ($importResult == 'success') {
        if (updateAdminCredentials($_POST) == 'success') {
            envUpdateAfterInstalltion($_POST);
            file_put_contents(installedPath(), 'installed');
            message($_SERVER);
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Installation completed successfully!', 'redirect' => 'finish.php']);
                exit;
            }
            header('Location: finish.php');
            exit;
        } else {
            $errorMsg = 'Could not update Admin Credentials';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit;
            }
            $_SESSION['singleError'] = $errorMsg;
            header('Location: database.php');
            exit;
        }
    } elseif ($importResult == 'db_error') {
        $errorMsg = 'Wrong Database Credentials! Cannot connect to Database';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        $_SESSION['singleError'] = $errorMsg;
        header('Location: database.php');
        exit;
    } elseif ($importResult == 'db_not_found') {
        $errorMsg = 'Database File Not Found in Directory';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        $_SESSION['singleError'] = $errorMsg;
        header('Location: database.php');
        exit;
    } elseif ($importResult == 'not_execute') {
        $errorMsg = 'Database Not Executed';
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $errorMsg]);
            exit;
        }
        $_SESSION['singleError'] = $errorMsg;
        header('Location: database.php');
        exit;
    }
} else {
    if (isset($_SESSION['errors'])) {
        $errors = $_SESSION['errors'];
        unset($_SESSION['errors']);
    }

    if (isset($_SESSION['singleError'])) {
        $singleError = $_SESSION['singleError'];
        unset($_SESSION['singleError']);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Springsoftit Installer</title>
    <link rel="stylesheet" href="src/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .text-danger {
            color: red;
            font-size: 14px;
        }

        .alert {
            position: relative;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }

        .col-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 15px;
        }

        .col-12 {
            flex: 0 0 100%;
            max-width: 100%;
            padding: 0 15px;
        }

        .form-control.error {
            border-color: #dc3545;
        }

        .docker-info {
            font-size: 12px;
            color: #0c5460;
            margin-top: 5px;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
            padding: 12px 25px;
            display: inline-block;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 10px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #11c26d;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="installer-wrapper">
        <div class="installer-box">
            <div class="installer-header">
                <img src="src/logo.png" alt="logo" class="logo">
                <h2 class="text-white">SpringSoftIT Auto Installer</h2>
            </div>
            <div class="installer-body">
                <?php if (isset($singleError)) : ?>
                    <div class="alert alert-danger" role="alert" id="error-message">
                        <?= htmlspecialchars($singleError) ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-danger" role="alert" id="error-message" style="display: none;"></div>
                <div class="alert alert-success" role="alert" id="success-message" style="display: none;"></div>
                <div class="alert alert-info" role="alert" id="progress-message" style="display: none;"></div>
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                </div>

                <?php if ($isDocker): ?>
                    <div class="alert alert-info">
                        <strong>Docker Environment Detected</strong><br>
                        If your database is in a Docker container, try these hostnames: <?= implode(', ', $dockerHosts) ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" id="database-form">
                    <div class="row">
                        <div class="mb-3 col-12">
                            <label>Purchase Code</label>
                            <input type="text" name="purchase_code" class="form-control" required>
                            <small class="text-danger"><?= $errors['purchase_code'] ?? '' ?></small>
                        </div>

                        <div class="mb-3 col-6">
                            <label>Site Url</label>
                            <input type="text" name="url" class="form-control" value="<?= htmlspecialchars(getBaseURL()) ?>" required>
                            <small class="text-danger"><?= $errors['url'] ?? '' ?></small>
                        </div>

                        <div class="mb-3 col-6">
                            <label>Database Type</label>
                            <select name="db_type" id="db_type" class="form-control">
                                <?php foreach ($config['database_types'] as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $selectedDbType === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Select database type or use auto-detect</small>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label>Database Host</label>
                                <input type="text" class="form-control" name="db_host" id="db_host" placeholder="Database Host" value="<?= htmlspecialchars($defaultHost) ?>" required>
                                <?php if ($isDocker): ?>
                                    <small class="docker-info">Docker detected. Try: <?= implode(', ', $dockerHosts) ?></small>
                                <?php endif; ?>
                                <small class="text-danger"><?= $errors['db_host'] ?? '' ?></small>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label>Database Port</label>
                                <input type="text" class="form-control" name="db_port" id="db_port" value="<?= htmlspecialchars($defaultPort) ?>" required>
                                <small class="text-danger"><?= $errors['db_port'] ?? '' ?></small>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label>Database Name</label>
                                <input type="text" class="form-control" name="db_name" id="db_name" required>
                                <small class="text-danger"><?= $errors['db_name'] ?? '' ?></small>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label>Database Username</label>
                                <input type="text" class="form-control" name="db_username" id="db_username" required>
                                <small class="text-danger"><?= $errors['db_username'] ?? '' ?></small>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="mb-3">
                                <label>Database Password</label>
                                <input type="password" class="form-control" name="db_pass" id="db_pass">
                                <small class="text-danger"><?= $errors['db_pass'] ?? '' ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn-secondary" id="test-connection">Test Connection</button>
                    </div>

                    <h3 class="mb-3">Set Admin Credentials</h3>

                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" class="form-control" name="username" required>
                        <small class="text-danger"><?= $errors['username'] ?? '' ?></small>
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
                        <small class="text-danger"><?= $errors['password'] ?? '' ?></small>
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                        <small class="text-danger"><?= $errors['email'] ?? '' ?></small>
                    </div>

                    <button type="submit" class="btn" id="install-btn">Install Now</button>
                </form>

            </div>
            <div class="installer-footer">
                <a href="permission.php" class="btn">Back</a>
            </div>
        </div>
    </div>
    <script src="src/installer.js"></script>
</body>
</html>
