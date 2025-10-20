
#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  服务器流量监控系统 - 一键安装脚本${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}请使用 root 权限运行此脚本${NC}"
    exit 1
fi

GITHUB_USER="wang-zewen"
GITHUB_REPO="server-traffic-monitor"
RAW_URL="https://raw.githubusercontent.com/${GITHUB_USER}/${GITHUB_REPO}/main"

detect_php_version() {
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
        echo -e "${GREEN}✓ 检测到 PHP $PHP_VERSION${NC}"
        return 0
    fi
    return 1
}

echo -e "${YELLOW}[1/5] 更新软件包列表...${NC}"
apt update -qq

echo -e "${YELLOW}[2/5] 安装必要软件...${NC}"
apt install -y vnstat nginx php-fpm php-cli curl wget > /dev/null 2>&1

if detect_php_version; then
    PHP_SOCK=$(find /var/run/php -name "php*-fpm.sock" | head -n 1)
    if [ -z "$PHP_SOCK" ]; then
        echo -e "${RED}✗ 未找到 PHP-FPM socket${NC}"
        exit 1
    fi
    echo -e "${GREEN}✓ PHP-FPM socket: $PHP_SOCK${NC}"
else
    echo -e "${RED}✗ PHP 安装失败${NC}"
    exit 1
fi

echo -e "${YELLOW}[3/5] 启动并配置 vnstat...${NC}"
systemctl start vnstat
systemctl enable vnstat > /dev/null 2>&1
echo -e "${GREEN}✓ vnstat 已启动${NC}"

echo -e "${YELLOW}[4/5] 下载并部署 Web 文件...${NC}"
WEB_DIR="/var/www/html/traffic"
mkdir -p $WEB_DIR
mkdir -p $WEB_DIR/data

for file in index.php speed.php speedtest.php status.php servers.php; do
    echo -e "  下载 $file..."
    wget -q -O $WEB_DIR/$file "$RAW_URL/$file"
    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ 下载 $file 失败${NC}"
        exit 1
    fi
done

chown -R www-data:www-data $WEB_DIR
chmod -R 755 $WEB_DIR
echo -e "${GREEN}✓ Web 文件已部署到 $WEB_DIR${NC}"

echo -e "${YELLOW}[5/5] 配置 Nginx...${NC}"
cat > /etc/nginx/sites-available/traffic <<NGINX_EOF
server {
    listen 8080;
    server_name _;
    
    root $WEB_DIR;
    index index.php index.html;
    
    location / {
        try_files \$uri \$uri/ =404;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_SOCK;
    }
}
NGINX_EOF

ln -sf /etc/nginx/sites-available/traffic /etc/nginx/sites-enabled/traffic
rm -f /etc/nginx/sites-enabled/default

nginx -t > /dev/null 2>&1
if [ $? -eq 0 ]; then
    systemctl restart nginx
    systemctl restart php*-fpm
    echo -e "${GREEN}✓ Nginx 配置成功${NC}"
else
    echo -e "${RED}✗ Nginx 配置失败${NC}"
    nginx -t
    exit 1
fi

SERVER_IP=$(curl -s -4 ifconfig.me || hostname -I | awk '{print $1}')

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  ✓ 安装完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "访问地址: ${YELLOW}http://${SERVER_IP}:8080${NC}"
echo ""
echo -e "${YELLOW}功能说明:${NC}"
echo "✓ 实时监控 CPU、内存、硬盘使用率"
echo "✓ 实时显示网络上传/下载速度"
echo "✓ 统计总流量使用情况"
echo "✓ 测速功能测试服务器下载速度"
echo "✓ 服务器列表保存在服务器，随处访问"
echo ""
