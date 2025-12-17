<?php
/**
 * 更新通知配置表，添加Telegram Bot、自定义Webhook等通知类型
 * 使用方法：在浏览器中访问此文件，或通过命令行执行：php add_notification_types.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "开始更新通知配置表...\n";
    
    // 检查 telegram_bot_token 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_config LIKE 'telegram_bot_token'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "添加 telegram_bot_token 字段...\n";
        $pdo->exec("ALTER TABLE notification_config 
                    ADD COLUMN telegram_bot_token VARCHAR(255) COMMENT 'Telegram Bot Token' AFTER wechat_work_webhook");
        echo "✓ telegram_bot_token 字段添加成功\n";
    } else {
        echo "✓ telegram_bot_token 字段已存在，跳过\n";
    }
    
    // 检查 telegram_chat_id 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_config LIKE 'telegram_chat_id'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "添加 telegram_chat_id 字段...\n";
        $pdo->exec("ALTER TABLE notification_config 
                    ADD COLUMN telegram_chat_id VARCHAR(100) COMMENT 'Telegram Chat ID' AFTER telegram_bot_token");
        echo "✓ telegram_chat_id 字段添加成功\n";
    } else {
        echo "✓ telegram_chat_id 字段已存在，跳过\n";
    }
    
    // 检查 custom_webhook 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_config LIKE 'custom_webhook'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "添加 custom_webhook 字段...\n";
        $pdo->exec("ALTER TABLE notification_config 
                    ADD COLUMN custom_webhook VARCHAR(500) COMMENT '自定义Webhook地址' AFTER telegram_chat_id");
        echo "✓ custom_webhook 字段添加成功\n";
    } else {
        echo "✓ custom_webhook 字段已存在，跳过\n";
    }
    
    // 检查 custom_webhook_method 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_config LIKE 'custom_webhook_method'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "添加 custom_webhook_method 字段...\n";
        $pdo->exec("ALTER TABLE notification_config 
                    ADD COLUMN custom_webhook_method ENUM('POST', 'GET') DEFAULT 'POST' COMMENT '自定义Webhook请求方法' AFTER custom_webhook");
        echo "✓ custom_webhook_method 字段添加成功\n";
    } else {
        echo "✓ custom_webhook_method 字段已存在，跳过\n";
    }
    
    // 检查 custom_webhook_headers 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_config LIKE 'custom_webhook_headers'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "添加 custom_webhook_headers 字段...\n";
        $pdo->exec("ALTER TABLE notification_config 
                    ADD COLUMN custom_webhook_headers TEXT COMMENT '自定义Webhook请求头（JSON格式）' AFTER custom_webhook_method");
        echo "✓ custom_webhook_headers 字段添加成功\n";
    } else {
        echo "✓ custom_webhook_headers 字段已存在，跳过\n";
    }
    
    // 检查 custom_webhook_body_template 字段是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_config LIKE 'custom_webhook_body_template'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "添加 custom_webhook_body_template 字段...\n";
        $pdo->exec("ALTER TABLE notification_config 
                    ADD COLUMN custom_webhook_body_template TEXT COMMENT '自定义Webhook请求体模板（JSON格式，可用变量：{title}, {body}, {status}）' AFTER custom_webhook_headers");
        echo "✓ custom_webhook_body_template 字段添加成功\n";
    } else {
        echo "✓ custom_webhook_body_template 字段已存在，跳过\n";
    }
    
    // 更新 notification_type 枚举值，添加新类型
    $stmt = $pdo->query("SHOW COLUMNS FROM notification_config WHERE Field = 'notification_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        $currentType = $column['Type'];
        // 检查是否已经包含新类型
        if (strpos($currentType, 'telegram') === false || strpos($currentType, 'custom_webhook') === false) {
            echo "更新 notification_type 枚举值...\n";
            // 先删除旧的ENUM约束
            $pdo->exec("ALTER TABLE notification_config MODIFY COLUMN notification_type VARCHAR(50)");
            // 重新创建ENUM
            $pdo->exec("ALTER TABLE notification_config MODIFY COLUMN notification_type ENUM('bark', 'wechat_work', 'telegram', 'custom_webhook', 'dingtalk', 'slack') DEFAULT 'bark' COMMENT '通知类型'");
            echo "✓ notification_type 枚举值更新成功\n";
        } else {
            echo "✓ notification_type 枚举值已包含新类型，跳过\n";
        }
    }
    
    echo "\n通知配置表更新完成！\n";
    echo "现在您可以在管理后台的通知配置页面中选择使用以下通知方式：\n";
    echo "- Bark通知\n";
    echo "- 企业微信群机器人\n";
    echo "- Telegram Bot\n";
    echo "- 自定义Webhook\n";
    echo "- 钉钉群机器人（待实现）\n";
    echo "- Slack（待实现）\n";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}
?>

