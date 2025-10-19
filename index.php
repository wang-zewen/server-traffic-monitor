
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>多服务器流量监控</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { margin-bottom: 15px; color: #333; }
        
        .add-server { display: flex; gap: 10px; flex-wrap: wrap; }
        .add-server input { padding: 10px; border: 1px solid #ddd; border-radius: 4px; flex: 1; min-width: 200px; }
        .add-server button { padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .add-server button:hover { background: #1976D2; }
        
        .servers { display: grid; gap: 20px; }
        .server { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative; }
        .server.offline { opacity: 0.6; background: #f9f9f9; }
        .server.offline::after { content: '离线'; position: absolute; top: 20px; right: 20px; background: #f44336; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; }
        
        .server-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #2196F3; }
        .server-header h2 { color: #333; font-size: 20px; }
        .delete-btn { background: #f44336; color: white; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .delete-btn:hover { background: #d32f2f; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-box { background: #f9f9f9; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-box h3 { margin-bottom: 10px; color: #666; font-size: 14px; font-weight: normal; }
        .stat-box .value { font-size: 28px; font-weight: bold; color: #2196F3; }
        
        .status { position: absolute; top: 20px; right: 20px; width: 10px; height: 10px; border-radius: 50%; background: #4CAF50; }
        .server.offline .status { background: #f44336; }
        
        .badge { display: inline-block; background: #4CAF50; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin-left: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🖥️ 多服务器流量监控</h1>
        <div class="add-server">
            <input type="text" id="serverName" placeholder="服务器名称 (例如: 美国服务器)" />
            <input type="text" id="serverIp" placeholder="IP地址 (例如: 192.168.1.100)" />
            <input type="number" id="serverPort" placeholder="端口 (默认: 8080)" value="8080" />
            <button onclick="addServer()">➕ 添加服务器</button>
        </div>
    </div>
    
    <div class="servers" id="serversContainer"></div>

    <script>
        // 初始化：检查是否已有服务器列表，如果没有则添加本机
        function initServers() {
            let servers = JSON.parse(localStorage.getItem('servers') || '[]');
            
            // 如果是第一次访问（没有保存的服务器），自动添加本机
            if (servers.length === 0) {
                const hostname = window.location.hostname;
                const port = window.location.port || '8080';
                
                servers.push({
                    id: Date.now(),
                    name: '本机服务器',
                    ip: hostname,
                    port: port,
                    isLocal: true
                });
                
                localStorage.setItem('servers', JSON.stringify(servers));
            }
            
            return servers;
        }
        
        let servers = initServers();
        
        function addServer() {
            const name = document.getElementById('serverName').value.trim();
            const ip = document.getElementById('serverIp').value.trim();
            const port = document.getElementById('serverPort').value.trim() || '8080';
            
            if (!name || !ip) {
                alert('请填写服务器名称和IP地址');
                return;
            }
            
            const server = { 
                id: Date.now(), 
                name, 
                ip, 
                port,
                isLocal: false
            };
            
            servers.push(server);
            localStorage.setItem('servers', JSON.stringify(servers));
            
            document.getElementById('serverName').value = '';
            document.getElementById('serverIp').value = '';
            document.getElementById('serverPort').value = '8080';
            
            renderServers();
        }
        
        function deleteServer(id) {
            const server = servers.find(s => s.id === id);
            if (server && server.isLocal) {
                alert('不能删除本机服务器');
                return;
            }
            
            if (confirm('确定要删除这个服务器吗？')) {
                servers = servers.filter(s => s.id !== id);
                localStorage.setItem('servers', JSON.stringify(servers));
                renderServers();
            }
        }
        
        function renderServers() {
            const container = document.getElementById('serversContainer');
            container.innerHTML = '';
            
            servers.forEach(server => {
                const serverId = `server_${server.id}`;
                const div = document.createElement('div');
                div.className = 'server';
                div.id = serverId;
                
                const localBadge = server.isLocal ? '<span class="badge">本机</span>' : '';
                const deleteBtn = server.isLocal ? 
                    '<button class="delete-btn" style="opacity: 0.5; cursor: not-allowed;" disabled>本机</button>' :
                    `<button class="delete-btn" onclick="deleteServer(${server.id})">删除</button>`;
                
                div.innerHTML = `
                    <div class="status"></div>
                    <div class="server-header">
                        <h2>${server.name}${localBadge} <small style="color: #999; font-size: 14px;">(${server.ip}:${server.port})</small></h2>
                        ${deleteBtn}
                    </div>
                    <div class="stats">
                        <div class="stat-box">
                            <h3>⬆️ 上传速度</h3>
                            <div class="value" id="${serverId}_upload">-</div>
                        </div>
                        <div class="stat-box">
                            <h3>⬇️ 下载速度</h3>
                            <div class="value" id="${serverId}_download">-</div>
                        </div>
                        <div class="stat-box">
                            <h3>总上传</h3>
                            <div class="value" id="${serverId}_total_up">-</div>
                        </div>
                        <div class="stat-box">
                            <h3>总下载</h3>
                            <div class="value" id="${serverId}_total_down">-</div>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
            
            if (servers.length === 0) {
                container.innerHTML = '<div style="text-align: center; padding: 40px; color: #999;">暂无服务器，请添加服务器开始监控</div>';
            }
        }
        
        function updateServer(server) {
            const serverId = `server_${server.id}`;
            const url = `http://${server.ip}:${server.port}/speed.php`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.getElementById(`${serverId}_upload`).textContent = data.upload;
                    document.getElementById(`${serverId}_download`).textContent = data.download;
                    document.getElementById(`${serverId}_total_up`).textContent = data.total_up;
                    document.getElementById(`${serverId}_total_down`).textContent = data.total_down;
                    document.getElementById(serverId).classList.remove('offline');
                })
                .catch(error => {
                    document.getElementById(serverId).classList.add('offline');
                    console.error(`${server.name} 连接失败:`, error);
                });
        }
        
        function updateAll() {
            servers.forEach(server => updateServer(server));
        }
        
        // 初始化
        renderServers();
        updateAll();
        setInterval(updateAll, 2000); // 每2秒更新一次
    </script>
</body>
</html>
