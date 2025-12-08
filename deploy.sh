#!/bin/bash

# ===================================
# 抖音弹幕打印后台 - 生产环境部署脚本
# 基于 Laravel Sail + Nginx + acme.sh
# ===================================

set -e

# 项目目录
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
echo_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
echo_error() { echo -e "${RED}[ERROR]${NC} $1"; }
echo_step() { echo -e "${BLUE}[STEP]${NC} $1"; }

# Docker Compose 命令
DC="docker compose -f docker-compose.prod.yml"

# 安装 Docker
install_docker() {
    echo_step "开始安装 Docker..."
    
    # 检查是否有 root 权限
    if [ "$EUID" -ne 0 ]; then
        echo_warn "安装 Docker 需要 root 权限，将使用 sudo"
        SUDO="sudo"
    else
        SUDO=""
    fi
    
    # 检查 curl 是否可用
    if ! command -v curl &> /dev/null; then
        echo_info "安装 curl..."
        $SUDO apt-get update && $SUDO apt-get install -y curl
    fi
    
    # 使用官方脚本安装 Docker
    echo_info "使用官方脚本安装 Docker..."
    curl -fsSL https://get.docker.com | $SUDO sh
    
    # 将当前用户加入 docker 组
    if [ -n "$SUDO_USER" ]; then
        CURRENT_USER="$SUDO_USER"
    else
        CURRENT_USER="$(whoami)"
    fi
    
    if [ "$CURRENT_USER" != "root" ]; then
        echo_info "将用户 $CURRENT_USER 加入 docker 组..."
        $SUDO usermod -aG docker "$CURRENT_USER"
        echo_warn "用户组变更需要重新登录才能生效"
        echo_warn "如果后续命令报权限错误，请重新登录或执行: newgrp docker"
    fi
    
    # 启动 Docker 服务
    echo_info "启动 Docker 服务..."
    $SUDO systemctl enable docker
    $SUDO systemctl start docker
    
    echo_info "Docker 安装完成！"
}

# 检查 Docker 是否安装
check_docker() {
    if ! command -v docker &> /dev/null; then
        echo_warn "Docker 未安装"
        read -p "是否自动安装 Docker? [Y/n]: " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Nn]$ ]]; then
            echo_error "请手动安装 Docker 后重试"
            exit 1
        fi
        install_docker
    fi
    
    if ! docker compose version &> /dev/null; then
        echo_warn "Docker Compose 未安装或版本过旧"
        echo_info "尝试重新安装 Docker（包含最新 Compose 插件）..."
        install_docker
    fi
    
    # 再次验证
    if ! command -v docker &> /dev/null || ! docker compose version &> /dev/null; then
        echo_error "Docker 安装失败，请手动安装"
        exit 1
    fi
    
    echo_info "Docker 环境检查通过"
    docker --version
    docker compose version
}

# 创建必要目录
create_directories() {
    echo_step "创建必要目录..."
    mkdir -p docker/nginx/conf.d
    mkdir -p docker/nginx/ssl
    mkdir -p docker/nginx/logs
    mkdir -p storage/logs
    mkdir -p storage/framework/{sessions,views,cache}
    mkdir -p bootstrap/cache
}

# 修复文件权限（解决 root 克隆仓库导致的权限问题）
# Laravel Sail 容器内用户是 sail (uid=1000, gid=1000)
fix_permissions() {
    echo_step "修复文件权限（适配 Sail 容器）..."
    
    # Sail 容器内用户的 UID/GID
    SAIL_UID=1000
    SAIL_GID=1000
    
    # 检查当前文件所有者
    CURRENT_OWNER=$(stat -c '%u' "$PROJECT_DIR" 2>/dev/null || echo "unknown")
    
    if [ "$CURRENT_OWNER" = "0" ]; then
        echo_warn "检测到项目目录由 root 所有，需要修复权限..."
    fi
    
    # 修复整个项目目录的所有者（排除 docker 数据卷目录）
    echo_info "设置项目文件所有者为 $SAIL_UID:$SAIL_GID..."
    chown -R $SAIL_UID:$SAIL_GID "$PROJECT_DIR" 2>/dev/null || {
        echo_warn "无法修改项目目录所有者，尝试使用宽松权限..."
        # 如果无法修改所有者，使用宽松权限作为备选方案
        chmod -R a+rw "$PROJECT_DIR" 2>/dev/null || true
    }
    
    # 确保关键目录有正确的权限
    echo_info "设置关键目录权限..."
    
    # Laravel 需要写入的目录
    WRITABLE_DIRS=(
        "storage"
        "storage/app"
        "storage/app/public"
        "storage/framework"
        "storage/framework/cache"
        "storage/framework/cache/data"
        "storage/framework/sessions"
        "storage/framework/views"
        "storage/logs"
        "bootstrap/cache"
    )
    
    for dir in "${WRITABLE_DIRS[@]}"; do
        if [ -d "$dir" ]; then
            chown -R $SAIL_UID:$SAIL_GID "$dir" 2>/dev/null || true
            chmod -R 775 "$dir" 2>/dev/null || chmod -R 777 "$dir" 2>/dev/null || true
        fi
    done
    
    # vendor 目录（Composer 需要）
    if [ -d "vendor" ]; then
        chown -R $SAIL_UID:$SAIL_GID vendor 2>/dev/null || true
    fi
    
    # .env 文件
    if [ -f ".env" ]; then
        chown $SAIL_UID:$SAIL_GID .env 2>/dev/null || chmod 666 .env 2>/dev/null || true
    fi
    
    # Docker 相关目录保持当前用户所有（避免权限问题）
    # nginx 日志目录需要 nginx 容器可写
    if [ -d "docker/nginx/logs" ]; then
        chmod -R 777 docker/nginx/logs 2>/dev/null || true
    fi
    
    echo_info "权限修复完成"
}

# 设置权限（简化版，在容器启动后调用）
set_permissions() {
    echo_step "设置目录权限..."
    # 在容器内执行权限修复
    $DC exec -T laravel.test chown -R sail:sail /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    $DC exec -T laravel.test chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
}

# 预安装 vendor（解决首次部署时 vendor 不存在的问题）
# Sail 镜像构建依赖 vendor/laravel/sail/runtimes/
pre_install_vendor() {
    if [ ! -d "vendor/laravel/sail" ]; then
        echo_step "首次部署：预安装 Composer 依赖..."
        echo_info "使用临时 PHP 容器安装 vendor..."
        
        # 使用官方 PHP 镜像安装 composer 依赖
        docker run --rm \
            -v "$(pwd)":/var/www/html \
            -w /var/www/html \
            composer:latest \
            composer install --no-dev --no-scripts --no-interaction --prefer-dist
        
        if [ $? -ne 0 ]; then
            echo_error "Composer 依赖安装失败"
            exit 1
        fi
        
        # 修复 vendor 目录权限
        chown -R 1000:1000 vendor 2>/dev/null || true
        
        echo_info "Composer 依赖预安装完成"
    fi
}

# 构建镜像
build_images() {
    echo_step "构建 Docker 镜像..."
    
    # 确保 vendor/laravel/sail 存在
    pre_install_vendor
    
    $DC build
}

# 启动服务
start_services() {
    echo_step "启动服务..."
    $DC up -d
}

# 停止服务
stop_services() {
    echo_step "停止服务..."
    $DC down
}

# 重启服务
restart_services() {
    echo_step "重启服务..."
    $DC restart
}

# 安装依赖
install_dependencies() {
    echo_step "安装 Composer 依赖..."
    $DC exec -T laravel.test composer install --no-dev --optimize-autoloader
}

# Laravel 初始化
laravel_init() {
    echo_step "Laravel 初始化..."
    
    # 生成 APP_KEY（如果没有）
    if grep -q "^APP_KEY=$" .env 2>/dev/null; then
        echo_info "生成 APP_KEY..."
        $DC exec -T laravel.test php artisan key:generate
    fi
    
    # 运行迁移
    echo_info "运行数据库迁移..."
    $DC exec -T laravel.test php artisan migrate --force
    
    # 清理并优化缓存
    echo_info "优化缓存..."
    $DC exec -T laravel.test php artisan config:cache
    $DC exec -T laravel.test php artisan route:cache
    $DC exec -T laravel.test php artisan view:cache
    
    # 创建存储链接
    $DC exec -T laravel.test php artisan storage:link 2>/dev/null || true
    
    echo_info "Laravel 初始化完成"
}

# 更新部署
update() {
    echo_step "开始更新部署..."
    
    # 拉取最新代码
    echo_info "拉取最新代码..."
    git pull origin main || git pull origin master
    
    # 修复权限（git pull 可能引入 root 所有的新文件）
    fix_permissions
    
    # 安装依赖
    install_dependencies
    
    # 运行迁移
    echo_info "运行数据库迁移..."
    $DC exec -T laravel.test php artisan migrate --force
    
    # 清理缓存
    echo_info "清理并重建缓存..."
    $DC exec -T laravel.test php artisan optimize:clear
    $DC exec -T laravel.test php artisan config:cache
    $DC exec -T laravel.test php artisan route:cache
    $DC exec -T laravel.test php artisan view:cache
    
    # 确保容器内权限正确
    set_permissions
    
    # 重启服务
    restart_services
    
    echo_info "更新完成！"
}

# 查看日志
logs() {
    SERVICE="${2:-}"
    if [ -n "$SERVICE" ]; then
        $DC logs -f "$SERVICE"
    else
        $DC logs -f
    fi
}

# 进入容器
shell() {
    SERVICE="${2:-laravel.test}"
    $DC exec "$SERVICE" sh
}

# 执行 artisan 命令
artisan() {
    shift  # 移除第一个参数 "artisan"
    $DC exec -T laravel.test php artisan "$@"
}

# 首次部署
deploy() {
    echo "======================================"
    echo "  抖音弹幕打印后台 - 生产环境部署"
    echo "======================================"
    echo ""
    
    check_docker
    create_directories
    
    # 检查 .env 文件
    if [ ! -f .env ]; then
        if [ -f .env.example ]; then
            echo_warn ".env 文件不存在，从 .env.example 创建..."
            cp .env.example .env
            echo_warn "请编辑 .env 文件配置正确的参数后重新运行"
            echo_warn "必须配置: APP_URL, DB_PASSWORD 等"
            exit 1
        else
            echo_error ".env 和 .env.example 都不存在！"
            exit 1
        fi
    fi
    
    # 检查 vendor 目录
    if [ ! -d "vendor" ]; then
        echo_warn "vendor 目录不存在，将在容器内安装依赖..."
    fi
    
    # ⚠️ 重要：在启动容器之前修复权限
    # 解决 root 用户克隆仓库导致的权限问题
    fix_permissions
    
    build_images
    start_services
    
    echo_info "等待数据库启动..."
    sleep 10
    
    install_dependencies
    laravel_init
    
    # 容器启动后再次确保权限正确
    set_permissions
    
    echo ""
    echo "======================================"
    echo_info "部署完成！"
    echo ""
    echo "下一步操作："
    echo "  1. 配置 SSL 证书: ./ssl-manager.sh issue"
    echo "  2. 启用 HTTPS:    ./ssl-manager.sh enable"
    echo "======================================"
}

# 查看状态
status() {
    echo_step "服务状态:"
    $DC ps
}

# 显示帮助
show_help() {
    echo "抖音弹幕打印后台 - 生产环境部署脚本"
    echo ""
    echo "用法: $0 <命令> [选项]"
    echo ""
    echo "部署命令:"
    echo "  deploy          首次部署（构建镜像、启动服务、初始化）"
    echo "  update          更新部署（拉取代码、迁移、重启）"
    echo ""
    echo "服务命令:"
    echo "  start           启动所有服务"
    echo "  stop            停止所有服务"
    echo "  restart         重启所有服务"
    echo "  status          查看服务状态"
    echo ""
    echo "维护命令:"
    echo "  fix-permissions 修复文件权限（解决 root 克隆导致的权限问题）"
    echo "  logs [service]  查看日志 (可选: laravel.test, nginx, mysql, redis)"
    echo "  shell [service] 进入容器 (默认: laravel.test)"
    echo "  artisan <cmd>   执行 artisan 命令"
    echo ""
    echo "SSL 证书:"
    echo "  运行 ./ssl-manager.sh help 查看 SSL 相关命令"
    echo ""
    echo "示例:"
    echo "  $0 deploy           # 首次部署"
    echo "  $0 fix-permissions  # 修复文件权限"
    echo "  $0 logs nginx       # 查看 nginx 日志"
    echo "  $0 artisan migrate  # 运行迁移"
    echo "  $0 shell mysql      # 进入 mysql 容器"
}

# 主入口
case "${1:-help}" in
    deploy)
        deploy
        ;;
    update)
        update
        ;;
    start)
        start_services
        ;;
    stop)
        stop_services
        ;;
    restart)
        restart_services
        ;;
    status)
        status
        ;;
    fix-permissions)
        fix_permissions
        # 如果容器正在运行，也在容器内修复
        if $DC ps --status running 2>/dev/null | grep -q laravel.test; then
            set_permissions
        fi
        ;;
    logs)
        logs "$@"
        ;;
    shell)
        shell "$@"
        ;;
    artisan)
        artisan "$@"
        ;;
    build)
        build_images
        ;;
    help|*)
        show_help
        ;;
esac

