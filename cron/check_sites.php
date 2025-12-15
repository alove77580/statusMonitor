<?php
require_once __DIR__ . '/../includes/functions.php';

// 设置脚本执行时间限制
set_time_limit(300);

echo "开始执行网站状态检查...\n";

try {
    // 获取所有启用的监控网站
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM monitor_sites WHERE is_active = 1");
    $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sites)) {
        echo "没有找到启用的监控网站\n";
        exit;
    }
    
    // 获取通知配置
    $notificationConfig = getNotificationConfig();
    
    foreach ($sites as $site) {
        // 检查是否需要执行检查（根据检查间隔）
        if (!shouldCheckSite($site['id'], $site['check_interval'])) {
            echo "跳过检查: {$site['name']} - 未到检查时间\n";
            continue;
        }
        
        echo "检查网站: {$site['name']} ({$site['url']})\n";
        
        // 检查网站状态
        $result = checkSiteStatus($site['url'], $site['type']);
        
        // 记录结果
        logMonitorResult($site['id'], $result['status'], $result['response_time'], $result['error_message']);
        
        // 获取上一次状态
        $lastStatus = getLatestStatus($site['id']);
        $previousStatus = null;
        
        // 获取倒数第二次的状态（用于比较）
        $stmt = $pdo->prepare("SELECT status FROM monitor_logs WHERE site_id = ? ORDER BY checked_at DESC LIMIT 1 OFFSET 1");
        $stmt->execute([$site['id']]);
        $previousResult = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($previousResult) {
            $previousStatus = $previousResult['status'];
        }
        
        // 如果状态发生变化且启用了通知，发送通知
        if ($notificationConfig['is_enabled'] && 
            $previousStatus !== null && 
            $previousStatus !== $result['status']) {
            
            $title = $result['status'] == 'UP' ? '网站恢复' : '网站异常';
            $body = "{$site['name']} ({$site['url']}) - " . 
                   ($result['status'] == 'UP' ? '已恢复正常' : '出现异常') .
                   " - 响应时间: {$result['response_time']}ms";
            
            $notificationType = $notificationConfig['notification_type'] ?? 'bark';
            $sendSuccess = false;
            
            if ($notificationType == 'bark' && !empty($notificationConfig['bark_key'])) {
                $sendSuccess = sendBarkNotification($title, $body, $notificationConfig['bark_key']);
            } elseif ($notificationType == 'wechat_work' && !empty($notificationConfig['wechat_work_webhook'])) {
                $sendSuccess = sendWechatWorkNotification($title, $body, $notificationConfig['wechat_work_webhook']);
            }
            
            if ($sendSuccess) {
                echo "通知发送成功\n";
            } else {
                echo "通知发送失败\n";
            }
        }
        
        echo "状态: " . getStatusText($result['status']) . 
             " | 响应时间: {$result['response_time']}ms\n";
        
        if ($result['error_message']) {
            echo "错误信息: {$result['error_message']}\n";
        }
        
        echo "---\n";
    }
    
    echo "网站状态检查完成\n";
    
} catch (Exception $e) {
    echo "执行过程中出现错误: " . $e->getMessage() . "\n";
}

/**
 * 检查是否应该检查指定网站
 * @param int $siteId 网站ID
 * @param int $checkInterval 检查间隔（秒）
 * @return bool
 */
function shouldCheckSite($siteId, $checkInterval) {
    $pdo = getDBConnection();
    
    // 获取最后一次检查时间
    $stmt = $pdo->prepare("SELECT checked_at FROM monitor_logs WHERE site_id = ? ORDER BY checked_at DESC LIMIT 1");
    $stmt->execute([$siteId]);
    $lastCheck = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lastCheck) {
        // 如果从未检查过，立即检查
        return true;
    }
    
    $lastCheckTime = strtotime($lastCheck['checked_at']);
    $currentTime = time();
    $timeDiff = $currentTime - $lastCheckTime;
    
    // 如果距离上次检查的时间超过了设定的检查间隔，则执行检查
    return $timeDiff >= $checkInterval;
}
?> 