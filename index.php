<?php
require_once 'includes/functions.php';

// 获取所有监控网站
$sites = getAllMonitorSites();

// 获取每个网站的最新状态
foreach ($sites as &$site) {
    $site['latest_status'] = getLatestStatus($site['id']);
}
unset($site); // 取消引用，避免后续问题
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>星空-网站状态监控</title>
    <link href="https://fastly.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --border-color: #e5e7eb;
        }
        
        /* 深色主题变量 */
        [data-theme="dark"] {
            --primary-color: #818cf8;
            --secondary-color: #a78bfa;
            --success-color: #34d399;
            --danger-color: #f87171;
            --warning-color: #fbbf24;
            --info-color: #60a5fa;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --border-color: #374151;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #cbd5e1 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: var(--light-color);
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .navbar {
            background: rgba(15, 23, 42, 0.9) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }
        
        .navbar-brand {
            color: var(--dark-color) !important;
        }
        
        [data-theme="dark"] .navbar-brand {
            color: var(--light-color) !important;
        }
        
        .nav-link {
            color: var(--dark-color) !important;
        }
        
        [data-theme="dark"] .nav-link {
            color: var(--light-color) !important;
        }
        
        .nav-link:hover {
            color: var(--primary-color) !important;
        }
        
        .theme-toggle {
            background: none;
            border: none;
            color: var(--dark-color);
            font-size: 1.2rem;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        [data-theme="dark"] .theme-toggle {
            color: var(--light-color);
        }
        
        .theme-toggle:hover {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            transform: scale(1.1);
        }
        
        /* 返回星空按钮样式 */
        .navbar .btn-outline-primary {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }
        
        .navbar .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(99, 102, 241, 0.3);
        }
        
        [data-theme="dark"] .navbar .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        [data-theme="dark"] .navbar .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            margin-top: 2rem;
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .main-container {
            background: rgba(15, 23, 42, 0.95);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .header-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            color: var(--dark-color);
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .header-section {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: var(--light-color);
        }
        
        .header-section h2 {
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
        }
        
        .header-section p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .header-section small {
            opacity: 0.8;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .header-section small {
            opacity: 0.7;
        }
        
        .header-section small[onclick] {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .header-section small[onclick]:hover {
            opacity: 1;
            color: var(--primary-color);
        }
        
        [data-theme="dark"] .header-section small[onclick]:hover {
            color: var(--primary-color);
        }
        
        /* 状态信息卡片样式 */
        .status-info-cards {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .status-card {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            min-width: 160px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .status-card:hover::before {
            opacity: 1;
        }
        
        [data-theme="dark"] .status-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .status-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        [data-theme="dark"] .status-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        
        .status-card.clickable {
            cursor: pointer;
        }
        
        .status-card.clickable:hover {
            background: rgba(99, 102, 241, 0.1);
            border-color: var(--primary-color);
        }
        
        [data-theme="dark"] .status-card.clickable:hover {
            background: rgba(99, 102, 241, 0.2);
        }
        
        .status-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 8px;
            margin-right: 0.75rem;
            color: white;
            font-size: 0.9rem;
        }
        
        .status-content {
            flex: 1;
        }
        
        .status-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--dark-color);
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        [data-theme="dark"] .status-label {
            color: var(--light-color);
        }
        
        .status-value {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        [data-theme="dark"] .status-value {
            color: var(--light-color);
        }
        
        /* 加载动画 */
        .status-card.loading {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        .status-card.loading .status-icon {
            animation: spin 1s linear infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.02);
            }
        }
        
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        /* 响应式设计 */
        @media (max-width: 768px) {
            .status-info-cards {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .status-card {
                min-width: 200px;
                width: 100%;
                max-width: 280px;
            }
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.3);
        }
        
        .refresh-btn:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 8px 15px -3px rgba(99, 102, 241, 0.4);
        }
        
        .table-container {
            padding: 2rem;
        }
        
        .table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .table {
            background: #1e293b;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }
        
        .table thead th {
            background: linear-gradient(135deg, #ffffff, #f1f5f9);
            border: none;
            font-weight: 600;
            color: var(--dark-color);
            padding: 1.25rem 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .table thead th {
            background: linear-gradient(135deg, #334155, #475569);
            color: var(--light-color);
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            transform: scale(1.005);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme="dark"] .table tbody tr:hover {
            background: linear-gradient(135deg, #475569, #64748b);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }
        
        .table tbody tr:last-child {
            border-bottom: none;
        }
        
        .table td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            border: none;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .table td {
            color: var(--light-color);
        }
        
        .site-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .site-name {
            color: var(--light-color);
        }
        
        .site-url {
            color: #6b7280;
            font-size: 0.9rem;
            word-break: break-all;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .site-url {
            color: #9ca3af;
        }
        
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 500;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .badge.bg-success {
            background: linear-gradient(135deg, var(--success-color), #059669) !important;
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706) !important;
        }
        
        .badge.bg-info {
            background: linear-gradient(135deg, var(--info-color), #2563eb) !important;
        }
        
        .status-up {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .status-down {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .status-unknown {
            color: #6b7280;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .status-unknown {
            color: #9ca3af;
        }
        
        .response-time {
            font-weight: 600;
            color: var(--dark-color);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .response-time {
            color: var(--light-color);
        }
        

        
        .last-check {
            color: #6b7280;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .last-check {
            color: #9ca3af;
        }
        
        .error-message {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 0.5rem;
            color: var(--danger-color);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .error-message {
            background: linear-gradient(135deg, #450a0a, #7f1d1d);
            border: 1px solid #dc2626;
            color: #fca5a5;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .empty-state {
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .empty-state i {
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .empty-state h3 {
            color: var(--light-color);
        }
        
        .stats-bar {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .stats-bar {
            background: linear-gradient(135deg, #1e293b, #334155);
            border-bottom: 1px solid var(--border-color);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .stat-label {
            color: #9ca3af;
        }
        
        .footer {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            color: var(--dark-color);
            text-align: center;
            padding: 1.5rem;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .footer {
            background: rgba(15, 23, 42, 0.9);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--light-color);
        }
        
        /* 深色模式下的链接样式 */
        [data-theme="dark"] a {
            color: var(--primary-color);
        }
        
        [data-theme="dark"] a:hover {
            color: var(--secondary-color);
        }
        
        /* 深色模式下的文本样式 */
        [data-theme="dark"] .text-muted {
            color: #9ca3af !important;
        }
        
        /* 深色模式下的全局文字颜色 */
        [data-theme="dark"] * {
            color: inherit;
        }
        
        [data-theme="dark"] p, 
        [data-theme="dark"] span, 
        [data-theme="dark"] div {
            color: var(--light-color) !important;
        }
        
        [data-theme="dark"] h1, 
        [data-theme="dark"] h2, 
        [data-theme="dark"] h3, 
        [data-theme="dark"] h4, 
        [data-theme="dark"] h5, 
        [data-theme="dark"] h6 {
            color: var(--light-color) !important;
        }
        
        /* 深色模式下表格文字强制颜色 */
        [data-theme="dark"] .table,
        [data-theme="dark"] .table * {
            color: var(--light-color) !important;
        }
        
        /* 深色模式下保持特定元素的颜色 */
        [data-theme="dark"] .status-up {
            color: var(--success-color) !important;
        }
        
        [data-theme="dark"] .status-down {
            color: var(--danger-color) !important;
        }
        
        [data-theme="dark"] .badge {
            color: white !important;
        }
        
        /* 深色模式下统计数字颜色 */
        [data-theme="dark"] .stat-number {
            color: var(--primary-color) !important;
        }
        
        [data-theme="dark"] .stat-label {
            color: #9ca3af !important;
        }
        
        /* 历史记录弹框样式 */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .modal-content {
            background: #1e293b;
            color: var(--light-color);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-bottom: 1px solid var(--border-color);
            border-radius: 15px 15px 0 0;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .modal-header {
            background: linear-gradient(135deg, #1e293b, #334155);
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-title {
            font-weight: 600;
            color: var(--dark-color);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .modal-title {
            color: var(--light-color);
        }
        
        .modal-body {
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .modal-body {
            background: #1e293b;
        }
        
        .modal-footer {
            background: linear-gradient(135deg, #f8fafc, #ffffff);
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 15px 15px;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .modal-footer {
            background: linear-gradient(135deg, #334155, #1e293b);
            border-top: 1px solid var(--border-color);
        }
        
        .history-btn {
            border-radius: 20px;
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .history-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .history-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .history-table {
            background: #334155;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }
        
        .history-table th {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border: none;
            font-weight: 600;
            color: var(--dark-color);
            padding: 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .history-table th {
            background: linear-gradient(135deg, #475569, #64748b);
            color: var(--light-color);
        }
        
        .history-table td {
            padding: 1rem;
            border: none;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .history-table td {
            color: var(--light-color);
        }
        
        .history-table tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .history-table tbody tr:hover {
            background: linear-gradient(135deg, #475569, #64748b);
        }
        
        .history-pagination {
            margin-top: 1.5rem;
            justify-content: center;
        }
        
        .history-pagination .page-link {
            border-radius: 8px;
            margin: 0 2px;
            border: none;
            color: var(--dark-color);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .history-pagination .page-link {
            background: #334155;
            color: var(--light-color);
        }
        
        .history-pagination .page-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }
        
        .history-pagination .page-item.active .page-link {
            background: var(--primary-color);
            color: white;
        }
        
        /* 历史记录筛选按钮样式 */
        .history-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            background: white;
            color: var(--dark-color);
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        [data-theme="dark"] .filter-btn {
            background: #334155;
            color: var(--light-color);
            border-color: var(--border-color);
        }
        
        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .filter-btn .count {
            background: rgba(255, 255, 255, 0.2);
            color: inherit;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .filter-btn.active .count {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .history-stats {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .history-stat-item {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        [data-theme="dark"] .history-stat-item {
            background: linear-gradient(135deg, #475569, #64748b);
            border-color: var(--border-color);
        }
        
        .history-stat-number {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }
        
        .history-stat-label {
            font-size: 0.8rem;
            color: var(--dark-color);
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        [data-theme="dark"] .history-stat-label {
            color: var(--light-color);
        }
        
        @media (max-width: 768px) {
            .header-section h2 {
                font-size: 2rem;
            }
            
            .table-responsive {
                border-radius: 15px;
            }
            
            .stats-bar {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .modal-dialog {
                margin: 1rem;
            }
            
            .history-filters {
                gap: 0.25rem;
            }
            
            .filter-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
            
            .history-stats {
                gap: 0.5rem;
            }
            
            .history-stat-item {
                padding: 0.5rem 0.75rem;
            }
            
            .history-stat-number {
                font-size: 1rem;
            }
            
            .history-stat-label {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-server me-2"></i>星空-网站状态监控
            </a>
            <div class="navbar-nav ms-auto">
                <a href="https://starry.itzkb.cn/" class="btn btn-outline-primary me-2" title="返回星空">
                    <i class="fas fa-home me-1"></i>返回星空
                </a>
                <button class="theme-toggle" onclick="toggleTheme()" title="切换主题">
                    <i class="fas fa-sun" id="theme-icon"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-container">
            <div class="header-section">
                <div class="row align-items-center">
                    <div class="col">
                        <h2><i class="fas fa-server me-3"></i>监控状态概览</h2>
                        <p>实时监控网站和API的连通状态</p>
                        
                        <!-- 状态信息卡片 -->
                        <div class="status-info-cards">
                            <div class="status-card">
                                <div class="status-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-label">最后更新</div>
                                    <div class="status-value" id="last-update">正在加载...</div>
                                </div>
                            </div>
                            
                            <div class="status-card">
                                <div class="status-icon">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-label">自动刷新</div>
                                    <div class="status-value" id="auto-refresh-status">每60秒</div>
                                </div>
                            </div>
                            
                            <div class="status-card clickable" onclick="toggleAutoRefresh()" id="auto-refresh-toggle">
                                <div class="status-icon">
                                    <i class="fas fa-pause"></i>
                                </div>
                                <div class="status-content">
                                    <div class="status-label">刷新控制</div>
                                    <div class="status-value">暂停自动刷新</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn refresh-btn" onclick="forceRefresh()">
                            <i class="fas fa-sync-alt me-2"></i>刷新状态
                        </button>
                    </div>
                </div>
            </div>

            <?php if (empty($sites)): ?>
            <div class="empty-state">
                <i class="fas fa-server"></i>
                <h3>暂无监控网站</h3>
                <p>请前往<a href="admin/" class="text-decoration-none fw-bold">管理后台</a>添加监控网站</p>
            </div>
            <?php else: ?>
            
            <?php
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
            ?>
            
            <div class="stats-bar">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $totalSites; ?></div>
                            <div class="stat-label">总监控数</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number" style="color: var(--success-color);"><?php echo $upSites; ?></div>
                            <div class="stat-label">正常运行</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number" style="color: var(--danger-color);"><?php echo $downSites; ?></div>
                            <div class="stat-label">异常状态</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number" style="color: var(--warning-color);"><?php echo $unknownSites; ?></div>
                            <div class="stat-label">未知状态</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>网站名称</th>
                                <th>URL</th>
                                <th>类型</th>
                                <th>状态</th>
                                <th>响应时间</th>
                                <th>最后检查</th>
                                <th>历史记录</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sites as $site): ?>
                            <tr>
                                <td>
                                    <div class="site-name"><?php echo htmlspecialchars($site['name']); ?></div>
                                </td>
                                <td>
                                    <div class="site-url"><?php echo htmlspecialchars($site['url']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $site['type'] == 'API' ? 'warning' : ($site['type'] == 'HTTPS' ? 'success' : 'info'); ?>">
                                        <?php echo $site['type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($site['latest_status']): ?>
                                    <span class="<?php echo getStatusClass($site['latest_status']['status']); ?>">
                                        <i class="fas fa-<?php echo $site['latest_status']['status'] == 'UP' ? 'check-circle' : 'times-circle'; ?> me-2"></i>
                                        <?php echo getStatusText($site['latest_status']['status']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="status-unknown">
                                        <i class="fas fa-question-circle me-2"></i>未知
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($site['latest_status']): ?>
                                    <span class="response-time"><?php echo $site['latest_status']['response_time']; ?>ms</span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($site['latest_status']): ?>
                                    <span class="last-check"><?php echo date('m-d H:i:s', strtotime($site['latest_status']['checked_at'])); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">从未检查</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary history-btn" onclick="showHistory(<?php echo $site['id']; ?>, '<?php echo htmlspecialchars($site['name']); ?>')">
                                        <i class="fas fa-history me-1"></i>历史记录
                                    </button>
                                </td>
                            </tr>
                            <?php if ($site['latest_status'] && $site['latest_status']['error_message']): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?php echo htmlspecialchars($site['latest_status']['error_message']); ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>

    <!-- 历史记录弹框 -->
    <div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historyModalLabel">
                        <i class="fas fa-history me-2"></i>监控历史记录
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="historyContent">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">加载中...</span>
                            </div>
                            <p class="mt-2">正在加载历史记录...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p class="mb-0">
                <i class="fas fa-code me-2"></i>
                网站状态监控系统
            </p>
        </div>
    </footer>

    <script src="https://fastly.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 主题切换功能
        function toggleTheme() {
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            const currentTheme = html.getAttribute('data-theme');
            
            if (currentTheme === 'dark') {
                html.removeAttribute('data-theme');
                themeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'dark');
            }
        }
        
        // 页面加载时恢复主题设置
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            const themeIcon = document.getElementById('theme-icon');
            
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                themeIcon.className = 'fas fa-moon';
            } else {
                themeIcon.className = 'fas fa-sun';
            }
            
            // 添加表格行进入动画
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // 添加统计数字动画
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(number => {
                const finalValue = parseInt(number.textContent);
                let currentValue = 0;
                const increment = finalValue / 20;
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    number.textContent = Math.floor(currentValue);
                }, 50);
            });
            
            // 初始化最后更新时间
            const now = new Date();
            const lastUpdateElement = document.getElementById('last-update');
            if (lastUpdateElement) {
                lastUpdateElement.textContent = '刚刚';
            }
            
            // 初始化状态卡片
            initStatusCards();
            
            // 启动自动刷新
            startAutoRefresh();
        });
        
        // 初始化状态卡片
        function initStatusCards() {
            // 设置初始状态
            const statusElement = document.getElementById('auto-refresh-status');
            if (statusElement) {
                statusElement.textContent = '每60秒';
            }
        }
        
        // 自动刷新相关变量
        let autoRefreshInterval;
        let countdownInterval;
        let countdown = 60;
        let autoRefreshEnabled = true;
        
        function updateCountdown() {
            const statusElement = document.getElementById('auto-refresh-status');
            if (statusElement) {
                statusElement.textContent = `${countdown}秒后更新`;
            }
        }
        
        function startAutoRefresh() {
            if (!autoRefreshEnabled) return;
            
            // 清除之前的定时器
            if (autoRefreshInterval) clearInterval(autoRefreshInterval);
            if (countdownInterval) clearInterval(countdownInterval);
            
            // 开始倒计时
            countdown = 60;
            updateCountdown();
            
            countdownInterval = setInterval(() => {
                if (!autoRefreshEnabled) return;
                
                countdown--;
                updateCountdown();
                
                if (countdown <= 0) {
                    refreshData();
                    countdown = 60;
                }
            }, 1000);
        }
        
        function toggleAutoRefresh() {
            autoRefreshEnabled = !autoRefreshEnabled;
            const toggleElement = document.getElementById('auto-refresh-toggle');
            const toggleIcon = toggleElement.querySelector('.status-icon i');
            const toggleValue = toggleElement.querySelector('.status-value');
            
            if (autoRefreshEnabled) {
                startAutoRefresh();
                toggleIcon.className = 'fas fa-pause';
                toggleValue.textContent = '暂停自动刷新';
            } else {
                // 停止倒计时
                if (countdownInterval) clearInterval(countdownInterval);
                const statusElement = document.getElementById('auto-refresh-status');
                if (statusElement) {
                    statusElement.textContent = '已暂停';
                }
                toggleIcon.className = 'fas fa-play';
                toggleValue.textContent = '恢复自动刷新';
            }
        }
        
        // 刷新监控数据
        function refreshData() {
            // 显示加载状态
            const lastUpdateElement = document.getElementById('last-update');
            const lastUpdateCard = lastUpdateElement?.closest('.status-card');
            if (lastUpdateElement) {
                lastUpdateElement.textContent = '正在更新数据...';
                // 添加加载动画
                if (lastUpdateCard) {
                    lastUpdateCard.classList.add('loading');
                }
            }
            
            return fetch('get_status.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateStats(data.stats);
                        updateTable(data.sites);
                        updateTimestamp(data.timestamp);
                        
                        // 移除加载动画
                        const lastUpdateCard = document.querySelector('.status-card.loading');
                        if (lastUpdateCard) {
                            lastUpdateCard.classList.remove('loading');
                        }
                    } else {
                        console.error('刷新数据失败:', data.error);
                        // 显示错误提示
                        showError('数据刷新失败: ' + data.error);
                        
                        // 移除加载动画
                        const lastUpdateCard = document.querySelector('.status-card.loading');
                        if (lastUpdateCard) {
                            lastUpdateCard.classList.remove('loading');
                        }
                    }
                })
                .catch(error => {
                    console.error('网络错误:', error);
                    showError('网络连接错误，请检查网络连接');
                    
                    // 移除加载动画
                    const lastUpdateCard = document.querySelector('.status-card.loading');
                    if (lastUpdateCard) {
                        lastUpdateCard.classList.remove('loading');
                    }
                });
        }
        
        // 强制刷新数据
        function forceRefresh() {
            // 添加加载动画
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>刷新中...';
            btn.disabled = true;
            
            // 刷新数据
            refreshData().finally(() => {
                // 恢复按钮状态
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 1000);
                
                // 重新启动自动刷新倒计时
                startAutoRefresh();
            });
        }
        
        // 启动自动刷新
        startAutoRefresh();
        
        // 显示错误提示
        function showError(message) {
            const lastUpdateElement = document.getElementById('last-update');
            if (lastUpdateElement) {
                lastUpdateElement.innerHTML = `<i class="fas fa-exclamation-triangle me-1 text-danger"></i>${message}`;
                // 3秒后恢复
                setTimeout(() => {
                    lastUpdateElement.innerHTML = `<i class="fas fa-clock me-1"></i>最后更新: 连接失败`;
                }, 3000);
            }
        }
        
        // 数字动画效果
        function animateNumber(element, newValue) {
            const currentValue = parseInt(element.textContent);
            if (currentValue === newValue) return;
            
            const diff = newValue - currentValue;
            const steps = 10;
            const increment = diff / steps;
            let current = currentValue;
            
            const timer = setInterval(() => {
                current += increment;
                if ((diff > 0 && current >= newValue) || (diff < 0 && current <= newValue)) {
                    current = newValue;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 50);
        }
        
        // 更新统计数据
        function updateStats(stats) {
            const statNumbers = document.querySelectorAll('.stat-number');
            if (statNumbers.length >= 4) {
                // 总监控数
                animateNumber(statNumbers[0], stats.total);
                // 正常运行
                animateNumber(statNumbers[1], stats.up);
                // 异常状态
                animateNumber(statNumbers[2], stats.down);
                // 未知状态
                animateNumber(statNumbers[3], stats.unknown);
            }
        }
        
        // HTML转义函数
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // 格式化日期
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) { // 1分钟内
                return '刚刚';
            } else if (diff < 3600000) { // 1小时内
                return Math.floor(diff / 60000) + '分钟前';
            } else if (diff < 86400000) { // 1天内
                return Math.floor(diff / 3600000) + '小时前';
            } else {
                return date.toLocaleDateString('zh-CN') + ' ' + date.toLocaleTimeString('zh-CN', {hour: '2-digit', minute: '2-digit'});
            }
        }
        
        // 更新表格数据
        function updateTable(sites) {
            const tbody = document.querySelector('tbody');
            if (!tbody) return;
            
            let tableHTML = '';
            
            sites.forEach(site => {
                const status = site.latest_status;
                const statusClass = status ? (status.status === 'UP' ? 'status-up' : 'status-down') : 'status-unknown';
                const statusIcon = status ? (status.status === 'UP' ? 'check-circle' : 'times-circle') : 'question-circle';
                const statusText = status ? (status.status === 'UP' ? '正常' : '异常') : '未知';
                
                tableHTML += `
                    <tr>
                        <td>
                            <div class="site-name">${escapeHtml(site.name)}</div>
                        </td>
                        <td>
                            <div class="site-url">${escapeHtml(site.url)}</div>
                        </td>
                        <td>
                            <span class="badge bg-${site.type == 'API' ? 'warning' : (site.type == 'HTTPS' ? 'success' : 'info')}">
                                ${site.type}
                            </span>
                        </td>
                        <td>
                            ${status ? 
                                `<span class="${statusClass}">
                                    <i class="fas fa-${statusIcon} me-2"></i>
                                    ${statusText}
                                </span>` : 
                                `<span class="status-unknown">
                                    <i class="fas fa-question-circle me-2"></i>未知
                                </span>`
                            }
                        </td>
                        <td>
                            ${status ? 
                                `<span class="response-time">${status.response_time}ms</span>` : 
                                `<span class="text-muted">-</span>`
                            }
                        </td>
                        <td>
                            ${status ? 
                                `<span class="last-check">${formatDate(status.checked_at)}</span>` : 
                                `<span class="text-muted">从未检查</span>`
                            }
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary history-btn" onclick="showHistory(${site.id}, '${escapeHtml(site.name)}')">
                                <i class="fas fa-history me-1"></i>历史记录
                            </button>
                        </td>
                    </tr>
                `;
                
                // 如果有错误信息，添加错误行
                if (status && status.error_message) {
                    tableHTML += `
                        <tr>
                            <td colspan="7">
                                <div class="error-message">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    ${escapeHtml(status.error_message)}
                                </div>
                            </td>
                        </tr>
                    `;
                }
            });
            
            tbody.innerHTML = tableHTML;
            
            // 重新添加表格行动画
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        }
        
        // 更新时间戳
        function updateTimestamp(timestamp) {
            const lastUpdateElement = document.getElementById('last-update');
            if (lastUpdateElement) {
                const date = new Date(timestamp);
                const now = new Date();
                const diff = now - date;
                
                let timeText;
                if (diff < 60000) { // 1分钟内
                    timeText = '刚刚';
                } else if (diff < 3600000) { // 1小时内
                    timeText = Math.floor(diff / 60000) + '分钟前';
                } else if (diff < 86400000) { // 1天内
                    timeText = Math.floor(diff / 3600000) + '小时前';
                } else {
                    timeText = date.toLocaleDateString('zh-CN') + ' ' + date.toLocaleTimeString('zh-CN', {hour: '2-digit', minute: '2-digit'});
                }
                
                lastUpdateElement.textContent = timeText;
            }
        }
        
        // 显示历史记录
        function showHistory(siteId, siteName) {
            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            const modalTitle = document.getElementById('historyModalLabel');
            const historyContent = document.getElementById('historyContent');
            
            // 更新弹框标题
            modalTitle.innerHTML = `<i class="fas fa-history me-2"></i>${siteName} - 监控历史记录`;
            
            // 显示加载状态
            historyContent.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">加载中...</span>
                    </div>
                    <p class="mt-2">正在加载历史记录...</p>
                </div>
            `;
            
            // 显示弹框
            modal.show();
            
            // 加载历史记录（默认显示全部）
            loadHistory(siteId, 1, '');
        }
        
        // 加载历史记录
        function loadHistory(siteId, page = 1, status = '') {
            const historyContent = document.getElementById('historyContent');
            
            // 构建URL参数
            let url = `get_history.php?site_id=${siteId}&page=${page}`;
            if (status) {
                url += `&status=${status}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderHistoryTable(data);
                    } else {
                        historyContent.innerHTML = `
                            <div class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                <p>加载历史记录失败：${data.error}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    historyContent.innerHTML = `
                        <div class="text-center text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p>网络错误，请重试</p>
                        </div>
                    `;
                });
        }
        
        // 渲染历史记录表格
        function renderHistoryTable(data) {
            const historyContent = document.getElementById('historyContent');
            const logs = data.logs;
            const currentPage = data.current_page;
            const totalPages = data.total_pages;
            const siteId = data.site_id;
            const currentFilter = data.current_filter || '';
            const stats = data.stats || { total: 0, up: 0, down: 0 };
            
            // 构建筛选按钮和统计信息
            let filterHTML = `
                <div class="history-stats">
                    <div class="history-stat-item">
                        <span class="history-stat-number">${stats.total}</span>
                        <span class="history-stat-label">总记录</span>
                    </div>
                    <div class="history-stat-item">
                        <span class="history-stat-number">${stats.up}</span>
                        <span class="history-stat-label">正常</span>
                    </div>
                    <div class="history-stat-item">
                        <span class="history-stat-number">${stats.down}</span>
                        <span class="history-stat-label">异常</span>
                    </div>
                </div>
                
                <div class="history-filters">
                    <button class="filter-btn ${currentFilter === '' ? 'active' : ''}" onclick="loadHistory(${siteId}, 1, '')">
                        <i class="fas fa-list"></i>
                        全部
                        <span class="count">${stats.total}</span>
                    </button>
                    <button class="filter-btn ${currentFilter === 'UP' ? 'active' : ''}" onclick="loadHistory(${siteId}, 1, 'UP')">
                        <i class="fas fa-check-circle"></i>
                        正常
                        <span class="count">${stats.up}</span>
                    </button>
                    <button class="filter-btn ${currentFilter === 'DOWN' ? 'active' : ''}" onclick="loadHistory(${siteId}, 1, 'DOWN')">
                        <i class="fas fa-times-circle"></i>
                        异常
                        <span class="count">${stats.down}</span>
                    </button>
                </div>
            `;
            
            if (logs.length === 0) {
                historyContent.innerHTML = filterHTML + `
                    <div class="text-center text-muted">
                        <i class="fas fa-inbox fa-2x mb-3"></i>
                        <p>暂无监控历史记录</p>
                    </div>
                `;
                return;
            }
            
            let tableHTML = filterHTML + `
                <div class="table-responsive">
                    <table class="table history-table">
                        <thead>
                            <tr>
                                <th>检查时间</th>
                                <th>状态</th>
                                <th>响应时间</th>
                                <th>错误信息</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            logs.forEach(log => {
                const statusClass = log.status === 'UP' ? 'status-up' : 'status-down';
                const statusIcon = log.status === 'UP' ? 'check-circle' : 'times-circle';
                const statusText = log.status === 'UP' ? '正常' : '异常';
                
                tableHTML += `
                    <tr>
                        <td>
                            <i class="fas fa-clock me-1 text-muted"></i>
                            ${new Date(log.checked_at).toLocaleString('zh-CN')}
                        </td>
                        <td>
                            <span class="${statusClass}">
                                <i class="fas fa-${statusIcon} me-1"></i>
                                ${statusText}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold">${log.response_time}ms</span>
                        </td>
                        <td>
                            ${log.error_message ? 
                                `<span class="text-danger small">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    ${log.error_message}
                                </span>` : 
                                '<span class="text-muted">-</span>'
                            }
                        </td>
                    </tr>
                `;
            });
            
            tableHTML += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // 添加分页
            if (totalPages > 1) {
                tableHTML += `
                    <nav aria-label="历史记录分页">
                        <ul class="pagination history-pagination">
                `;
                
                // 上一页
                if (currentPage > 1) {
                    tableHTML += `
                        <li class="page-item">
                            <a class="page-link" href="#" onclick="loadHistory(${siteId}, ${currentPage - 1}, '${currentFilter}'); return false;">
                                <i class="fas fa-chevron-left"></i> 上一页
                            </a>
                        </li>
                    `;
                }
                
                // 页码
                const startPage = Math.max(1, currentPage - 2);
                const endPage = Math.min(totalPages, currentPage + 2);
                
                for (let i = startPage; i <= endPage; i++) {
                    tableHTML += `
                        <li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="loadHistory(${siteId}, ${i}, '${currentFilter}'); return false;">${i}</a>
                        </li>
                    `;
                }
                
                // 下一页
                if (currentPage < totalPages) {
                    tableHTML += `
                        <li class="page-item">
                            <a class="page-link" href="#" onclick="loadHistory(${siteId}, ${currentPage + 1}, '${currentFilter}'); return false;">
                                下一页 <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    `;
                }
                
                tableHTML += `
                        </ul>
                    </nav>
                `;
            }
            
            historyContent.innerHTML = tableHTML;
        }
        
        // 添加表格行悬停效果
        document.addEventListener('mouseover', function(e) {
            if (e.target.closest('tbody tr')) {
                e.target.closest('tbody tr').style.transform = 'scale(1.01)';
            }
        });
        
        document.addEventListener('mouseout', function(e) {
            if (e.target.closest('tbody tr')) {
                e.target.closest('tbody tr').style.transform = 'scale(1)';
            }
        });
    </script>
</body>
</html> 