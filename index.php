<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>多服务器流量监控</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { margin-bottom: 5px; color: #333; }
        .version { color: #999; font-size: 12px; margin-bottom: 15px; }
        
        .add-server { display: flex; gap: 10px; flex-wrap: wrap; }
        .add-server input { padding: 10px; border: 1px solid #ddd; border-radius: 4px; flex: 1; min-width: 200px; }
        .add-server button { padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .add-server button:hover { background: #1976D2; }
        
        .servers { display: grid; gap: 20px; }
        .server { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative; }
        .server.offline { opacity: 0.6; background: #f9f9f9; }
        .server.offline::after { content: '离线'; position: absolute; top: 20px; right: 80px; background: #f44336; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; z-index: 10; }
        
        .server-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #2196F3; }
        .server-header h2 { color: #333; font-size: 20px; }
        .server-actions { display: flex; gap: 10px; }
        .test-btn { background: #FF9800; color: white; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .test-btn:hover { background: #F57C00; }
        .test-btn:disabled { background: #ccc; cursor: not-allowed; }
        .delete-btn { background: #f44336; color: white; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .delete-btn:hover { background: #d32f2f; }
        
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .stat-box { background: #f9f9f9; padding: 15px; border-radius: 5px; text-align: center; }
        .stat-box h3 { margin-bottom: 10px; color: #666; font-size: 13px; font-weight: normal; }
        .stat-box .value { font-size: 22px; font-weight: bold; color: #2196F3; }
        .stat-box.speed-test .value { color: #FF9800; }
        
        /* 系统状态 - 3个圆环一行 */
        .stat-box.system-status .value { display: flex; justify-content: space-around; gap: 5px; margin-top: 5px; }
        .mini-ring { text-align: center; flex: 1; }
        .mini-progress { position: relative; width: 50px; height: 50px; margin: 0 auto; }
        .mini-progress svg { transform: rotate(-90deg); }
        .mini-progress circle { fill: none; stroke-width: 4; }
        .mini-progress .bg { stroke: #e0e0e0; }
        .mini-progress .progress { stroke: #4CAF50; stroke-linecap: round; transition: stroke-dashoffset 0.5s; }
        .mini-progress .progress.warning { stroke: #FF9800; }
        .mini-progress .progress.danger { stroke: #f44336; }
        .mini-progress .percent { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 10px; font-weight: bold; color: #333; }
        .mini-ring .label { font-size: 10px; color: #666; margin-top: 3px; }
        .mini-ring .total { font-size: 9px; color: #999; margin-top: 2px; font-weight: normal; }
        
        .status { position: absolute; top: 20px; right: 20px; width: 10px; height: 10px; border-radius: 50%; background: #4CAF50; }
        .server.offline .status { background: #f44336; }
        
        .badge { display: inline-block; background: #4CAF50; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin-left: 8px; }
        
        .testing { animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🖥️ 多服务器流量监控</h1>
        <div class="version">v1.2.1 | <a href="https://github.com/wang-zewen/server-traffic-monitor" target="_blank" style="color: #2196F3; text-decoration: none;">GitHub</a></div>
        
        <div class="add-server">
            <input type="text" id="serverName" placeholder="服务器名称 (例如: 美国服务器)" />
            <input type="text" id="serverIp" placeholder="IP地址 (例如: 192.168.1.100)" />
            <input type="number" id="serverPort" placeholder="端口 (默认: 8080)" value="8080" />
            <button onclick="addServer()">➕ 添加服务器</button>
        </div>
    </div>
    
    <div class="servers" id="serversContainer"></div>

    <script>
        const VERSION = '1.2.1';
        
        function initServers() {
            let servers = JSON.parse(localStorage.getItem('servers') || '[]');
            
            if (servers.length === 0) {
                const hostname = window.location.hostname;
                const port = window.location.port || '8080';
                
                servers.push({
                    id: Date.now(),
                    name: '本机服务器',
                    ip: hostname,
                    port: port,
                    isLocal: true,
                    downloadSpeed: '-'
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
                isLocal: false,
                downloadSpeed: '-'
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
        
        // 创建迷你圆环（添加total参数用于显示总量）
        function createMiniRing(percent, label, total = '') {
            const radius = 23;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (percent / 100) * circumference;
            
            let colorClass = '';
            if (percent > 90) colorClass = 'danger';
            else if (percent > 70) colorClass = 'warning';
            
            const totalHtml = total ? `<div class="total">${total}</div>` : '';
            
            return `
                <div class="mini-ring">
                    <div class="mini-progress">
                        <svg width="50" height="50">
                            <circle class="bg" cx="25" cy="25" r="${radius}"></circle>
                            <circle class="progress ${colorClass}" cx="25" cy="25" r="${radius}"
                                style="stroke-dasharray: ${circumference}; stroke-dashoffset: ${offset};"></circle>
                        </svg>
                        <div class="percent">${percent}%</div>
                    </div>
                    <div class="label">${label}</div>
                    ${totalHtml}
                </div>
            `;
        }
        
        // 测速功能
        async function testSpeed(serverId) {
            const server = servers.find(s => s.id === serverId);
            if (!server) return;
            
            const btn = document.getElementById(`test_btn_${serverId}`);
            const valueEl = document.getElementById(`server_${serverId}_speed`);
            
            btn.disabled = true;
            btn.textContent = '测速中...';
            valueEl.textContent = '测试中...';
            valueEl.parentElement.classList.add('testing');
            
            try {
                const testSize = 10;
                const url = `http://${server.ip}:${server.port}/speedtest.php?size=${testSize}`;
                
                const startTime = performance.now();
                const response = await fetch(url);
                
                if (!response.ok) throw new Error('测速失败');
                
                const reader = response.body.getReader();
                let receivedLength = 0;
                
                while(true) {
                    const {done, value} = await reader.read();
                    if (done) break;
                    receivedLength += value.length;
                }
                
                const endTime = performance.now();
                const duration = (endTime - startTime) / 1000;
                const speedMbps = (receivedLength * 8 / 1024 / 1024 / duration).toFixed(2);
                const speedMBps = (receivedLength / 1024 / 1024 / duration).toFixed(2);
                
                const speedText = speedMBps > 1 ? `${speedMBps} MB/s` : `${speedMbps} Mbps`;
                
                server.downloadSpeed = speedText;
                localStorage.setItem('servers', JSON.stringify(servers));
                
                valueEl.textContent = speedText;
                btn.textContent = '重新测速';
                
            } catch (error) {
                console.error('测速失败:', error);
                valueEl.textContent = '测速失败';
                btn.textContent = '重试';
            } finally {
                btn.disabled = false;
                valueEl.parentElement.classList.remove('testing');
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
                        <div class="server-actions">
                            <button class="test-btn" id="test_btn_${server.id}" onclick="testSpeed(${server.id})">测速</button>
                            ${deleteBtn}
                        </div>
                    </div>
                    
                    <div class="stats">
                        <div class="stat-box system-status">
                            // <h3>💻 系统状态</h3>
                            <div class="value" id="${serverId}_status">
                                ${createMiniRing(0, 'CPU', '')}
                                ${createMiniRing(0, '内存', '')}
                                ${createMiniRing(0, '硬盘', '')}
                            </div>
                        </div>
                        
                        <div class="stat-box">
                            <h3>⬆️ 上传速度</h3>
                            <div class="value" id="${serverId}_upload">-</div>
                        </div>
                        <div class="stat-box">
                            <h3>⬇️ 下载速度</h3>
                            <div class="value" id="${serverId}_download">-</div>
                        </div>
                        <div class="stat-box speed-test">
                            <h3>📊 下载测速</h3>
                            <div class="value" id="${serverId}_speed">${server.downloadSpeed || '-'}</div>
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
        
        function updateServerStatus(server) {
            const serverId = `server_${server.id}`;
            const url = `http://${server.ip}:${server.port}/status.php`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    const statusContainer = document.getElementById(`${serverId}_status`);
                    if (statusContainer) {
                        // CPU总核心数
                        const cpuTotal = data.cpu_cores ? `${data.cpu_cores}核` : '';
                        // 内存总大小
                        const memTotal = data.memory.total || '';
                        // 硬盘总大小
                        const diskTotal = data.disk.total || '';
                        
                        statusContainer.innerHTML = `
                            ${createMiniRing(data.cpu, 'CPU', cpuTotal)}
                            ${createMiniRing(data.memory.percent, '内存', memTotal)}
                            ${createMiniRing(data.disk.percent, '硬盘', diskTotal)}
                        `;
                    }
                })
                .catch(error => {
                    console.error(`${server.name} 状态获取失败:`, error);
                });
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
            
            // 同时更新系统状态
            updateServerStatus(server);
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
