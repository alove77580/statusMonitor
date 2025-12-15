<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'auth.php';

// 检查登录状态
checkLogin();

$error = '';
$success = '';

// 获取当前通知配置
$notificationConfig = getNotificationConfig();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notificationType = trim($_POST['notification_type'] ?? 'bark');
    $barkKey = trim($_POST['bark_key'] ?? '');
    $wechatWorkWebhook = trim($_POST['wechat_work_webhook'] ?? '');
    $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
    
    // 根据选择的类型，清空另一个类型的配置
    if ($notificationType == 'bark') {
        $wechatWorkWebhook = '';
    } else {
        $barkKey = '';
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE notification_config SET notification_type = ?, bark_key = ?, wechat_work_webhook = ?, is_enabled = ? WHERE id = ?");
        
        if ($stmt->execute([$notificationType, $barkKey, $wechatWorkWebhook, $isEnabled, $notificationConfig['id']])) {
            $success = '通知配置更新成功';
            // 更新本地数据
            $notificationConfig['notification_type'] = $notificationType;
            $notificationConfig['bark_key'] = $barkKey;
            $notificationConfig['wechat_work_webhook'] = $wechatWorkWebhook;
            $notificationConfig['is_enabled'] = $isEnabled;
        } else {
            $error = '更新失败，请重试';
        }
    } catch (Exception $e) {
        $error = '数据库错误: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>通知配置 - 管理后台</title>
    <link href="https://fastly.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cog me-2"></i>管理后台
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars(getCurrentUsername()); ?>
                </span>
                <a class="nav-link" href="index.php">
                    <i class="fas fa-arrow-left me-1"></i>返回列表
                </a>
                <a class="nav-link" href="logout.php" onclick="return confirm('确定要退出登录吗？')">
                    <i class="fas fa-sign-out-alt me-1"></i>退出登录
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-bell me-2"></i>通知配置
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>关于通知方式</h5>
                            <p class="mb-2">系统支持两种通知方式，请选择其中一种：</p>
                            <ul class="mb-0">
                                <li><strong>Bark通知：</strong>iOS推送服务，可以将网站状态变化推送到您的iPhone</li>
                                <li><strong>企业微信群机器人：</strong>通过企业微信群机器人webhook发送通知到企业微信群</li>
                            </ul>
                            <p class="mt-2 mb-0"><strong>注意：</strong>两种通知方式互斥，只能选择其中一种。</p>
                        </div>

                        <form method="POST" id="notificationForm">
                            <div class="mb-3">
                                <label class="form-label">通知方式</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="notification_type" id="notification_type_bark" value="bark" <?php echo (!isset($notificationConfig['notification_type']) || $notificationConfig['notification_type'] == 'bark') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notification_type_bark">
                                        <i class="fab fa-apple me-1"></i>Bark通知
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="notification_type" id="notification_type_wechat" value="wechat_work" <?php echo (isset($notificationConfig['notification_type']) && $notificationConfig['notification_type'] == 'wechat_work') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notification_type_wechat">
                                        <i class="fab fa-weixin me-1"></i>企业微信群机器人
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3" id="bark_config" style="display: <?php echo (!isset($notificationConfig['notification_type']) || $notificationConfig['notification_type'] == 'bark') ? 'block' : 'none'; ?>;">
                                <label for="bark_key" class="form-label">Bark推送密钥</label>
                                <input type="text" class="form-control" id="bark_key" name="bark_key" value="<?php echo htmlspecialchars($notificationConfig['bark_key'] ?? ''); ?>" placeholder="例如：xxxxxxxxxxxxxxxxxxxxxxxx">
                                <div class="form-text">
                                    <ol class="mb-0">
                                        <li>在App Store下载 <strong>Bark</strong> 应用</li>
                                        <li>打开应用获取推送密钥</li>
                                        <li>将密钥填入上面的输入框</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="mb-3" id="wechat_config" style="display: <?php echo (isset($notificationConfig['notification_type']) && $notificationConfig['notification_type'] == 'wechat_work') ? 'block' : 'none'; ?>;">
                                <label for="wechat_work_webhook" class="form-label">企业微信群机器人Webhook地址</label>
                                <input type="url" class="form-control" id="wechat_work_webhook" name="wechat_work_webhook" value="<?php echo htmlspecialchars($notificationConfig['wechat_work_webhook'] ?? ''); ?>" placeholder="https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=xxxxx">
                                <div class="form-text">
                                    <ol class="mb-0">
                                        <li>在企业微信群中添加群机器人</li>
                                        <li>获取机器人的Webhook地址</li>
                                        <li>将Webhook地址填入上面的输入框</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" <?php echo ($notificationConfig['is_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_enabled">
                                        启用通知
                                    </label>
                                </div>
                                <div class="form-text">启用后，当网站状态发生变化时会发送推送通知</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i>取消
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>保存配置
                                </button>
                            </div>
                        </form>

                        <hr class="my-4">

                        <h5><i class="fas fa-test-tube me-2"></i>测试通知</h5>
                        <p class="text-muted">点击下面的按钮测试通知是否正常工作：</p>
                        
                        <div class="d-grid gap-2 d-md-flex">
                            <button type="button" class="btn btn-outline-success" onclick="testNotification('UP')">
                                <i class="fas fa-check-circle me-1"></i>测试正常通知
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="testNotification('DOWN')">
                                <i class="fas fa-times-circle me-1"></i>测试异常通知
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light mt-5 py-4">
        <div class="container text-center">
            <p class="text-muted mb-0">
                <i class="fas fa-code me-1"></i>
                网站状态监控系统 v1.0 - 管理后台
            </p>
        </div>
    </footer>

    <script src="https://fastly.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 切换通知方式时显示/隐藏对应的配置
        document.querySelectorAll('input[name="notification_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const barkConfig = document.getElementById('bark_config');
                const wechatConfig = document.getElementById('wechat_config');
                
                if (this.value === 'bark') {
                    barkConfig.style.display = 'block';
                    wechatConfig.style.display = 'none';
                    // 清空企业微信配置
                    document.getElementById('wechat_work_webhook').value = '';
                } else {
                    barkConfig.style.display = 'none';
                    wechatConfig.style.display = 'block';
                    // 清空Bark配置
                    document.getElementById('bark_key').value = '';
                }
            });
        });

        function testNotification(status) {
            const notificationType = document.querySelector('input[name="notification_type"]:checked').value;
            const title = status === 'UP' ? '测试通知' : '测试异常';
            const body = status === 'UP' ? '这是一条测试正常状态的通知' : '这是一条测试异常状态的通知';
            
            let requestData = {
                notification_type: notificationType,
                title: title,
                body: body
            };
            
            if (notificationType === 'bark') {
                const barkKey = document.getElementById('bark_key').value;
                if (!barkKey) {
                    alert('请先设置Bark推送密钥');
                    return;
                }
                requestData.bark_key = barkKey;
            } else {
                const webhook = document.getElementById('wechat_work_webhook').value;
                if (!webhook) {
                    alert('请先设置企业微信群机器人Webhook地址');
                    return;
                }
                requestData.wechat_work_webhook = webhook;
            }
            
            fetch('test_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('测试通知发送成功！');
                } else {
                    alert('测试通知发送失败：' + data.message);
                }
            })
            .catch(error => {
                alert('发送测试通知时出错：' + error.message);
            });
        }
    </script>
</body>
</html> 