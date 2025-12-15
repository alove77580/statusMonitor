<?php
require_once __DIR__ . '/../config/database.php';

/**
 * 检查网站状态
 */
function checkSiteStatus($url, $type = 'HTTP') {
    $startTime = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'StatusMonitor/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000); // 转换为毫秒
    
    if ($error) {
        return [
            'status' => 'DOWN',
            'response_time' => $responseTime,
            'error_message' => $error
        ];
    }
    
    if ($httpCode >= 200 && $httpCode < 400) {
        return [
            'status' => 'UP',
            'response_time' => $responseTime,
            'error_message' => null
        ];
    } else {
        return [
            'status' => 'DOWN',
            'response_time' => $responseTime,
            'error_message' => "HTTP状态码: $httpCode"
        ];
    }
}

/**
 * 发送Bark通知
 */
function sendBarkNotification($title, $body, $barkKey) {
    if (empty($barkKey)) {
        return false;
    }
    
    $url = "https://api.day.app/{$barkKey}/{$title}/{$body}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode == 200;
}

/**
 * 发送企业微信群机器人通知
 */
function sendWechatWorkNotification($title, $content, $webhook) {
    if (empty($webhook)) {
        return false;
    }
    
    // 企业微信群机器人支持markdown格式
    $data = [
        'msgtype' => 'markdown',
        'markdown' => [
            'content' => "## {$title}\n\n{$content}"
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        return isset($result['errcode']) && $result['errcode'] == 0;
    }
    
    return false;
}

/**
 * 获取所有监控网站
 */
function getAllMonitorSites() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM monitor_sites ORDER BY sort_order ASC, id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 获取网站的最新状态
 */
function getLatestStatus($siteId) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM monitor_logs WHERE site_id = ? ORDER BY checked_at DESC LIMIT 1");
    $stmt->execute([$siteId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 更新网站排序
 */
function updateSiteOrder($siteId, $newOrder) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE monitor_sites SET sort_order = ? WHERE id = ?");
    return $stmt->execute([$newOrder, $siteId]);
}

/**
 * 批量更新网站排序
 */
function updateSitesOrder($orderData) {
    $pdo = getDBConnection();
    $pdo->beginTransaction();
    
    try {
        foreach ($orderData as $order => $siteId) {
            $stmt = $pdo->prepare("UPDATE monitor_sites SET sort_order = ? WHERE id = ?");
            $stmt->execute([$order, $siteId]);
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * 记录监控结果
 */
function logMonitorResult($siteId, $status, $responseTime, $errorMessage = null) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO monitor_logs (site_id, status, response_time, error_message) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$siteId, $status, $responseTime, $errorMessage]);
}

/**
 * 获取通知配置
 */
function getNotificationConfig() {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM notification_config LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * 格式化时间间隔显示
 */
function formatInterval($seconds) {
    if ($seconds < 60) {
        return $seconds . '秒';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . '分钟';
    } else {
        return floor($seconds / 3600) . '小时';
    }
}

/**
 * 获取状态显示文本
 */
function getStatusText($status) {
    return $status == 'UP' ? '正常' : '异常';
}

/**
 * 获取状态CSS类
 */
function getStatusClass($status) {
    return $status == 'UP' ? 'status-up' : 'status-down';
}
?> 