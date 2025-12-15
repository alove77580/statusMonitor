<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'auth.php';

// 检查登录状态
checkLogin();

// 设置响应头
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '方法不允许']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$url = trim($input['url'] ?? '');

if (empty($url)) {
    echo json_encode(['error' => 'URL不能为空']);
    exit;
}

// 检查URL是否已经包含协议前缀
$originalUrl = $url;
$hasProtocol = preg_match('/^https?:\/\//i', $url);

if ($hasProtocol) {
    // 如果已经有协议前缀，直接使用该URL
    $protocols = [''];
    $urlWithoutProtocol = preg_replace('/^https?:\/\//i', '', $url);
    $originalProtocol = parse_url($url, PHP_URL_SCHEME);
} else {
    // 如果没有协议前缀，先尝试HTTPS，再尝试HTTP
    $protocols = ['https://', 'http://'];
    $urlWithoutProtocol = $url;
    $originalProtocol = null;
}

$siteInfo = null;

foreach ($protocols as $protocol) {
    $testUrl = $protocol . ltrim($urlWithoutProtocol, '/');
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'StatusMonitor/1.0');
        curl_setopt($ch, CURLOPT_NOBODY, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!$error && $httpCode >= 200 && $httpCode < 400) {
            // 成功访问，获取网站标题
            $title = '';
            if ($response) {
                // 提取网站标题
                if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $response, $matches)) {
                    $title = trim($matches[1]);
                    // 清理标题中的特殊字符
                    $title = preg_replace('/\s+/', ' ', $title);
                    $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                }
            }
            
            // 解析域名
            $parsedUrl = parse_url($testUrl);
            $hostname = $parsedUrl['host'] ?? '';
            
            // 生成友好的网站名称
            $siteName = '';
            if (!empty($title)) {
                // 使用网站标题作为名称
                $siteName = $title;
                // 如果标题太长，截取前30个字符
                if (mb_strlen($siteName) > 30) {
                    $siteName = mb_substr($siteName, 0, 30) . '...';
                }
            } else {
                // 使用域名作为名称
                $siteName = $hostname;
                // 移除www前缀
                $siteName = preg_replace('/^www\./', '', $siteName);
                // 首字母大写
                $siteName = ucfirst($siteName);
            }
            
            // 如果用户输入了协议前缀，优先使用用户输入的协议
            $finalUrl = $testUrl;
            if ($hasProtocol && $originalProtocol) {
                // 确保使用用户输入的协议
                $finalUrl = $originalProtocol . '://' . ltrim($urlWithoutProtocol, '/');
            }
            
            $siteInfo = [
                'success' => true,
                'url' => $finalUrl,
                'protocol' => parse_url($finalUrl, PHP_URL_SCHEME),
                'hostname' => $hostname,
                'title' => $title,
                'site_name' => $siteName,
                'type' => determineType($finalUrl, $hostname)
            ];
            break;
        }
    } catch (Exception $e) {
        continue;
    }
}

if (!$siteInfo) {
    echo json_encode(['error' => '无法访问该网站，请检查URL是否正确']);
    exit;
}

echo json_encode($siteInfo);

/**
 * 判断监控类型
 */
function determineType($url, $hostname) {
    // 检查是否是API端点
    if (strpos($url, '/api/') !== false || 
        strpos($url, '/v1/') !== false || 
        strpos($url, '/v2/') !== false || 
        strpos($url, '/rest/') !== false || 
        strpos($url, '/graphql') !== false || 
        strpos($hostname, 'api.') !== false || 
        preg_match('/\.(json|xml)$/', $url)) {
        return 'API';
    }
    
    // 根据协议判断
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if ($scheme === 'https') {
        return 'HTTPS';
    } else {
        return 'HTTP';
    }
}
?> 