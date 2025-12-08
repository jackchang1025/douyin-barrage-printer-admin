<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $siteName }} - {{ $siteSlogan }}</title>
    <meta name="description" content="{{ $siteDescription }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary: #8b5cf6;
            --accent: #a855f7;
            --dark: #0f0f23;
            --dark-light: #1a1a2e;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --success: #22c55e;
            --warning: #f59e0b;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--dark);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 20px 0;
            backdrop-filter: blur(20px);
            background: rgba(15, 15, 35, 0.8);
            border-bottom: 1px solid var(--card-border);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-secondary);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--text-primary);
        }

        .nav-links a.nav-download {
            color: var(--bg-dark);
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
        }

        .nav-links a.nav-download:hover {
            color: var(--bg-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 212, 255, 0.3);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            padding: 120px 0 80px;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .hero-gradient {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.15) 0%, transparent 50%);
            animation: rotate 60s linear infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .hero-pattern {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 50px;
            font-size: 14px;
            color: #a5b4fc;
            margin-bottom: 32px;
        }

        .hero-title {
            font-size: clamp(40px, 8vw, 72px);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            letter-spacing: -2px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 50%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 18px;
            line-height: 1.8;
            color: var(--text-secondary);
            margin-bottom: 40px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .install-notice {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            max-width: 520px;
            margin: 0 auto 32px;
            padding: 12px 16px;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 8px;
            text-align: left;
        }

        .notice-icon {
            flex-shrink: 0;
            color: #fbbf24;
            margin-top: 2px;
        }

        .notice-content {
            font-size: 13px;
            line-height: 1.5;
            color: var(--text-secondary);
        }

        .notice-title {
            color: #fbbf24;
            font-weight: 600;
        }

        .notice-text {
            color: var(--text-muted);
        }

        .version-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 60px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .version-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: rgba(99, 102, 241, 0.15);
            border-radius: 50px;
            color: #a5b4fc;
            font-size: 12px;
        }

        .version-size {
            color: var(--text-secondary);
        }

        .btn {
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 8px 32px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .hero-stats {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            padding: 32px 48px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            backdrop-filter: blur(20px);
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
        }

        .stat-divider {
            width: 1px;
            height: 40px;
            background: var(--card-border);
        }

        /* Section Common */
        .section {
            padding: 100px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-tag {
            display: inline-block;
            padding: 6px 16px;
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            font-size: 13px;
            font-weight: 600;
            border-radius: 50px;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-title {
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -1px;
        }

        .section-subtitle {
            font-size: 18px;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Features Section */
        .features {
            background: linear-gradient(180deg, var(--dark) 0%, rgba(15, 15, 35, 0.95) 100%);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }

        .feature-card {
            padding: 40px 32px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            transition: all 0.4s;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: rgba(99, 102, 241, 0.3);
            background: rgba(99, 102, 241, 0.05);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.15);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 16px;
            margin-bottom: 24px;
        }

        .feature-icon svg {
            width: 28px;
            height: 28px;
            color: white;
        }

        .feature-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .feature-description {
            font-size: 15px;
            line-height: 1.7;
            color: var(--text-secondary);
        }

        /* Pricing Section */
        .pricing {
            background:
                radial-gradient(circle at 0% 100%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 100% 0%, rgba(168, 85, 247, 0.08) 0%, transparent 50%),
                var(--dark);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .pricing-card {
            position: relative;
            padding: 40px 32px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            transition: all 0.4s;
        }

        .pricing-card:hover {
            transform: translateY(-8px);
        }

        .pricing-card.popular {
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
            border-color: rgba(99, 102, 241, 0.4);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.2);
        }

        .pricing-card.enterprise {
            background: linear-gradient(180deg, rgba(245, 158, 11, 0.08) 0%, rgba(251, 191, 36, 0.03) 100%);
            border-color: rgba(245, 158, 11, 0.3);
        }

        .popular-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            padding: 6px 20px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            font-size: 13px;
            font-weight: 600;
            border-radius: 50px;
            white-space: nowrap;
        }

        .plan-header {
            margin-bottom: 24px;
        }

        .plan-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .plan-description {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .plan-price {
            margin-bottom: 32px;
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .price-currency {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .price-amount {
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
        }

        .price-period {
            font-size: 16px;
            color: var(--text-muted);
        }

        .plan-features {
            list-style: none;
            margin-bottom: 32px;
        }

        .plan-features li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--card-border);
            font-size: 15px;
            color: var(--text-secondary);
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        .check-icon {
            color: var(--success);
            font-size: 18px;
            flex-shrink: 0;
        }

        .plan-btn {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .plan-btn.outline {
            background: transparent;
            border: 2px solid var(--card-border);
            color: var(--text-primary);
        }

        .plan-btn.outline:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Contact Section */
        .contact {
            background: linear-gradient(180deg, rgba(15, 15, 35, 0.95) 0%, var(--dark) 100%);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            max-width: 900px;
            margin: 0 auto;
        }

        .contact-card {
            padding: 40px 32px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s;
        }

        .contact-card:hover {
            transform: translateY(-8px);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .contact-icon {
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 20px;
            margin: 0 auto 20px;
        }

        .contact-icon.wechat {
            background: linear-gradient(135deg, #07c160 0%, #00a651 100%);
        }

        .contact-icon.qq {
            background: linear-gradient(135deg, #12b7f5 0%, #0099ff 100%);
        }

        .contact-icon.phone {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .contact-icon svg {
            width: 32px;
            height: 32px;
            color: white;
        }

        .contact-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .qrcode-wrapper {
            width: 180px;
            height: 180px;
            margin: 0 auto 16px;
            padding: 12px;
            background: white;
            border-radius: 16px;
            overflow: hidden;
        }

        .qrcode-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .phone-number {
            font-size: 24px;
            font-weight: 700;
            color: #a5b4fc;
            margin-bottom: 12px;
        }

        .contact-hint {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* Footer */
        .footer {
            padding: 40px 0;
            border-top: 1px solid var(--card-border);
            text-align: center;
        }

        .footer p {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 16px 0;
            }

            .nav-links {
                display: none;
            }

            .hero-stats {
                flex-direction: column;
                gap: 24px;
                padding: 24px;
            }

            .stat-divider {
                width: 60px;
                height: 1px;
            }

            .hero-actions {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
                margin-left: auto;
                margin-right: auto;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .install-notice {
                max-width: 100%;
                padding: 10px 14px;
            }

            .notice-content {
                font-size: 12px;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">{{ $siteName }}</div>
                <nav>
                    <ul class="nav-links">
                        <li><a href="#features">功能</a></li>
                        <li><a href="#pricing">价格</a></li>
                        <li><a href="{{ route('download.latest') }}" class="nav-download">下载</a></li>
                        <li><a href="#contact">联系我们</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg">
            <div class="hero-gradient"></div>
            <div class="hero-pattern"></div>
        </div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <span>🔥</span>
                    <span>{{ $siteSlogan }}</span>
                </div>
                <h1 class="hero-title">{{ $siteName }}</h1>
                <p class="hero-description">{{ $siteDescription }}</p>
                <div class="hero-actions">
                    <a href="{{ route('download.latest') }}" class="btn btn-primary">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        下载客户端 (Windows)
                    </a>
                    <a href="#features" class="btn btn-outline">了解更多</a>
                </div>
                <div class="install-notice">
                    <div class="notice-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="notice-content">
                        <span class="notice-title">安装提示：</span>
                        <span class="notice-text">首次安装时，部分杀毒软件（如360、电脑管家）可能会误报。请点击"信任"或将软件添加到白名单后继续安装。</span>
                    </div>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">10K+</span>
                        <span class="stat-label">活跃用户</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-number">99.9%</span>
                        <span class="stat-label">稳定运行</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">技术支持</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section features">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">核心功能</span>
                <h2 class="section-title">为什么选择我们？</h2>
                <p class="section-subtitle">专为抖音直播主播打造的弹幕打印工具</p>
            </div>
            <div class="features-grid">
                @foreach($features as $feature)
                <div class="feature-card">
                    <div class="feature-icon">
                        @if($feature['icon'] === 'monitor')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        @elseif($feature['icon'] === 'printer')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        @elseif($feature['icon'] === 'filter')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        @else
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        @endif
                    </div>
                    <h3 class="feature-title">{{ $feature['title'] }}</h3>
                    <p class="feature-description">{{ $feature['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="section pricing">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">价格方案</span>
                <h2 class="section-title">选择适合您的方案</h2>
                <p class="section-subtitle">灵活的定价，满足不同规模的需求</p>
            </div>
            <div class="pricing-grid">
                @foreach($plans as $plan)

                <div class="pricing-card {{ $plan->code === 'pro' ? 'popular' : ($plan->code === 'enterprise' ? 'enterprise' : '') }}">
                    @if($plan->code === 'pro')
                    <div class="popular-badge">最受欢迎</div>
                    @endif
                    <div class="plan-header">
                        <h3 class="plan-name">{{ $plan->name }}</h3>
                        <p class="plan-description">{{ $plan->description }}</p>
                    </div>
                    <div class="plan-price">
                        <span class="price-currency">¥</span>
                        <span class="price-amount">{{ $plan->price == 0 ? '0' : number_format($plan->price, 0) }}</span>
                        <span class="price-period">/{{ $plan->duration_text }}</span>
                    </div>
                    <ul class="plan-features">
                        @if($plan->features)
                        @foreach($plan->features as $feature)
                        <li>
                            <span class="check-icon">✓</span>
                            <span>{{ $feature }}</span>
                        </li>
                        @endforeach
                        @endif
                    </ul>
                    <button class="btn plan-btn {{ $plan->code === 'pro' ? 'btn-primary' : 'outline' }}">
                        {{ $plan->price == 0 ? '免费开始' : '立即订阅' }}
                    </button>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section contact">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">联系我们</span>
                <h2 class="section-title">需要帮助？</h2>
                <p class="section-subtitle">{{ $contactDescription }}</p>
            </div>
            <div class="contact-grid">
                @if($contactWechatQrcode)
                <div class="contact-card">
                    <div class="contact-icon wechat">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295a.326.326 0 0 0 .167-.054l1.903-1.114a.864.864 0 0 1 .717-.098 10.16 10.16 0 0 0 2.837.403c.276 0 .543-.027.811-.05-.857-2.578.157-4.972 1.932-6.446 1.703-1.415 3.882-1.98 5.853-1.838-.576-3.583-4.196-6.348-8.596-6.348zM5.785 5.991c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178A1.17 1.17 0 0 1 4.623 7.17c0-.651.52-1.18 1.162-1.18zm5.813 0c.642 0 1.162.529 1.162 1.18a1.17 1.17 0 0 1-1.162 1.178 1.17 1.17 0 0 1-1.162-1.178c0-.651.52-1.18 1.162-1.18zm5.34 2.867c-1.797-.052-3.746.512-5.28 1.786-1.72 1.428-2.687 3.72-1.78 6.22.942 2.453 3.666 4.229 6.884 4.229.826 0 1.622-.12 2.361-.336a.722.722 0 0 1 .598.082l1.584.926a.272.272 0 0 0 .14.047c.134 0 .24-.111.24-.247 0-.06-.023-.12-.038-.177l-.327-1.233a.49.49 0 0 1 .177-.554C23.102 18.405 24 16.785 24 14.987c0-3.176-2.922-5.983-7.062-6.129zm-2.745 2.254c.534 0 .967.44.967.982a.975.975 0 0 1-.967.983.975.975 0 0 1-.966-.983c0-.542.433-.982.966-.982zm4.841 0c.535 0 .967.44.967.982a.975.975 0 0 1-.967.983.975.975 0 0 1-.967-.983c0-.542.433-.982.967-.982z" />
                        </svg>
                    </div>
                    <h3 class="contact-title">微信客服</h3>
                    <div class="qrcode-wrapper">
                        <img src="{{ $contactWechatQrcode }}" alt="微信二维码">
                    </div>
                    <p class="contact-hint">扫码添加微信客服</p>
                </div>
                @endif

                @if($contactQqQrcode)
                <div class="contact-card">
                    <div class="contact-icon qq">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21.395 15.035a39.548 39.548 0 0 0-.803-2.264l-1.079-2.695c.001-.032.014-.396.014-.396 0-4.636-3.023-8.51-7.527-8.51s-7.527 3.874-7.527 8.51c0 0 .012.352.014.396l-1.079 2.695a39.548 39.548 0 0 0-.803 2.264c-.194.69-.387 1.29-.387 1.29-.439 1.768-.31 2.58.127 2.807.884.46 2.386-.628 3.665-2.17 0 0-.049.477-.049.628 0 .592.167 1.17.292 1.17.567 0 1.357-1.473 2.096-2.466.313.041.63.063.952.063.323 0 .64-.022.952-.063.739.993 1.53 2.466 2.096 2.466.125 0 .292-.578.292-1.17 0-.151-.049-.628-.049-.628 1.279 1.542 2.781 2.63 3.665 2.17.437-.227.566-1.039.127-2.807 0 0-.193-.6-.387-1.29z" />
                        </svg>
                    </div>
                    <h3 class="contact-title">QQ客服</h3>
                    <div class="qrcode-wrapper">
                        <img src="{{ $contactQqQrcode }}" alt="QQ二维码">
                    </div>
                    <p class="contact-hint">扫码添加QQ客服</p>
                </div>
                @endif

                @if($contactPhone)
                <div class="contact-card">
                    <div class="contact-icon phone">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <h3 class="contact-title">电话咨询</h3>
                    <p class="phone-number">{{ $contactPhone }}</p>
                    <p class="contact-hint">工作时间：9:00 - 18:00</p>
                </div>
                @endif

                @if(!$contactWechatQrcode && !$contactQqQrcode && !$contactPhone)
                <div class="contact-card">
                    <div class="contact-icon phone">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="contact-title">客服服务</h3>
                    <p class="contact-hint">联系方式配置中...</p>
                </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
        </div>
    </footer>
</body>

</html>