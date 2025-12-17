<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'auth.php';

// 检查登录状态
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不允许']);
    exit;
}

// 获取POST数据
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => '无效的请求数据']);
    exit;
}

$notificationType = trim($input['notification_type'] ?? 'bark');
$title = trim($input['title'] ?? '');
$body = trim($input['body'] ?? '');
$status = trim($input['status'] ?? 'UP');

if (empty($title) || empty($body)) {
    echo json_encode(['success' => false, 'message' => '标题和内容不能为空']);
    exit;
}

// 发送测试通知
try {
    $result = false;
    $errorMsg = '通知发送失败';
    
    if ($notificationType == 'bark') {
        $barkKey = trim($input['bark_key'] ?? '');
        if (empty($barkKey)) {
            echo json_encode(['success' => false, 'message' => 'Bark密钥不能为空']);
            exit;
        }
        $result = sendBarkNotification($title, $body, $barkKey);
        $errorMsg = '通知发送失败，请检查Bark密钥是否正确';
    } elseif ($notificationType == 'wechat_work') {
        $webhook = trim($input['wechat_work_webhook'] ?? '');
        if (empty($webhook)) {
            echo json_encode(['success' => false, 'message' => '企业微信群机器人Webhook地址不能为空']);
            exit;
        }
        $result = sendWechatWorkNotification($title, $body, $webhook);
        $errorMsg = '通知发送失败，请检查企业微信群机器人Webhook地址是否正确';
    } elseif ($notificationType == 'telegram') {
        $botToken = trim($input['telegram_bot_token'] ?? '');
        $chatId = trim($input['telegram_chat_id'] ?? '');
        if (empty($botToken) || empty($chatId)) {
            echo json_encode(['success' => false, 'message' => 'Telegram Bot Token和Chat ID不能为空']);
            exit;
        }
        $result = sendTelegramNotification($title, $body, $botToken, $chatId);
        $errorMsg = '通知发送失败，请检查Telegram Bot Token和Chat ID是否正确';
    } elseif ($notificationType == 'custom_webhook') {
        $webhook = trim($input['custom_webhook'] ?? '');
        if (empty($webhook)) {
            echo json_encode(['success' => false, 'message' => '自定义Webhook地址不能为空']);
            exit;
        }
        $method = trim($input['custom_webhook_method'] ?? 'POST');
        $headers = trim($input['custom_webhook_headers'] ?? '');
        $bodyTemplate = trim($input['custom_webhook_body_template'] ?? '');
        $result = sendCustomWebhookNotification($title, $body, $status, $webhook, $method, $headers, $bodyTemplate);
        $errorMsg = '通知发送失败，请检查自定义Webhook配置是否正确';
    } else {
        echo json_encode(['success' => false, 'message' => '不支持的通知类型']);
        exit;
    }
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => '通知发送成功']);
    } else {
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '发送通知时出错：' . $e->getMessage()]);
}
?> 