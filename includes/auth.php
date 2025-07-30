<?php
session_start();

// 包含数据库配置
require_once __DIR__ . '/../config/database.php';

// 初始化登录错误计数
if (!isset($_SESSION['login_errors'])) {
    $_SESSION['login_errors'] = 0;
}

// 处理登出
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// 生成 CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 表单处理逻辑
$mode = $_POST['mode'] ?? '';
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 验证
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $message = '请求非法，已拦截（CSRF TOKEN 不匹配）';
        $message_type = 'error';
    } elseif (!$email || !$password) {
        $message = '请输入邮箱和密码';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '请输入有效的邮箱地址';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = '密码长度至少需要6个字符';
        $message_type = 'error';
    } elseif ($mode === 'register') {
        // 注册逻辑
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $check_stmt->execute(['email' => $email]);
        $email_exists = $check_stmt->fetchColumn();
        
        if ($email_exists > 0) {
            $message = '注册失败：该邮箱已被注册，请直接登录或使用其他邮箱。';
            $message_type = 'error';
        } else {
            $salt = bin2hex(random_bytes(16));
            $password_hash = hash_hmac('sha256', $password, $salt);

            $stmt = $pdo->prepare("INSERT INTO users (email, salt, password_hash) VALUES (:email, :salt, :hash)");
            try {
                $stmt->execute([
                    'email' => $email,
                    'salt' => $salt,
                    'hash' => $password_hash
                ]);
                $message = '注册成功！请使用您的邮箱和密码登录。';
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = '注册失败：系统错误，请稍后重试。';
                $message_type = 'error';
            }
        }
    } elseif ($mode === 'login') {
        // 登录逻辑
        if ($_SESSION['login_errors'] >= 3) {
            $message = '登录失败次数过多，请稍后再试。';
            $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $computed_hash = hash_hmac('sha256', $password, $user['salt']);
                if (hash_equals($user['password_hash'], $computed_hash)) {
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['login_errors'] = 0;
                    header("Location: login.php");
                    exit;
                } else {
                    $_SESSION['login_errors']++;
                    $remaining = 3 - $_SESSION['login_errors'];
                    $message = "密码错误，还可以尝试 {$remaining} 次";
                    $message_type = 'error';
                }
            } else {
                $message = '该邮箱尚未注册，请先注册账户';
                $message_type = 'error';
            }
        }
    }
}
?> 