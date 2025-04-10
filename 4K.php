<?php
error_reporting(E_ALL);

// 获取当前时间
$currentTime = date('Y-m-d H:i:s');

// 记录开始时间
$startTime = microtime(true);

// 获取 URL 参数
$url = isset($_GET['url']) ? $_GET['url'] : '';
if (empty($url)) {
    header("Content-Type: text/plain; charset=utf-8");
    http_response_code(200);
    echo "蓝天白云解析客户端API";
    exit();
}

// API 接口列表
$apis = [
    'http://110.40.85.1/api/?key=Ls92INtWa&url=',
];

$handles = [];
$multiHandle = curl_multi_init();
$timeout = 20; // 每个请求的最大超时时间（秒）
$maxExecutionTime = 20; // 最大整体执行时间（秒）
$firstResponse = null;
$firstResponseTime = PHP_INT_MAX;

// 初始化 cURL 句柄并添加到 multi handle
foreach ($apis as $api) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api . urlencode($url));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_multi_add_handle($multiHandle, $ch);
    $handles[$api] = $ch;
}

// 执行并发请求
$running = null;
$startMultiExecTime = microtime(true);

do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);

    // 处理每个句柄的响应
    foreach ($handles as $api => $ch) {
        $result = curl_multi_getcontent($ch);
        $responseTime = microtime(true) - $startMultiExecTime;

        if ($result !== false) {
            $decoded = json_decode($result, true);
            if (isset($decoded['url']) && $responseTime < $firstResponseTime) {
                $firstResponse = $decoded;
                $firstResponseTime = $responseTime;

                // 关闭其他句柄和终止请求
                foreach ($handles as $otherApi => $otherCh) {
                    if ($otherApi !== $api) {
                        curl_multi_remove_handle($multiHandle, $otherCh);
                        curl_close($otherCh);
                    }
                }
                curl_multi_close($multiHandle);

                // 返回最快响应
                $endTime = microtime(true);
                $responseTimeText = sprintf('%.2f秒', number_format($endTime - $startTime, 2));

                $vurl = $firstResponse['url'] ?? '';
                $format = ''; // 默认为空字符串
                if (strpos($vurl, '.m3u8') !== false) {
                    $format = 'm3u8';
                } elseif (strpos($vurl, '.mp4') !== false) {
                    $format = 'mp4';
                } elseif (strpos($vurl, '.flv') !== false) {
                    $format = 'flv';
                } elseif (strpos($vurl, '.hls') !== false) {
                    $format = 'hls';
                }

                header("Content-Type: application/json; charset=utf-8");
                echo json_encode([
                    'code' => 200,
                    'msg' => '解析成功',
                    'API' => 'HJYUN-VIP',
                    'url' => $vurl,
                    'link' => $url,
                    'main' => '蓝天白云提供解析服务',
                    'from' => '110.40.85.39:81',
                    '当前用户反馈群' => '628383402',
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                exit();
            }
        }
    }

    if (microtime(true) - $startMultiExecTime > $maxExecutionTime) {
        break;
    }
} while ($running > 0);

// 处理解析失败情况
if (!$firstResponse || empty($firstResponse['url'])) {
    http_response_code(404);
    $response = [
        'code' => 404,
        'msg' => '解析失败',
        'url' => 'https://idc.weyun.site/蓝天白云.mp4',
        'link' => $url,
        'main' => '蓝天白云提供解析服务',
        'from' => '110.40.85.39:81',
        '当前用户反馈群' => '628383402',
    ];

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// 处理解析成功情况
$vurl = $firstResponse['url'] ?? '';
$format = ''; // 默认为空字符串
if (strpos($vurl, '.m3u8') !== false) {
    $format = 'm3u8';
} elseif (strpos($vurl, '.mp4') !== false) {
    $format = 'mp4';
} elseif (strpos($vurl, '.flv') !== false) {
    $format = 'flv';
} elseif (strpos($vurl, '.hls') !== false) {
    $format = 'hls';
}

// 返回成功的响应
$response = [
    'code' => 200,
    'msg' => '解析成功',
    'API' => 'HJYUN-VIP',
    'url' => $vurl,
    'link' => $url,
    'main' => '蓝天白云提供解析服务',
    'from' => '110.40.85.39:81',
    '当前用户反馈群' => '628383402',
];

header("Content-Type: application/json; charset=utf-8");
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
