<?php

if (!session_id()) {
    session_start();
}

define('INSTALLER_PASSWORD_FILE', __DIR__ . '/../.installer_password');

/**
 * Get installer password file path
 */
function getInstallerPasswordFile()
{
    return INSTALLER_PASSWORD_FILE;
}

/**
 * Check if password file exists
 */
function hasInstallerPassword()
{
    return file_exists(getInstallerPasswordFile());
}

/**
 * Hash password for storage
 */
function hashInstallerPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against stored hash
 */
function verifyInstallerPassword($password, $hash = null)
{
    if ($hash === null) {
        if (!hasInstallerPassword()) {
            return false;
        }
        $hash = trim(file_get_contents(getInstallerPasswordFile()));
    }
    return password_verify($password, $hash);
}

/**
 * Set installer password (first time setup)
 */
function setInstallerPassword($password)
{
    $hash = hashInstallerPassword($password);
    return file_put_contents(getInstallerPasswordFile(), $hash) !== false;
}

/**
 * Check if user is authenticated
 */
function checkAuth()
{
    return isset($_SESSION['installer_authenticated']) && $_SESSION['installer_authenticated'] === true;
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth()
{
    // If no password file exists, create one with default password
    if (!hasInstallerPassword()) {
        // Default password: 'installer' - user should change this
        setInstallerPassword('installer');
    }

    // Skip auth check for login page itself
    $currentPage = basename($_SERVER['PHP_SELF']);
    if ($currentPage === 'login.php') {
        return;
    }

    if (!checkAuth()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Login user
 */
function login($password)
{
    if (verifyInstallerPassword($password)) {
        $_SESSION['installer_authenticated'] = true;
        session_regenerate_id(true); // Regenerate session ID for security
        return true;
    }
    return false;
}

/**
 * Logout user
 */
function logout()
{
    $_SESSION['installer_authenticated'] = false;
    session_destroy();
}

