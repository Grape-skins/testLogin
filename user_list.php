<?php
// 包含认证处理逻辑
require_once __DIR__ . '/includes/auth.php';

// 检查登录状态
if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit;
}

// 包含用户列表处理逻辑
require_once __DIR__ . '/includes/user_list.php';

// 显示用户列表模板
include __DIR__ . '/templates/user_list.html';
?> 