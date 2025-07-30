<?php
// 包含数据库配置
require_once __DIR__ . '/config/database.php';

// 测试用户数据
$test_users = [
    ['email' => 'admin@example.com', 'password' => 'admin123'],
    ['email' => 'user1@test.com', 'password' => 'password123'],
    ['email' => 'user2@test.com', 'password' => 'password123'],
    ['email' => 'user3@test.com', 'password' => 'password123'],
    ['email' => 'user4@test.com', 'password' => 'password123'],
    ['email' => 'user5@test.com', 'password' => 'password123'],
    ['email' => 'test@example.com', 'password' => 'test123'],
    ['email' => 'demo@example.com', 'password' => 'demo123'],
    ['email' => 'sample@test.com', 'password' => 'sample123'],
    ['email' => 'example@test.com', 'password' => 'example123'],
    ['email' => 'john@example.com', 'password' => 'john123'],
    ['email' => 'jane@test.com', 'password' => 'jane123'],
    ['email' => 'mike@example.com', 'password' => 'mike123'],
    ['email' => 'sarah@test.com', 'password' => 'sarah123'],
    ['email' => 'david@example.com', 'password' => 'david123'],
];

echo "开始生成测试用户数据...\n";

$success_count = 0;
$error_count = 0;

foreach ($test_users as $user) {
    try {
        // 检查用户是否已存在
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $check_stmt->execute(['email' => $user['email']]);
        $exists = $check_stmt->fetchColumn();
        
        if ($exists > 0) {
            echo "用户 {$user['email']} 已存在，跳过...\n";
            continue;
        }
        
        // 生成盐值和密码哈希
        $salt = bin2hex(random_bytes(16));
        $password_hash = hash_hmac('sha256', $user['password'], $salt);
        
        // 插入用户
        $stmt = $pdo->prepare("INSERT INTO users (email, salt, password_hash) VALUES (:email, :salt, :hash)");
        $stmt->execute([
            'email' => $user['email'],
            'salt' => $salt,
            'hash' => $password_hash
        ]);
        
        echo "成功创建用户: {$user['email']}\n";
        $success_count++;
        
    } catch (PDOException $e) {
        echo "创建用户 {$user['email']} 失败: " . $e->getMessage() . "\n";
        $error_count++;
    }
}

echo "\n生成完成！\n";
echo "成功创建: {$success_count} 个用户\n";
echo "失败: {$error_count} 个用户\n";
echo "总计: " . ($success_count + $error_count) . " 个用户\n";

// 显示所有用户
echo "\n当前所有用户:\n";
// $stmt = $pdo->query("SELECT id, email, created_at FROM users ORDER BY created_at DESC");
$stmt = $pdo->query("SELECT id, email FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    // echo "ID: {$user['id']}, 邮箱: {$user['email']}, 创建时间: {$user['created_at']}\n";
    echo "ID: {$user['id']}, 邮箱: {$user['email']}\n";
}
?> 