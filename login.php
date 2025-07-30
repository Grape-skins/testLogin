<?php
// 包含认证处理逻辑
require_once __DIR__ . '/includes/auth.php';

// 检查登录状态
if (isset($_SESSION['user_email'])) {
    // 用户已登录，跳转到用户列表页面
    header("Location: user_list.php");
    exit;
}

// 用户未登录，显示登录页面
include __DIR__ . '/templates/login.html';
?>
