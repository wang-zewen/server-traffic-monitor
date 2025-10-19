
<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/octet-stream');
header('Cache-Control: no-cache, no-store, must-revalidate');

// 生成指定大小的随机数据用于测速
// 默认 10MB
$size = isset($_GET['size']) ? intval($_GET['size']) : 10;
$size = min($size, 100); // 最大 100MB
$size = max($size, 1);   // 最小 1MB

$chunk_size = 1024 * 1024; // 1MB chunks
$total_size = $size * $chunk_size;

// 生成随机数据
$data = str_repeat('0123456789', $chunk_size / 10);

$sent = 0;
while ($sent < $total_size) {
    $remaining = $total_size - $sent;
    $to_send = min($chunk_size, $remaining);
    echo substr($data, 0, $to_send);
    $sent += $to_send;
    
    // 强制输出
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}
?>
