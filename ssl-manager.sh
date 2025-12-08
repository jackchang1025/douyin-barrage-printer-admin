#!/bin/bash

# ===================================
# SSL 证书自动管理脚本 (使用 acme.sh)
# 从 .env 文件读取 APP_URL 自动配置域名
# ===================================

set -e

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

# 项目目录
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
SSL_DIR="${PROJECT_DIR}/docker/nginx/ssl"
NGINX_CONF_DIR="${PROJECT_DIR}/docker/nginx/conf.d"
ACME_HOME="${HOME}/.acme.sh"

# 从 .env 提取域名
get_domain_from_env() {
    if [ ! -f "${PROJECT_DIR}/.env" ]; then
        echo_error ".env 文件不存在！"
        exit 1
    fi
    
    # 提取 APP_URL 的值
    APP_URL=$(grep -E "^APP_URL=" "${PROJECT_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'")
    
    if [ -z "$APP_URL" ]; then
        echo_error ".env 中未找到 APP_URL 配置！"
        exit 1
    fi
    
    # 从 URL 提取域名 (移除 http:// 或 https:// 前缀)
    DOMAIN=$(echo "$APP_URL" | sed -E 's|https?://||' | sed 's|/.*||')
    
    if [ -z "$DOMAIN" ]; then
        echo_error "无法从 APP_URL 提取域名！"
        exit 1
    fi
    
    echo "$DOMAIN"
}

# 从 .env 提取邮箱（可选，用于 acme.sh 注册）
get_email_from_env() {
    if [ -f "${PROJECT_DIR}/.env" ]; then
        EMAIL=$(grep -E "^ACME_EMAIL=" "${PROJECT_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'" 2>/dev/null || echo "")
        if [ -z "$EMAIL" ]; then
            EMAIL=$(grep -E "^MAIL_FROM_ADDRESS=" "${PROJECT_DIR}/.env" | cut -d '=' -f2 | tr -d '"' | tr -d "'" 2>/dev/null || echo "")
        fi
    fi
    echo "${EMAIL:-admin@example.com}"
}

# 安装 acme.sh
install_acme() {
    echo_step "检查 acme.sh 安装状态..."
    
    if [ -f "${ACME_HOME}/acme.sh" ]; then
        echo_info "acme.sh 已安装"
        return 0
    fi
    
    echo_info "正在安装 acme.sh..."
    
    EMAIL=$(get_email_from_env)
    
    curl https://get.acme.sh | sh -s email="$EMAIL"
    
    # 重新加载环境
    source "${ACME_HOME}/acme.sh.env" 2>/dev/null || true
    
    echo_info "acme.sh 安装完成"
}

# 申请 SSL 证书
issue_cert() {
    DOMAIN=$(get_domain_from_env)
    echo_step "为域名 ${DOMAIN} 申请 SSL 证书..."
    
    # 确保 acme.sh 已安装
    install_acme
    
    # 创建 SSL 目录
    mkdir -p "$SSL_DIR"
    
    # 确保 nginx 正在运行且 80 端口可访问
    echo_info "确保服务正在运行..."
    docker compose -f "${PROJECT_DIR}/docker-compose.prod.yml" up -d nginx
    
    sleep 3
    
    # 使用 webroot 模式申请证书
    # acme.sh 会在 /var/www/html/public/.well-known/acme-challenge/ 创建验证文件
    echo_info "使用 webroot 模式申请证书..."
    
    "${ACME_HOME}/acme.sh" --issue \
        -d "$DOMAIN" \
        -w "${PROJECT_DIR}/public" \
        --server letsencrypt \
        --force
    
    # 安装证书到项目目录
    echo_info "安装证书到项目目录..."
    
    "${ACME_HOME}/acme.sh" --install-cert -d "$DOMAIN" \
        --key-file "${SSL_DIR}/privkey.pem" \
        --fullchain-file "${SSL_DIR}/fullchain.pem" \
        --reloadcmd "cd ${PROJECT_DIR} && docker compose -f docker-compose.prod.yml exec nginx nginx -s reload"
    
    echo_info "证书申请完成！"
    echo_info "证书位置: ${SSL_DIR}/"
}

# 使用 standalone 模式申请（80端口空闲时）
issue_cert_standalone() {
    DOMAIN=$(get_domain_from_env)
    echo_step "使用 standalone 模式为 ${DOMAIN} 申请证书..."
    
    install_acme
    mkdir -p "$SSL_DIR"
    
    # 停止 nginx 释放 80 端口
    echo_info "临时停止 nginx..."
    docker compose -f "${PROJECT_DIR}/docker-compose.prod.yml" stop nginx 2>/dev/null || true
    
    sleep 2
    
    # standalone 模式
    "${ACME_HOME}/acme.sh" --issue \
        -d "$DOMAIN" \
        --standalone \
        --server letsencrypt \
        --force
    
    # 安装证书
    "${ACME_HOME}/acme.sh" --install-cert -d "$DOMAIN" \
        --key-file "${SSL_DIR}/privkey.pem" \
        --fullchain-file "${SSL_DIR}/fullchain.pem" \
        --reloadcmd "cd ${PROJECT_DIR} && docker compose -f docker-compose.prod.yml exec nginx nginx -s reload"
    
    # 重启 nginx
    echo_info "重启 nginx..."
    docker compose -f "${PROJECT_DIR}/docker-compose.prod.yml" start nginx
    
    echo_info "证书申请完成！"
}

# 使用 DNS 模式申请（需要配置 DNS API）
issue_cert_dns() {
    DOMAIN=$(get_domain_from_env)
    DNS_PROVIDER="${1:-dns_ali}"  # 默认使用阿里云 DNS
    
    echo_step "使用 DNS 模式为 ${DOMAIN} 申请证书..."
    echo_info "DNS 提供商: ${DNS_PROVIDER}"
    
    install_acme
    mkdir -p "$SSL_DIR"
    
    # 提示配置 DNS API
    echo_warn "请确保已配置 DNS API 环境变量"
    echo_warn "阿里云: export Ali_Key='xxx' && export Ali_Secret='xxx'"
    echo_warn "腾讯云: export DP_Id='xxx' && export DP_Key='xxx'"
    echo_warn "Cloudflare: export CF_Key='xxx' && export CF_Email='xxx'"
    echo ""
    
    read -p "是否继续? (y/n): " confirm
    if [ "$confirm" != "y" ]; then
        echo_info "已取消"
        exit 0
    fi
    
    # DNS 模式申请证书
    "${ACME_HOME}/acme.sh" --issue \
        -d "$DOMAIN" \
        -d "*.${DOMAIN}" \
        --dns "$DNS_PROVIDER" \
        --server letsencrypt \
        --force
    
    # 安装证书
    "${ACME_HOME}/acme.sh" --install-cert -d "$DOMAIN" \
        --key-file "${SSL_DIR}/privkey.pem" \
        --fullchain-file "${SSL_DIR}/fullchain.pem" \
        --reloadcmd "cd ${PROJECT_DIR} && docker compose -f docker-compose.prod.yml exec nginx nginx -s reload"
    
    echo_info "证书申请完成（含通配符证书）！"
}

# 启用 HTTPS 配置
enable_https() {
    DOMAIN=$(get_domain_from_env)
    echo_step "启用 HTTPS 配置..."
    
    # 检查证书是否存在
    if [ ! -f "${SSL_DIR}/fullchain.pem" ] || [ ! -f "${SSL_DIR}/privkey.pem" ]; then
        echo_error "SSL 证书不存在！请先运行: $0 issue"
        exit 1
    fi
    
    # 从模板生成 SSL 配置
    if [ -f "${NGINX_CONF_DIR}/ssl.conf.template" ]; then
        sed "s/{{DOMAIN}}/${DOMAIN}/g" "${NGINX_CONF_DIR}/ssl.conf.template" > "${NGINX_CONF_DIR}/ssl.conf"
        echo_info "已生成 ssl.conf"
    fi
    
    # 更新 default.conf 启用 HTTPS 重定向
    if [ -f "${NGINX_CONF_DIR}/default.conf" ]; then
        # 备份原文件
        cp "${NGINX_CONF_DIR}/default.conf" "${NGINX_CONF_DIR}/default.conf.bak"
        
        # 启用 HTTPS 重定向
        sed -i 's|# return 301 https://\$host\$request_uri;|return 301 https://\$host\$request_uri;|' "${NGINX_CONF_DIR}/default.conf"
        sed -i 's|proxy_pass http://laravel.test:80;|# proxy_pass http://laravel.test:80;|' "${NGINX_CONF_DIR}/default.conf"
        sed -i 's|proxy_set_header Host \$host;$|# proxy_set_header Host \$host;|' "${NGINX_CONF_DIR}/default.conf"
        sed -i 's|proxy_set_header X-Real-IP|# proxy_set_header X-Real-IP|' "${NGINX_CONF_DIR}/default.conf"
        sed -i 's|proxy_set_header X-Forwarded-For|# proxy_set_header X-Forwarded-For|' "${NGINX_CONF_DIR}/default.conf"
        sed -i 's|proxy_set_header X-Forwarded-Proto \$scheme;|# proxy_set_header X-Forwarded-Proto \$scheme;|' "${NGINX_CONF_DIR}/default.conf"
        
        echo_info "已更新 default.conf"
    fi
    
    # 重载 nginx
    echo_info "重载 nginx 配置..."
    docker compose -f "${PROJECT_DIR}/docker-compose.prod.yml" exec nginx nginx -t
    docker compose -f "${PROJECT_DIR}/docker-compose.prod.yml" exec nginx nginx -s reload
    
    echo_info "HTTPS 已启用！"
    echo_info "访问: https://${DOMAIN}"
}

# 续期证书
renew_cert() {
    echo_step "续期所有证书..."
    
    if [ ! -f "${ACME_HOME}/acme.sh" ]; then
        echo_error "acme.sh 未安装！"
        exit 1
    fi
    
    "${ACME_HOME}/acme.sh" --renew-all --force
    
    echo_info "证书续期完成！"
}

# 设置自动续期定时任务
setup_cron() {
    echo_step "设置自动续期定时任务..."
    
    # acme.sh 安装时会自动添加 cron，这里只是确认
    if crontab -l 2>/dev/null | grep -q "acme.sh"; then
        echo_info "acme.sh 定时任务已存在"
        crontab -l | grep "acme.sh"
    else
        echo_warn "未找到 acme.sh 定时任务，正在添加..."
        # 添加定时任务（每天凌晨 3 点检查续期）
        (crontab -l 2>/dev/null; echo "0 3 * * * ${ACME_HOME}/acme.sh --cron --home ${ACME_HOME} > /dev/null 2>&1") | crontab -
        echo_info "定时任务已添加"
    fi
}

# 查看证书信息
show_cert_info() {
    DOMAIN=$(get_domain_from_env)
    echo_step "证书信息..."
    
    if [ -f "${SSL_DIR}/fullchain.pem" ]; then
        echo_info "证书文件: ${SSL_DIR}/fullchain.pem"
        echo ""
        openssl x509 -in "${SSL_DIR}/fullchain.pem" -noout -text | grep -A2 "Validity"
        echo ""
        openssl x509 -in "${SSL_DIR}/fullchain.pem" -noout -subject -issuer
    else
        echo_warn "证书文件不存在"
    fi
    
    if [ -f "${ACME_HOME}/acme.sh" ]; then
        echo ""
        echo_info "acme.sh 管理的证书列表:"
        "${ACME_HOME}/acme.sh" --list
    fi
}

# 显示帮助
show_help() {
    DOMAIN=$(get_domain_from_env 2>/dev/null || echo "未配置")
    
    echo "SSL 证书自动管理脚本 (acme.sh)"
    echo ""
    echo "当前域名: ${DOMAIN}"
    echo ""
    echo "用法: $0 <命令> [选项]"
    echo ""
    echo "命令:"
    echo "  install       安装 acme.sh"
    echo "  issue         申请证书 (webroot 模式，推荐)"
    echo "  issue-standalone  申请证书 (standalone 模式，需停止 nginx)"
    echo "  issue-dns [provider]  申请证书 (DNS 模式，支持通配符)"
    echo "                 可用 provider: dns_ali, dns_dp, dns_cf 等"
    echo "  enable        启用 HTTPS 配置"
    echo "  renew         手动续期证书"
    echo "  cron          设置自动续期定时任务"
    echo "  info          查看证书信息"
    echo "  help          显示帮助"
    echo ""
    echo "快速开始:"
    echo "  1. 确保 .env 中配置了正确的 APP_URL"
    echo "  2. 运行: $0 issue"
    echo "  3. 运行: $0 enable"
    echo ""
    echo "DNS 模式示例 (阿里云):"
    echo "  export Ali_Key='your_key'"
    echo "  export Ali_Secret='your_secret'"
    echo "  $0 issue-dns dns_ali"
    echo ""
    echo "更多 DNS 提供商: https://github.com/acmesh-official/acme.sh/wiki/dnsapi"
}

# 主入口
case "${1:-help}" in
    install)
        install_acme
        ;;
    issue)
        issue_cert
        ;;
    issue-standalone)
        issue_cert_standalone
        ;;
    issue-dns)
        issue_cert_dns "${2:-dns_ali}"
        ;;
    enable)
        enable_https
        ;;
    renew)
        renew_cert
        ;;
    cron)
        setup_cron
        ;;
    info)
        show_cert_info
        ;;
    help|*)
        show_help
        ;;
esac

