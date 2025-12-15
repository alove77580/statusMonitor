# 网站状态监控系统

一个简单易用的网站和API状态监控系统，支持HTTP、HTTPS和API监控，具备通知功能。

## 功能特性

- 🔍 **多类型监控**: 支持HTTP、HTTPS和API监控
- ⏰ **灵活间隔**: 可自定义检查间隔时间
- 📊 **实时状态**: 实时显示网站状态和响应时间
- 🔔 **通知提醒**: 支持Bark推送通知
- 🌙 **主题切换**: 支持浅色/深色主题切换
- 📱 **响应式设计**: 适配各种设备屏幕
- 🎯 **排序功能**: 支持拖拽排序，自定义显示顺序

## 安装步骤

1. **环境要求**
   - PHP 7.4+
   - MySQL 5.7+
   - Web服务器 (Apache/Nginx)

2. **数据库设置**
   ```sql
   -- 创建数据库
   CREATE DATABASE status_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **导入数据库结构**
   ```bash
   mysql -u username -p status_monitor < install/install.sql
   ```

4. **添加排序字段** (如果从旧版本升级)
   ```bash
   mysql -u username -p status_monitor < install/add_sort_order.sql
   ```

5. **配置数据库连接**
   - 编辑 `config/database.php`
   - 设置数据库连接参数

6. **设置定时任务**
   ```bash
   # 每分钟检查一次
   * * * * * php /path/to/your/project/cron/check_sites.php
   ```

## 使用说明

### 前台功能
- **状态概览**: 查看所有监控网站的状态
- **实时数据**: 显示响应时间和最后检查时间
- **主题切换**: 点击右上角图标切换浅色/深色主题
- **自动刷新**: 页面每60秒自动刷新

### 后台管理
- **添加网站**: 添加新的监控网站
- **编辑设置**: 修改网站配置和检查间隔
- **排序管理**: 
  - 拖拽左侧的排序图标调整顺序
  - 点击"保存排序"按钮保存更改
  - 排序会影响前台显示顺序
- **状态控制**: 启用/禁用监控
- **日志查看**: 查看历史监控记录
- **通知配置**: 设置Bark推送通知

### 排序功能使用
1. 在后台管理页面，每个网站行左侧有拖拽图标
2. 点击并拖拽图标可以调整网站顺序
3. 调整完成后点击"保存排序"按钮
4. 排序更改会立即反映在前台显示中

## 文件结构

```
statusMonitor/
├── admin/              # 后台管理
│   ├── index.php      # 网站管理列表
│   ├── add_site.php   # 添加网站
│   ├── edit_site.php  # 编辑网站
│   ├── logs.php       # 查看日志
│   └── notification.php # 通知配置
├── config/
│   └── database.php   # 数据库配置
├── cron/
│   └── check_sites.php # 定时检查脚本
├── includes/
│   └── functions.php  # 核心函数
├── install/
│   ├── install.sql    # 数据库结构
│   └── add_sort_order.sql # 排序字段
└── index.php          # 前台显示
```

## 技术栈

- **后端**: PHP 7.4+
- **数据库**: MySQL 5.7+
- **前端**: Bootstrap 5, Font Awesome
- **排序**: Sortable.js
- **主题**: CSS变量 + JavaScript

## 更新日志

### v1.1.0
- ✨ 新增拖拽排序功能
- 🎨 优化深色主题显示
- 🔧 改进数据库结构

### v1.0.0
- 🎉 初始版本发布
- 📊 基础监控功能
- 🔔 通知系统
- 🌙 主题切换

## 许可证

MIT License 