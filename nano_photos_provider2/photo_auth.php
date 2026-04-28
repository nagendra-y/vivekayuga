<?php
session_start();

$config = require 'photo_admin_config.php';

function checkAuth() {
    global $config;

    if (!isset($_SESSION['photo_admin_authenticated'])) {
        return false;
    }

    if (!isset($_SESSION['photo_admin_login_time'])) {
        return false;
    }

    $timeout = $config['session_timeout_minutes'] * 60;
    $elapsed = time() - $_SESSION['photo_admin_login_time'];

    if ($elapsed > $timeout) {
        destroySession();
        return false;
    }

    $_SESSION['photo_admin_login_time'] = time();
    return true;
}

function attemptLogin($password) {
    global $config;

    if (password_verify($password, $config['admin_password_hash'])) {
        $_SESSION['photo_admin_authenticated'] = true;
        $_SESSION['photo_admin_login_time'] = time();
        return true;
    }

    return false;
}

function destroySession() {
    unset($_SESSION['photo_admin_authenticated']);
    unset($_SESSION['photo_admin_login_time']);
    session_destroy();
}

function requireAuth() {
    if (!checkAuth()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authentication required']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $password = $_POST['password'] ?? '';

        if (attemptLogin($password)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
        }
        exit;
    }

    if ($action === 'logout') {
        destroySession();
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'check') {
        echo json_encode(['authenticated' => checkAuth()]);
        exit;
    }
}
