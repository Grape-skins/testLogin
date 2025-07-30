<?php
// 获取分页参数，使用更安全的参数处理
// 支持GET和POST两种方式获取参数
$page = 1;
$per_page = 20;
$search_email = '';

// 从POST获取参数（优先）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = isset($_POST['per_page']) ? max(5, min(50, intval($_POST['per_page']))) : 20;
    
    if (isset($_POST['search_email'])) {
        $search_email = trim($_POST['search_email']);
        // 只过滤危险字符，保留邮箱的模糊搜索能力
        $search_email = preg_replace('/[<>"\']/', '', $search_email);
    }
    
    // 将参数存储到Session中
    $_SESSION['user_list_params'] = [
        'page' => $page,
        'per_page' => $per_page,
        'search_email' => $search_email
    ];
} else {
    // 从GET获取参数（向后兼容）
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? max(5, min(50, intval($_GET['per_page']))) : 20;
    
    if (isset($_GET['search_email'])) {
        $search_email = trim($_GET['search_email']);
        $search_email = preg_replace('/[<>"\']/', '', $search_email);
    }
    
    // 如果Session中有参数，优先使用Session中的参数
    if (isset($_SESSION['user_list_params'])) {
        $session_params = $_SESSION['user_list_params'];
        $page = $session_params['page'];
        $per_page = $session_params['per_page'];
        $search_email = $session_params['search_email'];
    }
}

// 计算偏移量
$offset = ($page - 1) * $per_page;

// 构建查询条件
$where_conditions = [];
$params = [];

if (!empty($search_email)) {
    $where_conditions[] = "email LIKE :search_email";
    $params['search_email'] = "%{$search_email}%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// 获取总记录数
$count_sql = "SELECT COUNT(*) FROM users {$where_clause} order by id desc";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();

// 计算总页数
$total_pages = ceil($total_records / $per_page);

// 获取用户列表
$sql = "SELECT id, email FROM users {$where_clause} ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

// 绑定参数
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 生成隐藏参数的分页链接
function generateHiddenPageUrl($page, $per_page, $search_email) {
    // 使用POST方法，参数通过隐藏表单字段传递
    $params = [
        'page' => $page,
        'per_page' => $per_page,
        'search_email' => $search_email
    ];
    
    // 返回JavaScript函数调用
    return "javascript:submitHiddenForm(" . json_encode($params) . ")";
}

// 生成分页导航
$pagination = [];
if ($total_pages > 1) {
    // 上一页
    if ($page > 1) {
        $pagination[] = [
            'url' => generateHiddenPageUrl($page - 1, $per_page, $search_email),
            'text' => '上一页',
            'class' => 'page-link'
        ];
    }
    
    // 页码
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);
    
    if ($start_page > 1) {
        $pagination[] = [
            'url' => generateHiddenPageUrl(1, $per_page, $search_email),
            'text' => '1',
            'class' => 'page-link'
        ];
        if ($start_page > 2) {
            $pagination[] = [
                'url' => '#',
                'text' => '...',
                'class' => 'page-ellipsis'
            ];
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $pagination[] = [
            'url' => generateHiddenPageUrl($i, $per_page, $search_email),
            'text' => $i,
            'class' => $i == $page ? 'page-link active' : 'page-link'
        ];
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $pagination[] = [
                'url' => '#',
                'text' => '...',
                'class' => 'page-ellipsis'
            ];
        }
        $pagination[] = [
            'url' => generateHiddenPageUrl($total_pages, $per_page, $search_email),
            'text' => $total_pages,
            'class' => 'page-link'
        ];
    }
    
    // 下一页
    if ($page < $total_pages) {
        $pagination[] = [
            'url' => generateHiddenPageUrl($page + 1, $per_page, $search_email),
            'text' => '下一页',
            'class' => 'page-link'
        ];
    }
}
?> 