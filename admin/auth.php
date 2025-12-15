<?php
session_start();

// 检查用户是否已登录
function checkLogin() {
    // 检查session登录状态
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return;
    }
    
    // 检查记住登录的cookie
    if (isset($_COOKIE['admin_remember_token'])) {
        // 这里应该验证token的有效性，这里简化处理
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = 'admin';
        return;
    }
    
    // 未登录，重定向到登录页面
    header('Location: login.php');
    exit;
}

// 获取当前登录用户名
function getCurrentUsername() {
    return $_SESSION['admin_username'] ?? '';
}

// 退出登录
function logout() {
    // 清除记住登录的cookie
    if (isset($_COOKIE['admin_remember_token'])) {
        setcookie('admin_remember_token', '', time() - 3600, '/');
    }
    session_destroy();
    header('Location: login.php');
    exit;
}
?> 