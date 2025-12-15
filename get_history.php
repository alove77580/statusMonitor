<?php
require_once 'includes/functions.php';

// 设置响应头
header('Content-Type: application/json');

// 获取参数
$siteId = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$status = isset($_GET['status']) ? $_GET['status'] : ''; // 新增状态筛选参数

if (!$siteId) {
    echo json_encode(['success' => false, 'error' => '缺少网站ID参数']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // 验证网站是否存在
    $stmt = $pdo->prepare("SELECT id, name FROM monitor_sites WHERE id = ?");
    $stmt->execute([$siteId]);
    $site = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$site) {
        echo json_encode(['success' => false, 'error' => '网站不存在']);
        exit;
    }
    
    // 分页参数
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    // 构建WHERE条件
    $whereConditions = ['site_id = ?'];
    $params = [$siteId];
    
    // 如果指定了状态筛选
    if ($status && in_array($status, ['UP', 'DOWN'])) {
        $whereConditions[] = 'status = ?';
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // 获取日志总数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM monitor_logs WHERE $whereClause");
    $stmt->execute($params);
    $totalLogs = $stmt->fetchColumn();
    $totalPages = ceil($totalLogs / $perPage);
    
    // 获取日志列表
    $stmt = $pdo->prepare("SELECT * FROM monitor_logs WHERE $whereClause ORDER BY checked_at DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 获取各状态的数量统计
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM monitor_logs WHERE site_id = ? GROUP BY status");
    $stmt->execute([$siteId]);
    $statusStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'total' => 0,
        'up' => 0,
        'down' => 0
    ];
    
    foreach ($statusStats as $stat) {
        $stats['total'] += $stat['count'];
        if ($stat['status'] === 'UP') {
            $stats['up'] = $stat['count'];
        } else {
            $stats['down'] = $stat['count'];
        }
    }
    
    // 返回数据
    echo json_encode([
        'success' => true,
        'site_id' => $siteId,
        'site_name' => $site['name'],
        'logs' => $logs,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_logs' => $totalLogs,
        'per_page' => $perPage,
        'current_filter' => $status,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => '获取历史记录失败: ' . $e->getMessage()]);
}
?> 