
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 获取网卡名称
exec("ip route | grep default | awk '{print $5}'", $output);
$interface = trim($output[0]);
if (empty($interface)) {
    $interface = 'eth0';
}

// 获取当前流量值
$rx_file = "/sys/class/net/$interface/statistics/rx_bytes";
$tx_file = "/sys/class/net/$interface/statistics/tx_bytes";

if (!file_exists($rx_file) || !file_exists($tx_file)) {
    echo json_encode(['error' => 'Interface not found']);
    exit;
}

$cache_file = '/tmp/network_speed_cache.json';
$current_rx = (int)file_get_contents($rx_file);
$current_tx = (int)file_get_contents($tx_file);
$current_time = microtime(true);

$rx_speed = 0;
$tx_speed = 0;

if (file_exists($cache_file)) {
    $cache = json_decode(file_get_contents($cache_file), true);
    $time_diff = $current_time - $cache['time'];
    
    if ($time_diff > 0 && $time_diff < 5) {
        $rx_speed = ($current_rx - $cache['rx']) / $time_diff;
        $tx_speed = ($current_tx - $cache['tx']) / $time_diff;
    }
}

file_put_contents($cache_file, json_encode([
    'rx' => $current_rx,
    'tx' => $current_tx,
    'time' => $current_time
]));

function formatSpeed($bytes) {
    if ($bytes < 0) $bytes = 0;
    if ($bytes > 1024*1024*1024) {
        return round($bytes / (1024*1024*1024), 2) . ' GB/s';
    } elseif ($bytes > 1024*1024) {
        return round($bytes / (1024*1024), 2) . ' MB/s';
    } elseif ($bytes > 1024) {
        return round($bytes / 1024, 2) . ' KB/s';
    } else {
        return round($bytes, 0) . ' B/s';
    }
}

// 获取 vnstat 总流量
exec("vnstat --json 2>/dev/null", $vnstat_output);
$total_rx = 0;
$total_tx = 0;

if (!empty($vnstat_output)) {
    $vnstat = json_decode(implode('', $vnstat_output), true);
    if (isset($vnstat['interfaces'][0]['traffic']['total'])) {
        $total = $vnstat['interfaces'][0]['traffic']['total'];
        $total_rx = $total['rx'] ?? 0;
        $total_tx = $total['tx'] ?? 0;
    }
}

function formatBytes($bytes) {
    if ($bytes > 1024*1024*1024*1024) {
        return round($bytes / (1024*1024*1024*1024), 2) . ' TB';
    } elseif ($bytes > 1024*1024*1024) {
        return round($bytes / (1024*1024*1024), 2) . ' GB';
    } elseif ($bytes > 1024*1024) {
        return round($bytes / (1024*1024), 2) . ' MB';
    } else {
        return round($bytes / 1024, 2) . ' KB';
    }
}

echo json_encode([
    'upload' => formatSpeed($tx_speed),
    'download' => formatSpeed($rx_speed),
    'total_up' => formatBytes($total_tx),
    'total_down' => formatBytes($total_rx)
]);
?>
