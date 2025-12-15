<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'auth.php';

// 检查登录状态
checkLogin();

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_log'])) {
    $log_id = (int)$_POST['log_id'];
    $site_id = (int)$_POST['site_id'];
    
    try {
        $pdo = getDBConnection();
        
        // 验证日志是否属于该网站
        $stmt = $pdo->prepare("SELECT id FROM monitor_logs WHERE id = ? AND site_id = ?");
        $stmt->execute([$log_id, $site_id]);
        
        if ($stmt->fetch()) {
            // 删除日志
            $stmt = $pdo->prepare("DELETE FROM monitor_logs WHERE id = ?");
            $stmt->execute([$log_id]);
            
            $success = '日志删除成功！';
        } else {
            $error = '日志不存在或无权限删除！';
        }
    } catch (Exception $e) {
        $error = '删除失败: ' . $e->getMessage();
    }
}

// 处理批量删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_delete'])) {
    $log_ids = $_POST['log_ids'] ?? [];
    $site_id = (int)$_POST['site_id'];
    
    if (!empty($log_ids)) {
        try {
            $pdo = getDBConnection();
            
            // 验证所有日志是否属于该网站
            $placeholders = str_repeat('?,', count($log_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM monitor_logs WHERE id IN ($placeholders) AND site_id = ?");
            $params = array_merge($log_ids, [$site_id]);
            $stmt->execute($params);
            
            if ($stmt->fetchColumn() == count($log_ids)) {
                // 批量删除日志
                $stmt = $pdo->prepare("DELETE FROM monitor_logs WHERE id IN ($placeholders)");
                $stmt->execute($log_ids);
                
                $success = '批量删除成功！共删除 ' . count($log_ids) . ' 条记录。';
            } else {
                $error = '部分日志不存在或无权限删除！';
            }
        } catch (Exception $e) {
            $error = '批量删除失败: ' . $e->getMessage();
        }
    } else {
        $error = '请选择要删除的日志！';
    }
}

// 处理清空日志请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $site_id = (int)$_POST['site_id'];
    
    try {
        $pdo = getDBConnection();
        
        // 验证网站是否存在
        $stmt = $pdo->prepare("SELECT id FROM monitor_sites WHERE id = ?");
        $stmt->execute([$site_id]);
        
        if ($stmt->fetch()) {
            // 获取删除前的日志数量
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM monitor_logs WHERE site_id = ?");
            $stmt->execute([$site_id]);
            $deletedCount = $stmt->fetchColumn();
            
            // 清空该网站的所有日志
            $stmt = $pdo->prepare("DELETE FROM monitor_logs WHERE site_id = ?");
            $stmt->execute([$site_id]);
            
            $success = '日志清空成功！共删除 ' . $deletedCount . ' 条记录。';
            
            // 重定向到第一页，避免分页问题
            header('Location: logs.php?id=' . $site_id);
            exit;
        } else {
            $error = '网站不存在！';
        }
    } catch (Exception $e) {
        $error = '清空日志失败: ' . $e->getMessage();
    }
}

// 获取网站ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// 获取网站信息
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM monitor_sites WHERE id = ?");
    $stmt->execute([$id]);
    $site = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$site) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    $error = '获取网站信息失败: ' . $e->getMessage();
}

// 分页参数
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// 获取日志总数
$stmt = $pdo->prepare("SELECT COUNT(*) FROM monitor_logs WHERE site_id = ?");
$stmt->execute([$id]);
$totalLogs = $stmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

// 获取日志列表
$stmt = $pdo->prepare("SELECT * FROM monitor_logs WHERE site_id = ? ORDER BY checked_at DESC LIMIT ? OFFSET ?");
$stmt->bindParam(1, $id, PDO::PARAM_INT);
$stmt->bindParam(2, $perPage, PDO::PARAM_INT);
$stmt->bindParam(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>监控日志 - <?php echo htmlspecialchars($site['name']); ?></title>
    <link href="https://fastly.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table th {
            background-color: #f8f9fa;
        }
        .status-up {
            color: #28a745;
        }
        .status-down {
            color: #dc3545;
        }
        .btn-delete {
            color: #dc3545;
            border-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #dc3545;
            color: white;
        }
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .select-all-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
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
        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col">
                <h2>
                    <i class="fas fa-history me-2"></i>监控日志
                </h2>
                <p class="text-muted">
                    网站：<strong><?php echo htmlspecialchars($site['name']); ?></strong> 
                    (<?php echo htmlspecialchars($site['url']); ?>)
                </p>
            </div>
            <div class="col-auto">
                <a href="edit_site.php?id=<?php echo $site['id']; ?>" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-1"></i>编辑网站
                </a>
                <?php if ($totalLogs > 0): ?>
                <button type="button" class="btn btn-outline-danger" onclick="clearAllLogs()">
                    <i class="fas fa-trash-alt me-1"></i>清空日志
                </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($logs)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            暂无监控日志记录。
        </div>
        <?php else: ?>
        
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="select-all-wrapper">
                        <input type="checkbox" id="selectAll" class="form-check-input">
                        <label for="selectAll" class="form-check-label">全选</label>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="batchDelete()" id="batchDeleteBtn" style="display: none;">
                            <i class="fas fa-trash me-1"></i>批量删除
                        </button>
                    </div>
                    <div>
                        <span>共 <?php echo $totalLogs; ?> 条记录</span>
                        <span class="ms-3">第 <?php echo $page; ?> 页，共 <?php echo $totalPages; ?> 页</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="batchDeleteForm" method="POST" style="display: none;">
                    <input type="hidden" name="batch_delete" value="1">
                    <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                    <input type="hidden" name="log_ids" id="logIds">
                </form>
                
                <form id="clearLogsForm" method="POST" style="display: none;">
                    <input type="hidden" name="clear_logs" value="1">
                    <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                </form>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" id="selectAllTable" class="form-check-input">
                                    </div>
                                </th>
                                <th>检查时间</th>
                                <th>状态</th>
                                <th>响应时间</th>
                                <th>错误信息</th>
                                <th width="100">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" class="form-check-input log-checkbox" value="<?php echo $log['id']; ?>">
                                    </div>
                                </td>
                                <td>
                                    <i class="fas fa-clock me-1 text-muted"></i>
                                    <?php echo date('Y-m-d H:i:s', strtotime($log['checked_at'])); ?>
                                </td>
                                <td>
                                    <span class="<?php echo getStatusClass($log['status']); ?>">
                                        <i class="fas fa-<?php echo $log['status'] == 'UP' ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                                        <?php echo getStatusText($log['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold"><?php echo $log['response_time']; ?>ms</span>
                                </td>
                                <td>
                                    <?php if ($log['error_message']): ?>
                                    <span class="text-danger small">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <?php echo htmlspecialchars($log['error_message']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除这条日志吗？')">
                                        <input type="hidden" name="delete_log" value="1">
                                        <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                        <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav aria-label="日志分页" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?id=<?php echo $id; ?>&page=<?php echo $page - 1; ?>">
                        <i class="fas fa-chevron-left"></i> 上一页
                    </a>
                </li>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?id=<?php echo $id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?id=<?php echo $id; ?>&page=<?php echo $page + 1; ?>">
                        下一页 <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
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
    <script>
        // 全选功能
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.log-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBatchDeleteButton();
        });
        
        document.getElementById('selectAllTable').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.log-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            document.getElementById('selectAll').checked = this.checked;
            updateBatchDeleteButton();
        });
        
        // 单个复选框变化
        document.querySelectorAll('.log-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBatchDeleteButton);
        });
        
        function updateBatchDeleteButton() {
            const checkedBoxes = document.querySelectorAll('.log-checkbox:checked');
            const batchDeleteBtn = document.getElementById('batchDeleteBtn');
            
            if (checkedBoxes.length > 0) {
                batchDeleteBtn.style.display = 'inline-block';
            } else {
                batchDeleteBtn.style.display = 'none';
            }
        }
        
        function batchDelete() {
            const checkedBoxes = document.querySelectorAll('.log-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('请选择要删除的日志！');
                return;
            }
            
            if (!confirm(`确定要删除选中的 ${checkedBoxes.length} 条日志吗？`)) {
                return;
            }
            
            const logIds = Array.from(checkedBoxes).map(cb => cb.value);
            document.getElementById('logIds').value = JSON.stringify(logIds);
            document.getElementById('batchDeleteForm').submit();
        }
        
        function clearAllLogs() {
            const totalLogs = <?php echo $totalLogs; ?>;
            if (totalLogs === 0) {
                alert('没有可清空的日志！');
                return;
            }
            
            if (!confirm(`警告：确定要清空该网站的所有 ${totalLogs} 条日志吗？\n\n此操作不可恢复！`)) {
                return;
            }
            
            // 二次确认
            if (!confirm('请再次确认：您真的要清空所有日志吗？')) {
                return;
            }
            
            document.getElementById('clearLogsForm').submit();
        }
    </script>
</body>
</html> 