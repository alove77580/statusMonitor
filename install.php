<?php
// 检查是否已经安装
if (file_exists('config/database.php') && !isset($_GET['force'])) {
    echo '<!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>安装检查 - 网站状态监控</title>
        <link href="https://fastly.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">安装检查</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                系统已经安装完成！<br>
                                <a href="index.php" class="alert-link">点击这里访问前台</a> | 
                                <a href="admin/" class="alert-link">点击这里访问管理后台</a>
                            </div>
                            <p class="text-muted">如需重新安装，请删除 config/database.php 文件或访问 <a href="?force=1">install.php?force=1</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dbHost = trim($_POST['db_host'] ?? '');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    
    // 验证输入
    if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
        $error = '请填写所有必需的数据库信息';
    } else {
        try {
            // 测试数据库连接
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 创建配置文件
            $configContent = "<?php
// 数据库配置
define('DB_HOST', '" . addslashes($dbHost) . "');
define('DB_NAME', '" . addslashes($dbName) . "');
define('DB_USER', '" . addslashes($dbUser) . "');
define('DB_PASS', '" . addslashes($dbPass) . "');

// 创建数据库连接
function getDBConnection() {
    try {
        \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8\", DB_USER, DB_PASS);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return \$pdo;
    } catch(PDOException \$e) {
        die(\"数据库连接失败: \" . \$e->getMessage());
    }
}
?>";
            
            if (file_put_contents('config/database.php', $configContent)) {
                // 导入数据库结构
                $sqlFile = file_get_contents('install/install.sql');
                
                // 分割SQL语句并逐条执行
                $sqlStatements = array_filter(array_map('trim', explode(';', $sqlFile)));
                $executedStatements = 0;
                $totalStatements = count($sqlStatements);
                
                foreach ($sqlStatements as $sql) {
                    if (!empty($sql)) {
                        try {
                            $pdo->exec($sql);
                            $executedStatements++;
                        } catch (Exception $e) {
                            // 记录错误但继续执行
                            error_log("SQL执行错误: " . $e->getMessage() . " - SQL: " . $sql);
                        }
                    }
                }
                
                // 检查是否需要执行额外的SQL文件
                if (file_exists('install/add_sort_order.sql')) {
                    $additionalSqlFile = file_get_contents('install/add_sort_order.sql');
                    $additionalStatements = array_filter(array_map('trim', explode(';', $additionalSqlFile)));
                    
                    foreach ($additionalStatements as $sql) {
                        if (!empty($sql)) {
                            try {
                                $pdo->exec($sql);
                            } catch (Exception $e) {
                                // 忽略已存在字段的错误
                                if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                                    error_log("额外SQL执行错误: " . $e->getMessage());
                                }
                            }
                        }
                    }
                }
                
                $success = "安装完成！系统已成功配置。\n执行了 {$executedStatements}/{$totalStatements} 条SQL语句。";
            } else {
                $error = '无法创建配置文件，请检查目录权限';
            }
        } catch (Exception $e) {
            $error = '数据库连接失败: ' . $e->getMessage();
        }
    }
}

// 检查系统要求
$requirements = [
    'PHP版本 >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO扩展' => extension_loaded('pdo'),
    'PDO MySQL扩展' => extension_loaded('pdo_mysql'),
    'cURL扩展' => extension_loaded('curl'),
    'JSON扩展' => extension_loaded('json'),
    'MBString扩展' => extension_loaded('mbstring'),
    'OpenSSL扩展' => extension_loaded('openssl'),
    'config目录可写' => is_writable('config') || is_writable('.'),
    'install目录可读' => is_readable('install'),
    'includes目录可读' => is_readable('includes'),
    'cron目录可读' => is_readable('cron'),
];

$allRequirementsMet = true;
foreach ($requirements as $requirement => $met) {
    if (!$met) $allRequirementsMet = false;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 网站状态监控</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 20px 20px 0 0 !important;
            border: none;
            padding: 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(102, 126, 234, 0.4);
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e40af;
        }
        
        .text-success {
            color: #059669 !important;
        }
        
        .text-danger {
            color: #dc2626 !important;
        }
        
        .badge {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        
        .badge.bg-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
        }
        
        .badge.bg-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        }
        
        .badge.bg-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-1px);
        }
        
        .form-text {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .code-block {
            background: #1f2937;
            color: #f9fafb;
            padding: 1rem;
            border-radius: 10px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.875rem;
            overflow-x: auto;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .step.completed {
            background: #10b981;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-download me-2"></i>网站状态监控系统安装向导
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- 步骤指示器 -->
                        <div class="step-indicator">
                            <div class="step <?php echo !$success ? 'active' : 'completed'; ?>">1</div>
                            <div class="step <?php echo $success ? 'active' : ''; ?>">2</div>
                        </div>
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                                <div>
                                    <h5 class="alert-heading">🎉 安装成功！</h5>
                                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($success)); ?></p>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-home me-2"></i>前台访问</h6>
                                            <p class="text-muted small">查看监控状态和网站列表</p>
                                            <a href="index.php" class="btn btn-primary">
                                                <i class="fas fa-home me-1"></i>访问前台
                                            </a>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-cog me-2"></i>管理后台</h6>
                                            <p class="text-muted small">添加和管理监控网站</p>
                                            <a href="admin/" class="btn btn-outline-primary">
                                                <i class="fas fa-cog me-1"></i>访问管理后台
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 p-3 rounded" style="background: #f0f9ff; border: 1px solid #bae6fd;">
                                        <h6><i class="fas fa-lightbulb me-2"></i>下一步操作</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li><i class="fas fa-arrow-right text-primary me-2"></i>访问管理后台添加监控网站</li>
                                            <li><i class="fas fa-arrow-right text-primary me-2"></i>配置cron定时任务启用自动监控</li>
                                            <li><i class="fas fa-arrow-right text-primary me-2"></i>根据需要调整通知设置</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        
                        <h5><i class="fas fa-list-check me-2"></i>系统要求检查</h5>
                        <div class="row mb-4">
                            <?php foreach ($requirements as $requirement => $met): ?>
                            <div class="col-md-6 mb-2">
                                <div class="d-flex align-items-center p-3 rounded" style="background: <?php echo $met ? '#f0fdf4' : '#fef2f2'; ?>; border: 1px solid <?php echo $met ? '#bbf7d0' : '#fecaca'; ?>;">
                                    <div class="me-3">
                                        <?php if ($met): ?>
                                        <i class="fas fa-check-circle text-success fa-lg"></i>
                                        <?php else: ?>
                                        <i class="fas fa-times-circle text-danger fa-lg"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo $requirement; ?></div>
                                        <small class="text-muted">
                                            <?php if ($met): ?>
                                            <span class="text-success">✓ 通过</span>
                                            <?php else: ?>
                                            <span class="text-danger">✗ 未通过</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!$allRequirementsMet): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            请先解决上述系统要求问题，然后重新运行安装程序。
                        </div>
                        <?php else: ?>
                        
                        <h5><i class="fas fa-database me-2"></i>数据库配置</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="db_host" class="form-label">数据库主机 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                <div class="form-text">通常是 localhost 或 127.0.0.1</div>
                            </div>

                            <div class="mb-3">
                                <label for="db_name" class="form-label">数据库名称 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="db_name" name="db_name" value="status_monitor" required>
                                <div class="form-text">请确保数据库已创建</div>
                            </div>

                            <div class="mb-3">
                                <label for="db_user" class="form-label">数据库用户名 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                            </div>

                            <div class="mb-3">
                                <label for="db_pass" class="form-label">数据库密码</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass">
                                <div class="form-text">如果数据库没有密码可以留空</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-play me-1"></i>开始安装
                                </button>
                            </div>
                        </form>
                        
                        <?php endif; ?>
                        
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>安装说明</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-database me-2"></i>数据库准备</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>创建MySQL数据库</li>
                                    <li><i class="fas fa-check text-success me-2"></i>确保数据库用户有足够权限</li>
                                    <li><i class="fas fa-check text-success me-2"></i>记录数据库连接信息</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-server me-2"></i>服务器配置</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>确保PHP扩展已启用</li>
                                    <li><i class="fas fa-check text-success me-2"></i>检查目录权限</li>
                                    <li><i class="fas fa-check text-success me-2"></i>配置cron定时任务</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h6><i class="fas fa-terminal me-2"></i>Cron定时任务配置</h6>
                            <p class="text-muted mb-2">安装完成后，请添加以下cron任务来启用自动监控：</p>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="code-block flex-grow-1">
                                    <code><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/cron/check_sites.php'; ?></code>
                                </div>
                                <button type="button" class="btn btn-outline-secondary copy-btn" onclick="copyCronCommand()" title="复制到剪贴板">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                这将每分钟检查一次所有监控网站的状态
                            </small>
                        </div>
                        
                        <div class="mt-4">
                            <h6><i class="fas fa-shield-alt me-2"></i>安全建议</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-lock text-warning me-2"></i>安装完成后删除install.php文件</li>
                                <li><i class="fas fa-lock text-warning me-2"></i>修改默认管理员密码</li>
                                <li><i class="fas fa-lock text-warning me-2"></i>定期备份数据库</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 表单验证增强
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('请填写所有必需的字段');
                    return;
                }
                
                // 显示加载状态
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>安装中...';
                submitBtn.disabled = true;
            });
            
            // 实时验证
            const inputs = form.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && !this.value.trim()) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            });
        });
        
        // 复制cron命令到剪贴板
        function copyCronCommand() {
            const cronCommand = document.querySelector('.code-block code').textContent;
            navigator.clipboard.writeText(cronCommand).then(() => {
                // 显示复制成功提示
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check me-1"></i>已复制';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-secondary');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
            });
        }
    </script>
</body>
</html> 