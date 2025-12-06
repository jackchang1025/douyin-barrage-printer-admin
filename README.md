# 抖音弹幕打印系统 - 管理后台

基于 Laravel 12 + Filament 4 构建的后台管理系统，提供用户认证 API 和管理后台。

## 功能特性

### API 接口
- `POST /api/auth/login` - 邮箱密码登录
- `POST /api/auth/login-phone` - 手机号+密码登录
- `POST /api/auth/login-code` - 手机号+验证码登录
- `POST /api/auth/send-code` - 发送验证码
- `POST /api/auth/register` - 用户注册
- `POST /api/auth/logout` - 退出登录
- `GET /api/auth/me` - 获取当前用户信息
- `GET /api/subscription/check` - 检查订阅状态

### 管理后台 (Filament)
- 用户管理：查看、创建、编辑用户，延长订阅
- 订阅管理：管理用户订阅计划
- 验证码记录：查看和清理验证码

## 快速开始

### 环境要求
- PHP 8.2+
- Composer
- MySQL 5.7+ / PostgreSQL 10+ / SQLite
- Node.js 18+

### 安装步骤

1. **安装 PHP 依赖**

```bash
cd douyin-barrage-printer-admin
composer install
```

2. **安装 Sanctum（API 认证）**

```bash
composer require laravel/sanctum
```

3. **配置环境变量**

```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env` 文件，配置数据库连接：

```env
DB_CONNECTION=sqlite
# 或者使用 MySQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=douyin_barrage
# DB_USERNAME=root
# DB_PASSWORD=
```

4. **运行数据库迁移**

```bash
# 创建 SQLite 数据库文件（如果使用 SQLite）
touch database/database.sqlite

# 运行迁移
php artisan migrate
```

5. **创建初始数据**

```bash
php artisan db:seed
```

这会创建：
- 管理员账户：`admin@example.com` / `admin123`
- 测试用户：`+86 13900000000` / `123456`

6. **启动开发服务器**

```bash
php artisan serve
```

服务将在 `http://localhost:8000` 运行。

### 使用 Laravel Sail (Docker)

如果你更喜欢使用 Docker：

```bash
# 安装 Sail
composer require laravel/sail --dev
php artisan sail:install

# 启动容器
./vendor/bin/sail up -d

# 运行迁移和种子
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
```

## 访问后台

启动服务后，访问 `http://localhost:8000/admin` 进入管理后台。

使用管理员账户登录：
- 邮箱：`admin@example.com`
- 密码：`admin123`

## 前端配置

在 Electron 前端项目中，创建或编辑 `.env` 文件：

```env
VITE_API_BASE_URL=http://localhost:8000
VITE_API_PREFIX=/api
```

## API 使用示例

### 手机号密码登录

```bash
curl -X POST http://localhost:8000/api/auth/login-phone \
  -H "Content-Type: application/json" \
  -d '{"countryCode": "+86", "phone": "13900000000", "password": "123456"}'
```

响应：
```json
{
  "token": "1|xxxxx...",
  "user": {
    "id": 2,
    "name": "+86 13900000000",
    "country_code": "+86",
    "phone": "13900000000",
    "plan": "pro",
    "subscription_expiry": "2024-02-06T00:00:00.000000Z"
  }
}
```

### 发送验证码

```bash
curl -X POST http://localhost:8000/api/auth/send-code \
  -H "Content-Type: application/json" \
  -d '{"countryCode": "+86", "phone": "13800138000"}'
```

### 检查订阅状态

```bash
curl http://localhost:8000/api/subscription/check \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 订阅计划

| 计划 | 每日打印限制 | 过滤器 | 自定义模板 | API 访问 |
|------|------------|--------|----------|---------|
| 免费版 (free) | 10 | ❌ | ❌ | ❌ |
| 专业版 (pro) | 100 | ✅ | ✅ | ❌ |
| 企业版 (enterprise) | 无限制 | ✅ | ✅ | ✅ |

## 目录结构

```
douyin-barrage-printer-admin/
├── app/
│   ├── Filament/Admin/Resources/   # Filament 资源
│   │   ├── UserResource.php        # 用户管理
│   │   ├── SubscriptionResource.php # 订阅管理
│   │   └── VerificationCodeResource.php # 验证码管理
│   ├── Http/Controllers/Api/       # API 控制器
│   │   └── AuthController.php      # 认证控制器
│   └── Models/                     # 数据模型
│       ├── User.php
│       ├── Subscription.php
│       └── VerificationCode.php
├── config/
│   ├── cors.php                    # CORS 配置
│   └── ...
├── database/
│   ├── migrations/                 # 数据库迁移
│   └── seeders/                    # 数据填充
├── routes/
│   ├── api.php                     # API 路由
│   └── web.php                     # Web 路由
└── ...
```

## 开发提示

### 验证码调试

在开发环境下（`APP_DEBUG=true`），发送验证码接口会返回验证码值，方便测试。

### 清理过期验证码

在管理后台的验证码管理页面，可以一键清理过期和已使用的验证码。

### 扩展功能

如需添加短信发送功能，编辑 `app/Http/Controllers/Api/AuthController.php`，在 `sendCode` 方法中集成短信服务商 SDK。

## License

MIT License
