
# 服务器流量监控系统

一个轻量级的多服务器流量监控系统，基于 vnstat + PHP + Nginx。

## 功能特性

- ✅ 实时显示服务器上传/下载速度
- ✅ 统计总流量使用情况
- ✅ 支持多服务器集中监控
- ✅ 自动检测在线状态
- ✅ 响应式界面设计
- ✅ 一键安装部署

## 快速安装

### 在每台需要监控的服务器上执行：
```bash
# 下载项目
git clone https://github.com/你的用户名/server-traffic-monitor.git
cd server-traffic-monitor

# 运行安装脚本
sudo bash install.sh
```

### 或者使用一键命令：
```bash
curl -sSL https://raw.githubusercontent.com/你的用户名/server-traffic-monitor/main/install.sh | sudo bash
```

## 使用方法

1. 安装完成后访问 `http://服务器IP:8080`
2. 在页面上添加其他服务器的信息
3. 系统会自动开始监控所有服务器

## 系统要求

- Ubuntu 20.04+ / Debian 10+
- Root 权限

## 端口说明

- 默认监听端口：8080
- 可在安装后修改 `/etc/nginx/sites-available/traffic` 配置

## 故障排查

### 无法访问页面

1. 检查 Nginx 状态：`systemctl status nginx`
2. 检查防火墙：`ufw status`
3. 开放端口：`ufw allow 8080`

### 无法获取其他服务器数据

1. 确保其他服务器已安装并运行此系统
2. 检查网络连通性
3. 确认安全组已开放 8080 端口

## 卸载
```bash
sudo systemctl stop nginx
sudo rm -rf /var/www/html/traffic
sudo rm /etc/nginx/sites-enabled/traffic
sudo rm /etc/nginx/sites-available/traffic
sudo systemctl start nginx
```

## 许可证

MIT License
