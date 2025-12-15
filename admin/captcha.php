<?php
session_start();

// 生成验证码
function generateCaptcha() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $captcha = '';
    for ($i = 0; $i < 4; $i++) {
        $captcha .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $captcha;
}

// 生成新的验证码
$_SESSION['captcha'] = generateCaptcha();

// 返回验证码文本
echo $_SESSION['captcha'];
?> 