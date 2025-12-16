<?php
session_start();

require_once 'lib/auth.php';

$error = '';
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// If already authenticated, redirect to index
if (checkAuth()) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'redirect' => 'index.php']);
        exit;
    }
    header('Location: index.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if (empty($password)) {
        $error = 'Password is required';
    } else {
        if (login($password)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => 'index.php']);
                exit;
            }
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid password';
        }
    }
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $error]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer Login - Springsoftit</title>
    <link rel="stylesheet" href="src/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .login-container {
            max-width: 400px;
            margin: 0 auto;
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
                <h2 class="text-white">Installer Login</h2>
            </div>
            <div class="installer-body">
                <div class="login-container">
                    <?php if ($error && !$isAjax): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div id="error-message" class="alert alert-danger" style="display: none;" role="alert"></div>
                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                    </div>
                    
                    <form id="login-form" method="POST">
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required autofocus>
                            <small class="text-muted">Enter installer password to continue</small>
                        </div>
                        <button type="submit" class="btn" id="login-btn">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            $('#login-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $btn = $('#login-btn');
                const $loading = $('#loading');
                const $error = $('#error-message');
                
                $error.hide();
                $btn.prop('disabled', true);
                $loading.show();
                
                $.ajax({
                    url: 'login.php',
                    method: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.redirect || 'index.php';
                        } else {
                            $error.text(response.error || 'Login failed').show();
                            $btn.prop('disabled', false);
                            $loading.hide();
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'An error occurred';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.error) {
                                errorMsg = response.error;
                            }
                        } catch(e) {
                            // Fallback to form submission
                            $form.off('submit').submit();
                            return;
                        }
                        $error.text(errorMsg).show();
                        $btn.prop('disabled', false);
                        $loading.hide();
                    }
                });
            });
        });
    </script>
</body>
</html>

