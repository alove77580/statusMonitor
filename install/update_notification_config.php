<?php
/**
 * 更新通知配置表，添加企业微信群机器人webhook和通知类型字段
 * 使用方法：在浏览器中访问此文件，或通过命令行执行：php update_notification_config.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "开始更新通知配置表...\n";
    
    // 检查 wechat_work_webhook 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_config LIKE 'wechat_work_webhook'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "添加 wechat_work_webhook 字段...\n";
        $pdo->exec("ALTER TABLE notification_config 
                    ADD COLUMN wechat_work_webhook VARCHAR(500) COMMENT '企业微信群机器人webhook地址' AFTER bark_key");
        echo "✓ wechat_work_webhook 字段添加成功\n";
    } else {
        echo "✓ wechat_work_webhook 字段已存在，跳过\n";
    }
    
    // 检查 notification_type 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_config LIKE 'notification_type'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "添加 notification_type 字段...\n";
        $pdo->exec("ALTER TABLE notification_config 
                    ADD COLUMN notification_type ENUM('bark', 'wechat_work') DEFAULT 'bark' COMMENT '通知类型：bark或wechat_work' AFTER is_enabled");
        echo "✓ notification_type 字段添加成功\n";
    } else {
        echo "✓ notification_type 字段已存在，跳过\n";
    }
    
    // 更新已有数据
    $pdo->exec("UPDATE notification_config SET notification_type = 'bark' WHERE notification_type IS NULL");
    echo "✓ 数据更新完成\n";
    
    echo "\n通知配置表更新完成！\n";
    echo "现在您可以在管理后台的通知配置页面中选择使用Bark或企业微信群机器人通知。\n";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
?>

