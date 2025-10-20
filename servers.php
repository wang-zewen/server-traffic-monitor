
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$data_file = '/var/www/html/traffic/data/servers.json';
$data_dir = dirname($data_file);

// 确保数据目录存在
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}

// 初始化文件
if (!file_exists($data_file)) {
    file_put_contents($data_file, json_encode([]));
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // 读取服务器列表
        $servers = json_decode(file_get_contents($data_file), true);
        echo json_encode($servers ?: []);
        break;
        
    case 'POST':
        // 保存服务器列表
        $input = file_get_contents('php://input');
        $servers = json_decode($input, true);
        
        if ($servers !== null) {
            file_put_contents($data_file, json_encode($servers, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
        }
        break;
        
    case 'DELETE':
        // 删除服务器
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (isset($data['id'])) {
            $servers = json_decode(file_get_contents($data_file), true);
            $servers = array_filter($servers, function($s) use ($data) {
                return $s['id'] != $data['id'];
            });
            $servers = array_values($servers); // 重新索引
            
            file_put_contents($data_file, json_encode($servers, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>
