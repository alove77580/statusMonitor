-- 创建数据库
CREATE DATABASE IF NOT EXISTS status_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE status_monitor;

-- 监控网站表
CREATE TABLE IF NOT EXISTS monitor_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT '网站名称',
    url VARCHAR(500) NOT NULL COMMENT '监控URL',
    type ENUM('HTTP', 'HTTPS', 'API') NOT NULL DEFAULT 'HTTP' COMMENT '监控类型',
    check_interval INT NOT NULL DEFAULT 60 COMMENT '检查间隔（秒）',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 监控状态记录表
CREATE TABLE IF NOT EXISTS monitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    status ENUM('UP', 'DOWN') NOT NULL COMMENT '状态',
    response_time INT COMMENT '响应时间（毫秒）',
    error_message TEXT COMMENT '错误信息',
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES monitor_sites(id) ON DELETE CASCADE
);

-- 通知配置表
CREATE TABLE IF NOT EXISTS notification_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bark_key VARCHAR(255) COMMENT 'Bark推送密钥',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用通知',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 插入默认通知配置
INSERT INTO notification_config (bark_key, is_enabled) VALUES ('', 1);

-- 插入示例监控网站
INSERT INTO monitor_sites (name, url, type, check_interval) VALUES 
('百度', 'https://www.baidu.com', 'HTTPS', 60),
('谷歌', 'https://www.google.com', 'HTTPS', 60),
('GitHub API', 'https://api.github.com', 'API', 300); 