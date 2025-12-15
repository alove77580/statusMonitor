<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'auth.php';

// 检查登录状态
checkLogin();

/**
 * 验证URL是否有效（支持带和不带协议前缀的URL）
 */
function isValidUrl($url) {
    $url = trim($url);
    
    // 如果URL已经包含协议前缀，使用标准验证
    if (preg_match('/^https?:\/\//i', $url)) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    // 如果URL不包含协议前缀，添加http://前缀进行验证
    $urlWithProtocol = 'http://' . $url;
    return filter_var($urlWithProtocol, FILTER_VALIDATE_URL) !== false;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $type = $_POST['type'] ?? 'HTTP';
    $checkInterval = (int)($_POST['check_interval'] ?? 60);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // 验证输入
    if (empty($name)) {
        $error = '请输入网站名称';
    } elseif (empty($url)) {
        $error = '请输入监控URL';
    } elseif (!isValidUrl($url)) {
        $error = '请输入有效的URL地址';
    } elseif ($checkInterval < 15) {
        $error = '检查间隔不能少于15秒';
    } else {
        try {
            $pdo = getDBConnection();
            
            // 获取当前最大的排序值
            $stmt = $pdo->query("SELECT MAX(sort_order) as max_order FROM monitor_sites");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $newOrder = ($result['max_order'] ?? 0) + 1;
            
            $stmt = $pdo->prepare("INSERT INTO monitor_sites (name, url, type, check_interval, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $url, $type, $checkInterval, $isActive, $newOrder])) {
                $success = '监控网站添加成功';
                // 清空表单
                $name = $url = '';
                $type = 'HTTP';
                $checkInterval = 60;
                $isActive = 1;
            } else {
                $error = '添加失败，请重试';
            }
        } catch (Exception $e) {
            $error = '数据库错误: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加监控网站 - 管理后台</title>
    <link href="https://fastly.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* 自定义滑动条样式 */
        .form-range::-webkit-slider-thumb {
            background: #0d6efd;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .form-range::-moz-range-thumb {
            background: #0d6efd;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .form-range::-webkit-slider-track {
            background: #e9ecef;
            border-radius: 0.375rem;
        }
        
        .form-range::-moz-range-track {
            background: #e9ecef;
            border-radius: 0.375rem;
            border: none;
        }
        
        .form-range:focus::-webkit-slider-thumb {
            box-shadow: 0 0 0 1px #fff, 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        .form-range:focus::-moz-range-thumb {
            box-shadow: 0 0 0 1px #fff, 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        #interval_display {
            min-width: 80px;
            text-align: center;
        }
        
        .interval-markers {
            position: relative;
            margin-top: 10px;
        }
        
        .interval-markers .d-flex {
            position: relative;
            z-index: 1;
            position: relative;
            width: 100%;
        }
        
        .interval-markers span {
            font-size: 0.75rem;
            color: #6c757d;
            font-weight: 500;
            position: absolute;
            transform: translateX(-50%);
        }
        
        .quick-select {
            margin-top: 10px;
        }
        
        .quick-select .btn {
            transition: all 0.2s ease;
            border-radius: 20px;
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
        }
        
        .quick-select .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .quick-select .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-plus me-2"></i>添加监控网站
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

                        <form method="POST">
                            <div class="mb-3">
                                <label for="url" class="form-label">监控URL <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="url" class="form-control" id="url" name="url" value="<?php echo htmlspecialchars($url ?? ''); ?>" required placeholder="https://www.example.com">
                                    <button type="button" class="btn btn-outline-secondary" onclick="fetchSiteInfo()">
                                        <i class="fas fa-magic me-1"></i>自动获取
                                    </button>
                                </div>
                                <div class="form-text">请输入URL地址（如：www.baidu.com 或 https://www.baidu.com），失焦后会自动获取网站信息</div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">网站名称 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                <div class="form-text">为这个监控网站起一个易于识别的名称</div>
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label">监控类型</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="HTTP" <?php echo ($type ?? 'HTTP') == 'HTTP' ? 'selected' : ''; ?>>HTTP</option>
                                    <option value="HTTPS" <?php echo ($type ?? 'HTTP') == 'HTTPS' ? 'selected' : ''; ?>>HTTPS</option>
                                    <option value="API" <?php echo ($type ?? 'HTTP') == 'API' ? 'selected' : ''; ?>>API</option>
                                </select>
                                <div class="form-text">选择监控的类型，API类型通常用于监控接口服务</div>
                            </div>

                            <div class="mb-3">
                                <label for="check_interval" class="form-label">检查间隔</label>
                                <div class="d-flex align-items-center">
                                    <input type="range" class="form-range flex-grow-1 me-3" id="check_interval" name="check_interval" 
                                           min="15" max="3600" step="1" value="<?php echo $checkInterval ?? 60; ?>" 
                                           oninput="updateIntervalDisplay(this.value)">
                                    <span class="badge bg-primary fs-6" id="interval_display"><?php echo formatInterval($checkInterval ?? 60); ?></span>
                                </div>
                                <div class="form-text">拖动滑块选择检查间隔时间，或点击下方快速选择按钮</div>
                                <div class="quick-select mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setInterval(15)">15秒</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setInterval(30)">30秒</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setInterval(60)">1分钟</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setInterval(300)">5分钟</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setInterval(900)">15分钟</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setInterval(1800)">30分钟</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setInterval(3600)">1小时</button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo ($isActive ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        立即启用监控
                                    </label>
                                </div>
                                <div class="form-text">如果取消勾选，该监控网站将被禁用</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="index.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i>取消
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>保存
                                </button>
                            </div>
                        </form>
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
        // 页面加载完成后设置焦点
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('url').focus();
        });

        // URL输入框失焦时自动获取网站信息
        document.getElementById('url').addEventListener('blur', function() {
            const url = this.value.trim();
            if (url && url.length > 3) {
                fetchSiteInfo();
            }
        });

        // 验证URL格式
        function isValidUrl(string) {
            try {
                // 如果URL已经包含协议前缀，直接验证
                if (string.match(/^https?:\/\//i)) {
                    new URL(string);
                    return true;
                }
                
                // 如果URL不包含协议前缀，添加http://前缀进行验证
                new URL('http://' + string);
                return true;
            } catch (_) {
                return false;
            }
        }

        // 获取网站信息
        function fetchSiteInfo() {
            const urlInput = document.getElementById('url');
            const nameInput = document.getElementById('name');
            const typeSelect = document.getElementById('type');
            const url = urlInput.value.trim();

            if (!url) {
                showToast('请输入URL地址', 'error');
                return;
            }

            // 显示加载状态
            const fetchBtn = document.querySelector('button[onclick="fetchSiteInfo()"]');
            const originalText = fetchBtn.innerHTML;
            fetchBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>获取中...';
            fetchBtn.disabled = true;

            // 调用后端接口获取网站信息
            fetch('fetch_site_info.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ url: url })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 更新URL（添加协议前缀）
                    urlInput.value = data.url;
                    
                    // 自动设置网站名称（如果没有填写的话）
                    if (!nameInput.value.trim()) {
                        nameInput.value = data.site_name;
                    }
                    
                    // 设置监控类型
                    typeSelect.value = data.type;
                    
                    showToast('网站信息获取成功！', 'success');
                } else {
                    showToast(data.error || '获取网站信息失败', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('网络错误，请重试', 'error');
            })
            .finally(() => {
                // 恢复按钮状态
                setTimeout(() => {
                    fetchBtn.innerHTML = originalText;
                    fetchBtn.disabled = false;
                }, 500);
            });
        }

        // 更新间隔显示
        function updateIntervalDisplay(seconds) {
            const display = document.getElementById('interval_display');
            let text = '';
            
            if (seconds < 60) {
                text = seconds + '秒';
            } else if (seconds < 3600) {
                text = Math.floor(seconds / 60) + '分钟';
            } else {
                text = Math.floor(seconds / 3600) + '小时';
            }
            
            display.textContent = text;
            
            // 检查是否需要吸附到快速选择值
            const snappedValue = snapToQuickSelect(parseInt(seconds));
            if (snappedValue !== parseInt(seconds)) {
                const slider = document.getElementById('check_interval');
                slider.value = snappedValue;
                display.textContent = formatIntervalText(snappedValue);
                highlightSelectedButton(snappedValue);
            } else {
                highlightSelectedButton(parseInt(seconds));
            }
        }

        // 格式化时间显示文本
        function formatIntervalText(seconds) {
            if (seconds < 60) {
                return seconds + '秒';
            } else if (seconds < 3600) {
                return Math.floor(seconds / 60) + '分钟';
            } else {
                return Math.floor(seconds / 3600) + '小时';
            }
        }

        // 快速设置间隔
        function setInterval(seconds) {
            const slider = document.getElementById('check_interval');
            slider.value = seconds;
            updateIntervalDisplay(seconds);
            
            // 高亮当前选中的按钮
            highlightSelectedButton(seconds);
        }

        // 当滑块值改变时，自动吸附到最近的快速选择值
        function snapToQuickSelect(seconds) {
            const quickSelectValues = [15, 30, 60, 300, 900, 1800, 3600];
            let closest = quickSelectValues[0];
            let minDiff = Math.abs(seconds - closest);
            
            for (let value of quickSelectValues) {
                const diff = Math.abs(seconds - value);
                if (diff < minDiff) {
                    minDiff = diff;
                    closest = value;
                }
            }
            
            // 如果差值小于等于30秒，则吸附到最近的快速选择值
            if (minDiff <= 30) {
                return closest;
            }
            return seconds;
        }

        // 高亮选中的按钮
        function highlightSelectedButton(seconds) {
            // 移除所有按钮的高亮
            document.querySelectorAll('.quick-select .btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-secondary');
            });
            
            // 高亮当前选中的按钮
            const buttons = document.querySelectorAll('.quick-select .btn');
            const buttonMap = {
                15: 0, 30: 1, 60: 2, 300: 3, 900: 4, 1800: 5, 3600: 6
            };
            
            if (buttonMap.hasOwnProperty(seconds)) {
                const index = buttonMap[seconds];
                buttons[index].classList.remove('btn-outline-secondary');
                buttons[index].classList.add('btn-primary');
            }
        }

        // 页面加载时初始化按钮状态
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('url').focus();
            
            // 初始化快速选择按钮状态
            const currentValue = document.getElementById('check_interval').value;
            highlightSelectedButton(parseInt(currentValue));
        });

        // 显示提示信息
        function showToast(message, type = 'info') {
            // 移除现有的提示
            const existingToast = document.querySelector('.toast-message');
            if (existingToast) {
                existingToast.remove();
            }

            // 创建提示元素
            const toast = document.createElement('div');
            toast.className = `toast-message alert alert-${type === 'success' ? 'success' : 'warning'} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            // 3秒后自动移除
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 3000);
        }
    </script>
</body>
</html> 