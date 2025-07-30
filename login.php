<?php
// 包含认证处理逻辑
require_once __DIR__ . '/includes/auth.php';

// 检查登录状态
if (isset($_SESSION['user_email'])) {
    // 用户已登录，显示欢迎页面
    include __DIR__ . '/templates/welcome.html';
    exit;
}

// 用户未登录，显示登录页面
include __DIR__ . '/templates/login.html';
?>
