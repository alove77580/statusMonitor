<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'status_monitor');
define('DB_USER', 'root');
define('DB_PASS', 'root');

// 创建数据库连接
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("数据库连接失败: " . $e->getMessage());
    }
}
?> 