<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'auth.php';

// 检查登录状态
checkLogin();

// 处理删除操作
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM monitor_sites WHERE id = ?");
    if ($stmt->execute([$_GET['delete']])) {
        $success = "监控网站删除成功";
    } else {
        $error = "删除失败";
    }
}

// 处理启用/禁用操作
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE monitor_sites SET is_active = NOT is_active WHERE id = ?");
    if ($stmt->execute([$_GET['toggle']])) {
        $success = "状态更新成功";
    } else {
        $error = "状态更新失败";
    }
}

// 处理排序更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        $orderData = [];
        foreach ($_POST['order'] as $index => $siteId) {
            $orderData[$index] = $siteId;
        }
        
        if (updateSitesOrder($orderData)) {
            $success = "排序更新成功";
        } else {
            $error = "排序更新失败";
        }
    }
}

// 获取所有监控网站
$sites = getAllMonitorSites();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - 网站状态监控</title>
    <link href="https://fastly.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cog me-2"></i>管理后台
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars(getCurrentUsername()); ?>
                </span>
                <a class="nav-link" href="../">
                    <i class="fas fa-home me-1"></i>返回前台
                </a>
                <a class="nav-link" href="logout.php" onclick="return confirm('确定要退出登录吗？')">
                    <i class="fas fa-sign-out-alt me-1"></i>退出登录
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>监控网站管理</h2>
                <p class="text-muted">管理所有监控网站和通知配置</p>
            </div>
            <div class="col-auto">
                <a href="add_site.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>添加监控网站
                </a>
                <a href="notification.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-bell me-1"></i>通知配置
                </a>
            </div>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (empty($sites)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            暂无监控网站，<a href="add_site.php" class="alert-link">点击这里</a>添加第一个监控网站。
        </div>
        <?php else: ?>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">监控网站列表</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="saveOrder()">
                    <i class="fas fa-save me-1"></i>保存排序
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">排序</th>
                                <th>网站名称</th>
                                <th>URL</th>
                                <th>类型</th>
                                <th>检查间隔</th>
                                <th>状态</th>
                                <th>最后检查</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-tbody">
                            <?php foreach ($sites as $site): ?>
                            <tr data-id="<?php echo $site['id']; ?>">
                                <td>
                                    <i class="fas fa-grip-vertical text-muted" style="cursor: move;"></i>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($site['name']); ?></strong>
                                </td>
                                <td>
                                    <span class="text-muted"><?php echo htmlspecialchars($site['url']); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $site['type'] == 'API' ? 'warning' : ($site['type'] == 'HTTPS' ? 'success' : 'info'); ?>">
                                        <?php echo $site['type']; ?>
                                    </span>
                                </td>
                                <td><?php echo formatInterval($site['check_interval']); ?></td>
                                <td>
                                    <?php 
                                    $latestStatus = getLatestStatus($site['id']);
                                    if ($latestStatus): 
                                    ?>
                                    <span class="<?php echo getStatusClass($latestStatus['status']); ?>">
                                        <i class="fas fa-<?php echo $latestStatus['status'] == 'UP' ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                                        <?php echo getStatusText($latestStatus['status']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">未知</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($latestStatus): 
                                        echo date('m-d H:i:s', strtotime($latestStatus['checked_at']));
                                    else: 
                                        echo '从未检查';
                                    endif; 
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="edit_site.php?id=<?php echo $site['id']; ?>" class="btn btn-outline-primary" title="编辑">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle=<?php echo $site['id']; ?>" class="btn btn-outline-<?php echo $site['is_active'] ? 'warning' : 'success'; ?>" title="<?php echo $site['is_active'] ? '禁用' : '启用'; ?>">
                                            <i class="fas fa-<?php echo $site['is_active'] ? 'pause' : 'play'; ?>"></i>
                                        </a>
                                        <a href="logs.php?id=<?php echo $site['id']; ?>" class="btn btn-outline-info" title="查看日志">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <a href="?delete=<?php echo $site['id']; ?>" class="btn btn-outline-danger" title="删除" onclick="return confirm('确定要删除这个监控网站吗？')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script>
        // 初始化拖拽排序
        new Sortable(document.getElementById('sortable-tbody'), {
            handle: '.fa-grip-vertical',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag'
        });
        
        // 保存排序
        function saveOrder() {
            const rows = document.querySelectorAll('#sortable-tbody tr');
            const order = [];
            
            rows.forEach((row, index) => {
                order.push(row.getAttribute('data-id'));
            });
            
            // 创建表单并提交
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'update_order';
            form.appendChild(actionInput);
            
            order.forEach((siteId, index) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order[]';
                input.value = siteId;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <style>
        .sortable-ghost {
            opacity: 0.5;
            background: #f8f9fa !important;
        }
        .sortable-chosen {
            background: #e3f2fd !important;
        }
        .sortable-drag {
            background: #fff !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</body>
</html> 