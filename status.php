
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 获取 CPU 使用率
function getCpuUsage() {
    $load = sys_getloadavg();
    $cpu_count = intval(shell_exec("grep -c processor /proc/cpuinfo"));
    $cpu_usage = ($load[0] / $cpu_count) * 100;
    return min(round($cpu_usage, 2), 100);
}

// 获取内存使用情况
function getMemoryUsage() {
    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    
    $total = $mem[1];
    $used = $mem[2];
    
    return [
        'used' => round($used / 1024, 2), // MB
        'total' => round($total / 1024, 2), // MB
        'percent' => round(($used / $total) * 100, 2)
    ];
}

// 获取 Swap 使用情况
function getSwapUsage() {
    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    
    if (count($free_arr) < 3) {
        return ['used' => 0, 'total' => 0, 'percent' => 0];
    }
    
    $swap = explode(" ", $free_arr[2]);
    $swap = array_filter($swap);
    $swap = array_merge($swap);
    
    $total = $swap[1];
    $used = $swap[2];
    
    if ($total == 0) {
        return ['used' => 0, 'total' => 0, 'percent' => 0];
    }
    
    return [
        'used' => round($used / 1024, 2), // MB
        'total' => round($total / 1024, 2), // MB
        'percent' => round(($used / $total) * 100, 2)
    ];
}

// 获取磁盘使用情况
function getDiskUsage() {
    $disk_total = disk_total_space('/');
    $disk_free = disk_free_space('/');
    $disk_used = $disk_total - $disk_free;
    
    return [
        'used' => round($disk_used / 1024 / 1024 / 1024, 2), // GB
        'total' => round($disk_total / 1024 / 1024 / 1024, 2), // GB
        'percent' => round(($disk_used / $disk_total) * 100, 2)
    ];
}

echo json_encode([
    'cpu' => getCpuUsage(),
    'memory' => getMemoryUsage(),
    'swap' => getSwapUsage(),
    'disk' => getDiskUsage()
]);
?>
