<?php
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    // 获取所有监控网站
    $sites = getAllMonitorSites();
    
    // 获取每个网站的最新状态
    foreach ($sites as &$site) {
        $site['latest_status'] = getLatestStatus($site['id']);
    }
    unset($site);
    
    // 计算统计数据
    $totalSites = count($sites);
    $upSites = 0;
    $downSites = 0;
    $unknownSites = 0;
    
    foreach ($sites as $site) {
        if ($site['latest_status']) {
            if ($site['latest_status']['status'] == 'UP') {
                $upSites++;
            } else {
                $downSites++;
            }
        } else {
            $unknownSites++;
        }
    }
    
    $stats = [
        'total' => $totalSites,
        'up' => $upSites,
        'down' => $downSites,
        'unknown' => $unknownSites
    ];
    
    echo json_encode([
        'success' => true,
        'sites' => $sites,
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 