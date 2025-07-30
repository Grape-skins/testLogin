<!-- CREATE DATABASE IF NOT EXISTS test_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE test_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) NOT NULL UNIQUE,
  salt VARCHAR(64) NOT NULL,
  password_hash VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; -->


<?php
session_start();

// ===== 数据库连接配置 =====
$dsn = 'mysql:host=localhost;dbname=test_db;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass);
} catch (PDOException $e) {
    die('数据库连接失败: ' . $e->getMessage());
}

if (!isset($_SESSION['login_errors'])) {
    $_SESSION['login_errors'] = 0;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

/* #region 登录状态检查 */
    if (isset($_SESSION['user_email'])) {
        echo <<<HTML
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>用户中心</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .welcome-container {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 400px;
                width: 100%;
                animation: slideIn 0.5s ease-out;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .welcome-title {
                color: #333;
                font-size: 2rem;
                margin-bottom: 10px;
                font-weight: 600;
            }
            
            .user-email {
                color: #28a745;
                font-size: 1.1rem;
                margin-bottom: 30px;
                padding: 15px;
                background: rgba(40, 167, 69, 0.1);
                border-radius: 10px;
                border: 2px solid rgba(40, 167, 69, 0.2);
            }
            
            .logout-btn {
                background: linear-gradient(45deg, #ff6b6b, #ff8e53);
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 25px;
                font-weight: 500;
                transition: all 0.3s ease;
                display: inline-block;
                box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            }
            
            .logout-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
            }
        </style>
    </head>
    <body>
        <div class="welcome-container">
            <h1 class="welcome-title">欢迎回来！</h1>
            <div class="user-email">
                <strong>当前用户：</strong>{$_SESSION['user_email']}
            </div>
            <a href="?logout=1" class="logout-btn">安全退出</a>
        </div>
    </body>
    </html>
    HTML;
        exit;
    }
/* #endregion */

/* #region 生成 CSRF Token */
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
/* #endregion */

/* #region 表单处理逻辑 */
    $mode = $_POST['mode'] ?? '';
    $email = trim($_POST['email'] ?? ''); // 去除邮箱前后空格
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    $message = '';
    $message_type = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ===== CSRF 验证 =====
        if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            $message = '请求非法，已拦截（CSRF TOKEN 不匹配）';
            $message_type = 'error';
        } elseif (!$email || !$password) {
            $message = '请输入邮箱和密码';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // 验证邮箱格式
            $message = '请输入有效的邮箱地址';
            $message_type = 'error';
        } elseif (strlen($password) < 6) {
            // 密码长度验证
            $message = '密码长度至少需要6个字符';
            $message_type = 'error';
        } elseif ($mode === 'register') {
            // ===== 注册逻辑 =====
            // 首先检查邮箱是否已存在
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $check_stmt->execute(['email' => $email]);
            $email_exists = $check_stmt->fetchColumn();
            
            if ($email_exists > 0) {
                $message = '注册失败：该邮箱已被注册，请直接登录或使用其他邮箱。';
                $message_type = 'error';
            } else {
                // 邮箱不存在，可以注册
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
                    // 处理其他可能的数据库错误
                    $message = '注册失败：系统错误，请稍后重试。';
                    $message_type = 'error';
                }
            }
        } elseif ($mode === 'login') {
            // ===== 登录逻辑 =====
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
                        $_SESSION['login_errors'] = 0; // 清空失败计数
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
/* #endregion */

/* #region 登录/注册表单 */
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>用户登录 / 注册</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .login-container {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 400px;
                animation: slideIn 0.5s ease-out;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .form-title {
                text-align: center;
                color: #333;
                font-size: 2rem;
                margin-bottom: 30px;
                font-weight: 600;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-label {
                display: block;
                color: #555;
                font-weight: 500;
                margin-bottom: 8px;
                font-size: 14px;
            }
            
            .form-input {
                width: 100%;
                padding: 12px 16px;
                border: 2px solid #e1e5e9;
                border-radius: 10px;
                font-size: 16px;
                transition: all 0.3s ease;
                background: rgba(255, 255, 255, 0.8);
            }
            
            .form-input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                background: rgba(255, 255, 255, 1);
            }
            
            .mode-selector {
                display: flex;
                justify-content: center;
                margin-bottom: 30px;
                background: rgba(102, 126, 234, 0.1);
                border-radius: 10px;
                padding: 5px;
            }
            
            .mode-option {
                flex: 1;
                text-align: center;
            }
            
            .mode-radio {
                display: none;
            }
            
            .mode-label {
                display: block;
                padding: 12px 20px;
                cursor: pointer;
                border-radius: 8px;
                transition: all 0.3s ease;
                color: #667eea;
                font-weight: 500;
            }
            
            .mode-radio:checked + .mode-label {
                background: #667eea;
                color: white;
                box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
            }
            
            .submit-btn {
                width: 100%;
                background: linear-gradient(45deg, #667eea, #764ba2);
                color: white;
                padding: 14px;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            }
            
            .submit-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            }
            
            .submit-btn:active {
                transform: translateY(0);
            }
            
            .message {
                padding: 15px;
                border-radius: 10px;
                margin-bottom: 20px;
                font-weight: 500;
                text-align: center;
                animation: fadeIn 0.3s ease-out;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .message.success {
                background: rgba(40, 167, 69, 0.1);
                color: #28a745;
                border: 2px solid rgba(40, 167, 69, 0.2);
            }
            
            .message.error {
                background: rgba(220, 53, 69, 0.1);
                color: #dc3545;
                border: 2px solid rgba(220, 53, 69, 0.2);
            }
            
            .form-footer {
                text-align: center;
                margin-top: 20px;
                color: #666;
                font-size: 14px;
            }
            
            .password-strength {
                margin-top: 5px;
                font-size: 12px;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .password-strength.show {
                opacity: 1;
            }
            
            .strength-weak { color: #dc3545; }
            .strength-medium { color: #ffc107; }
            .strength-strong { color: #28a745; }
            
            .email-validation {
                margin-top: 5px;
                font-size: 12px;
                color: #dc3545;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .email-validation.show {
                opacity: 1;
            }
            
            @media (max-width: 480px) {
                .login-container {
                    padding: 30px 20px;
                    margin: 10px;
                }
                
                .form-title {
                    font-size: 1.5rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1 class="form-title">用户中心</h1>
            
            <!-- 显示消息 -->
    HTML;

    if ($message) {
        echo "<div class='message {$message_type}'>{$message}</div>";
    }

    echo <<<HTML
            
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="{$_SESSION['csrf_token']}">
                
                <!-- 模式选择 -->
                <div class="mode-selector">
                    <div class="mode-option">
                        <input type="radio" name="mode" value="login" id="login" class="mode-radio" checked>
                        <label for="login" class="mode-label">登录</label>
                    </div>
                    <div class="mode-option">
                        <input type="radio" name="mode" value="register" id="register" class="mode-radio">
                        <label for="register" class="mode-label">注册</label>
                    </div>
                </div>
                
                <!-- 邮箱输入 -->
                <div class="form-group">
                    <label for="email" class="form-label">邮箱地址</label>
                    <input type="email" name="email" id="email" class="form-input" 
                        placeholder="请输入您的邮箱地址" required value="{$email}">
                    <div class="email-validation" id="email-validation">请输入有效的邮箱地址</div>
                </div>
                
                <!-- 密码输入 -->
                <div class="form-group">
                    <label for="password" class="form-label">密码</label>
                    <input type="password" name="password" id="password" class="form-input" 
                        placeholder="请输入密码（至少6个字符）" required minlength="6">
                    <div class="password-strength" id="password-strength"></div>
                </div>
                
                <!-- 提交按钮 -->
                <button type="submit" class="submit-btn">
                    <span id="submit-text">登录</span>
                </button>
            </form>
            
            <div class="form-footer">
                <p>安全登录，保护您的个人信息</p>
            </div>
        </div>
        
        <script>
            // 动态更新提交按钮文本
            const loginRadio = document.getElementById('login');
            const registerRadio = document.getElementById('register');
            const submitText = document.getElementById('submit-text');
            const passwordStrength = document.getElementById('password-strength');
            const emailValidation = document.getElementById('email-validation');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            function updateSubmitText() {
                if (loginRadio.checked) {
                    submitText.textContent = '登录';
                    passwordStrength.classList.remove('show');
                } else {
                    submitText.textContent = '注册';
                }
            }
            
            // 密码强度检查
            function checkPasswordStrength(password) {
                if (password.length === 0) {
                    passwordStrength.classList.remove('show');
                    return;
                }
                
                let strength = 0;
                let strengthText = '';
                let strengthClass = 'strength-weak'; // 设置默认值
                
                // 长度检查
                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                
                // 包含数字
                if (/\d/.test(password)) strength++;
                
                // 包含字母
                if (/[a-zA-Z]/.test(password)) strength++;
                
                // 包含特殊字符
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
                
                if (password.length < 6) {
                    strengthText = '密码太短（至少需要6个字符）';
                    strengthClass = 'strength-weak';
                } else if (strength <= 2) {
                    strengthText = '密码强度：弱';
                    strengthClass = 'strength-weak';
                } else if (strength <= 3) {
                    strengthText = '密码强度：中等';
                    strengthClass = 'strength-medium';
                } else {
                    strengthText = '密码强度：强';
                    strengthClass = 'strength-strong';
                }
                
                passwordStrength.textContent = strengthText;
                passwordStrength.className = `password-strength show ` + strengthClass;
            }
            
            // 邮箱格式验证
            function validateEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email.length > 0 && !emailRegex.test(email)) {
                    emailValidation.classList.add('show');
                } else {
                    emailValidation.classList.remove('show');
                }
            }
            
            // 事件监听
            loginRadio.addEventListener('change', updateSubmitText);
            registerRadio.addEventListener('change', updateSubmitText);
            
            passwordInput.addEventListener('input', function() {
                if (registerRadio.checked) {
                    checkPasswordStrength(this.value);
                }
            });
            
            emailInput.addEventListener('blur', function() {
                validateEmail(this.value);
            });
            
            emailInput.addEventListener('input', function() {
                if (emailValidation.classList.contains('show')) {
                    validateEmail(this.value);
                }
            });
            
            // 模式切换时的处理
            registerRadio.addEventListener('change', function() {
                if (this.checked && passwordInput.value) {
                    checkPasswordStrength(passwordInput.value);
                }
            });
            
            // 表单提交验证
            document.querySelector('form').addEventListener('submit', function(e) {
                const email = emailInput.value.trim();
                const password = passwordInput.value;
                
                // 邮箱格式验证
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    emailValidation.classList.add('show');
                    emailInput.focus();
                    return;
                }
                
                // 密码长度验证
                if (password.length < 6) {
                    e.preventDefault();
                    alert('密码长度至少需要6个字符');
                    passwordInput.focus();
                    return;
                }
                
                // 提交动画
                const submitBtn = document.querySelector('.submit-btn');
                submitBtn.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    submitBtn.style.transform = '';
                }, 150);
            });
            
            // 输入框焦点效果
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = '';
                });
            });
        </script>
    </body>
    </html>
    HTML;
/* #endregion */
