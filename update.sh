
#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  服务器流量监控系统 - 更新脚本${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}请使用 root 权限运行此脚本${NC}"
    exit 1
fi

GITHUB_USER="wang-zewen"
GITHUB_REPO="server-traffic-monitor"
RAW_URL="https://raw.githubusercontent.com/${GITHUB_USER}/${GITHUB_REPO}/main"
WEB_DIR="/var/www/html/traffic"

if [ ! -d "$WEB_DIR" ]; then
    echo -e "${RED}✗ 未检测到已安装的系统${NC}"
    exit 1
fi

echo -e "${YELLOW}正在备份当前文件...${NC}"
BACKUP_DIR="/tmp/traffic_backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p $BACKUP_DIR
cp -r $WEB_DIR/* $BACKUP_DIR/
echo -e "${GREEN}✓ 备份完成: $BACKUP_DIR${NC}"
echo ""

echo -e "${YELLOW}正在下载最新版本...${NC}"

for file in index.php speed.php speedtest.php status.php servers.php; do
    echo -e "  更新 $file..."
    wget -q -O $WEB_DIR/$file "$RAW_URL/$file"
    if [ $? -ne 0 ]; then
        echo -e "${RED}✗ 下载 $file 失败，正在恢复备份...${NC}"
        cp $BACKUP_DIR/$file $WEB_DIR/ 2>/dev/null
    fi
done

# 确保数据目录存在
mkdir -p $WEB_DIR/data
chown -R www-data:www-data $WEB_DIR
chmod -R 755 $WEB_DIR

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  ✓ 更新完成！${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "备份文件: ${YELLOW}$BACKUP_DIR${NC}"
echo ""
