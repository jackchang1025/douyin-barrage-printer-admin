# Ubuntu 线上 Docker 部署指南

基于 Laravel Sail + Nginx + acme.sh 自动 SSL 证书

## 目录

1. [服务器要求](#服务器要求)
2. [快速开始](#快速开始)
3. [安装 Docker](#安装-docker)
4. [项目部署](#项目部署)
5. [SSL 证书配置 (acme.sh)](#ssl-证书配置)
6. [防火墙配置](#防火墙配置)
7. [常用运维命令](#常用运维命令)
8. [故障排查](#故障排查)

---

## 服务器要求

| 配置项 | 最低要求 | 推荐配置 | 说明 |
|--------|----------|----------|------|
| CPU | 1 核 | 2 核+ | 并发量大时需要更多 |
| 内存 | 2GB | 4GB+ | MySQL + Redis + PHP 占用约 1.5GB |
| 硬盘 | 20GB SSD | 40GB+ SSD | 日志和数据库会逐渐增长 |
| 带宽 | 1Mbps | 3Mbps+ | 根据用户量调整 |
| 系统 | Ubuntu 20.04 | Ubuntu 22.04 LTS | 稳定且长期支持 |

---

## 快速开始

```bash
# 1. 克隆项目
git clone https://your-repo/douyin-barrage-printer-admin.git
cd douyin-barrage-printer-admin

# 2. 配置环境变量
cp .env.example .env
vim .env  # 配置 APP_URL、DB_PASSWORD 等

# 3. 部署
chmod +x deploy.sh ssl-manager.sh
./deploy.sh deploy

# 4. 申请 SSL 证书（域名需已解析到服务器）
./ssl-manager.sh issue

# 5. 启用 HTTPS
./ssl-manager.sh enable
```

---

## 安装 Docker

### Ubuntu 一键安装

```bash
# 安装 Docker
curl -fsSL https://get.docker.com | sh

# 安装 Docker Compose 插件
sudo apt install -y docker-compose-plugin

# 将当前用户添加到 docker 组
sudo usermod -aG docker $USER
newgrp docker

# 验证
docker --version
docker compose version
```

### 配置镜像加速（国内服务器）

```bash
sudo mkdir -p /etc/docker
sudo tee /etc/docker/daemon.json <<-'EOF'
{
  "registry-mirrors": [
    "https://mirror.ccs.tencentyun.com",
    "https://hub-mirror.c.163.com"
  ]
}
EOF

sudo systemctl daemon-reload
sudo systemctl restart docker
```

---

## 项目部署

### 1. 准备工作

```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 安装必要工具
sudo apt install -y git curl wget vim

# 配置时区
sudo timedatectl set-timezone Asia/Shanghai
```

### 2. 克隆项目

```bash
cd /home  # 或你的部署目录
git clone https://your-repo/douyin-barrage-printer-admin.git
cd douyin-barrage-printer-admin
```

### 3. 配置环境变量

```bash
cp .env.example .env
vim .env
```

**必须配置的项目：**

```env
# 应用 URL（必须是你的域名，用于 SSL 证书申请）
APP_URL=https://admin.example.com

# 数据库配置
DB_DATABASE=douyin_admin
DB_USERNAME=sail
DB_PASSWORD=YourStrongPassword123!

# 可选：用于 acme.sh 注册的邮箱
ACME_EMAIL=admin@example.com
```

### 4. 执行部署

```bash
chmod +x deploy.sh ssl-manager.sh
./deploy.sh deploy
```

### 5. 查看服务状态

```bash
./deploy.sh status
```

---

## SSL 证书配置

使用 [acme.sh](https://github.com/acmesh-official/acme.sh) 自动申请和续期 Let's Encrypt 免费证书。

### 前提条件

- 域名已解析到服务器 IP
- 80 端口可访问
- `.env` 中 `APP_URL` 已配置正确的域名

### 方法一：Webroot 模式（推荐）

```bash
# 自动从 .env 读取域名并申请证书
./ssl-manager.sh issue

# 启用 HTTPS
./ssl-manager.sh enable
```

### 方法二：Standalone 模式

需要临时停止 nginx：

```bash
./ssl-manager.sh issue-standalone
./ssl-manager.sh enable
```

### 方法三：DNS 模式（支持通配符证书）

适用于需要 `*.example.com` 通配符证书的场景。

**阿里云 DNS：**

```bash
# 配置阿里云 AccessKey
export Ali_Key="your_access_key_id"
export Ali_Secret="your_access_key_secret"

# 申请证书（含通配符）
./ssl-manager.sh issue-dns dns_ali
./ssl-manager.sh enable
```

**腾讯云 DNS：**

```bash
export DP_Id="your_id"
export DP_Key="your_key"
./ssl-manager.sh issue-dns dns_dp
```

**Cloudflare：**

```bash
export CF_Key="your_api_key"
export CF_Email="your_email"
./ssl-manager.sh issue-dns dns_cf
```

更多 DNS 提供商：[acme.sh DNS API](https://github.com/acmesh-official/acme.sh/wiki/dnsapi)

### 自动续期

acme.sh 安装时会自动添加 cron 任务，证书到期前会自动续期。

```bash
# 确认自动续期任务
./ssl-manager.sh cron

# 手动续期
./ssl-manager.sh renew

# 查看证书信息
./ssl-manager.sh info
```

### 手动上传证书

如果使用云服务商购买的证书：

```bash
# 将证书放入指定目录
cp your_cert.pem docker/nginx/ssl/fullchain.pem
cp your_key.key docker/nginx/ssl/privkey.pem

# 启用 HTTPS
./ssl-manager.sh enable
```

---

## 防火墙配置

### UFW

```bash
sudo ufw allow ssh
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
sudo ufw status
```

### 云服务器安全组

在控制台开放端口：22, 80, 443

---

## 常用运维命令

### 服务管理

```bash
./deploy.sh start      # 启动服务
./deploy.sh stop       # 停止服务
./deploy.sh restart    # 重启服务
./deploy.sh status     # 查看状态
./deploy.sh logs       # 查看所有日志
./deploy.sh logs nginx # 查看 nginx 日志
```

### 代码更新

```bash
./deploy.sh update
```

### Laravel 命令

```bash
./deploy.sh artisan migrate              # 运行迁移
./deploy.sh artisan cache:clear          # 清理缓存
./deploy.sh artisan make:filament-user   # 创建管理员
```

### 进入容器

```bash
./deploy.sh shell              # 进入 Laravel 容器
./deploy.sh shell mysql        # 进入 MySQL 容器
./deploy.sh shell nginx        # 进入 Nginx 容器
```

### 数据库备份

```bash
# 备份
docker compose -f docker-compose.prod.yml exec -T mysql \
  mysqldump -u root -p"$DB_PASSWORD" douyin_admin > backup_$(date +%Y%m%d).sql

# 恢复
docker compose -f docker-compose.prod.yml exec -T mysql \
  mysql -u root -p"$DB_PASSWORD" douyin_admin < backup.sql
```

---

## 故障排查

### 容器状态

```bash
./deploy.sh status
docker compose -f docker-compose.prod.yml logs
```

### Nginx 配置测试

```bash
docker compose -f docker-compose.prod.yml exec nginx nginx -t
```

### SSL 证书问题

```bash
# 查看证书信息
./ssl-manager.sh info

# 强制重新申请
./ssl-manager.sh issue
```

### 权限问题

```bash
chmod -R 775 storage bootstrap/cache
```

### 502 Bad Gateway

```bash
# 检查 Laravel 应用是否运行
./deploy.sh logs laravel.test

# 重启服务
./deploy.sh restart
```

---

## 架构说明

```
                    ┌─────────────────┐
                    │    Internet     │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │  Nginx (80/443) │  ← SSL 终止
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │  Laravel Sail   │  ← PHP 应用
                    │   (Port 80)     │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
      ┌───────▼───────┐  ┌───▼───┐  ┌───────▼───────┐
      │    MySQL      │  │ Redis │  │   Storage     │
      │   (3306)      │  │(6379) │  │   (Volume)    │
      └───────────────┘  └───────┘  └───────────────┘
```

---

## 文件结构

```
├── deploy.sh                 # 部署脚本
├── ssl-manager.sh            # SSL 证书管理脚本
├── docker-compose.prod.yml   # 生产环境 Docker 配置
├── docker/
│   ├── nginx/
│   │   ├── conf.d/
│   │   │   ├── default.conf        # Nginx 主配置
│   │   │   ├── ssl.conf.template   # HTTPS 配置模板
│   │   │   └── ssl.conf            # HTTPS 配置（自动生成）
│   │   ├── ssl/                    # SSL 证书目录
│   │   └── logs/                   # Nginx 日志
│   └── php/
│       └── zzz-custom.ini          # PHP 自定义配置
└── .env                            # 环境变量
```

