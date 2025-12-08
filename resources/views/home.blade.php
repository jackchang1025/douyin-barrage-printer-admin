<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Primary Meta Tags -->
    <title>{{ $siteName }} - {{ $siteSlogan }}</title>
    <meta name="title" content="{{ $siteName }} - {{ $siteSlogan }}">
    <meta name="description" content="{{ $siteDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="author" content="{{ $seoAuthor }}">
    <meta name="robots" content="index, follow">
    <meta name="language" content="Chinese">
    <meta name="revisit-after" content="7 days">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url('/') }}">

    <!-- Favicon -->
    @if($siteFavicon)
    <link rel="icon" type="image/x-icon" href="{{ $siteFavicon }}">
    <link rel="shortcut icon" href="{{ $siteFavicon }}">
    @endif
    <link rel="apple-touch-icon" href="{{ $siteFavicon ?? asset('favicon.png') }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="{{ $siteName }} - {{ $siteSlogan }}">
    <meta property="og:description" content="{{ $siteDescription }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    @if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @endif
    <meta property="og:locale" content="zh_CN">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url('/') }}">
    <meta property="twitter:title" content="{{ $siteName }} - {{ $siteSlogan }}">
    <meta property="twitter:description" content="{{ $siteDescription }}">
    @if($ogImage)
    <meta property="twitter:image" content="{{ $ogImage }}">
    @endif

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "SoftwareApplication",
            "name": "{{ $siteName }}",
            "description": "{{ $siteDescription }}",
            "url": "{{ url('/') }}",
            "applicationCategory": "BusinessApplication",
            "operatingSystem": "Windows",
            "offers": {
                "@@type": "AggregateOffer",
                "priceCurrency": "CNY",
                "lowPrice": "0",
                "offerCount": "{{ count($plans) }}"
            },
            "author": {
                "@@type": "Organization",
                "name": "{{ $seoAuthor }}"
            }
        }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|noto-sans-sc:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        :root {
            /* Cyberpunk-inspired color palette */
            --primary: #00d4ff;
            --primary-dark: #00a8cc;
            --secondary: #ff00aa;
            --accent: #ffaa00;
            --neon-green: #00ff88;
            --neon-purple: #8b5cf6;

            /* Dark theme */
            --bg-dark: #0a0a0f;
            --bg-card: rgba(20, 20, 30, 0.6);
            --bg-glass: rgba(255, 255, 255, 0.03);
            --border-glass: rgba(255, 255, 255, 0.08);

            /* Text colors */
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.75);
            --text-muted: rgba(255, 255, 255, 0.5);

            /* Gradients */
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--neon-purple) 100%);
            --gradient-secondary: linear-gradient(135deg, var(--secondary) 0%, var(--accent) 100%);
            --gradient-glow: linear-gradient(135deg, rgba(0, 212, 255, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            font-size: 16px;
        }

        body {
            font-family: 'Noto Sans SC', 'Space Grotesk', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a {
            color: inherit;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ==================== HEADER ==================== */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 16px 0;
            background: rgba(10, 10, 15, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid var(--border-glass);
            transition: all 0.3s ease;
        }

        .header.scrolled {
            padding: 12px 0;
            background: rgba(10, 10, 15, 0.95);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            overflow: hidden;
        }

        .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logo-text {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 24px;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            gap: 8px;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--text-primary);
            background: var(--bg-glass);
        }

        .nav-links .nav-download {
            background: var(--gradient-primary);
            color: var(--bg-dark);
            font-weight: 600;
            box-shadow: 0 4px 20px rgba(0, 212, 255, 0.3);
        }

        .nav-links .nav-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 212, 255, 0.4);
        }

        /* ==================== HERO SECTION ==================== */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            padding: 140px 0 100px;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        /* Animated gradient orbs */
        .hero-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.5;
            animation: float 20s ease-in-out infinite;
        }

        .hero-orb-1 {
            width: 600px;
            height: 600px;
            background: var(--primary);
            top: -200px;
            left: -200px;
            animation-delay: 0s;
        }

        .hero-orb-2 {
            width: 500px;
            height: 500px;
            background: var(--neon-purple);
            bottom: -150px;
            right: -100px;
            animation-delay: -7s;
        }

        .hero-orb-3 {
            width: 400px;
            height: 400px;
            background: var(--secondary);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: -14s;
            opacity: 0.3;
        }

        @@keyframes float {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            25% {
                transform: translate(30px, -30px) scale(1.05);
            }

            50% {
                transform: translate(-20px, 20px) scale(0.95);
            }

            75% {
                transform: translate(-30px, -20px) scale(1.02);
            }
        }

        /* Grid pattern */
        .hero-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 212, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 212, 255, 0.03) 1px, transparent 1px);
            background-size: 80px 80px;
            mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
        }

        .hero-content {
            position: relative;
            z-index: 10;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }

        .hero-text {
            max-width: 600px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            border-radius: 50px;
            font-size: 13px;
            color: var(--primary);
            margin-bottom: 24px;
            animation: fadeInUp 0.6s ease forwards;
        }

        .hero-badge-dot {
            width: 8px;
            height: 8px;
            background: var(--neon-green);
            border-radius: 50%;
            animation: pulse 2s ease infinite;
        }

        @@keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.5;
                transform: scale(1.2);
            }
        }

        .hero-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(40px, 6vw, 64px);
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 20px;
            letter-spacing: -2px;
            animation: fadeInUp 0.6s ease 0.1s forwards;
            opacity: 0;
        }

        .hero-title-gradient {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 18px;
            line-height: 1.8;
            color: var(--text-secondary);
            margin-bottom: 32px;
            animation: fadeInUp 0.6s ease 0.2s forwards;
            opacity: 0;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            animation: fadeInUp 0.6s ease 0.3s forwards;
            opacity: 0;
        }

        @@keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 28px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--bg-dark);
            box-shadow: 0 8px 32px rgba(0, 212, 255, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 48px rgba(0, 212, 255, 0.35);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--border-glass);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: var(--bg-glass);
            border-color: var(--primary);
            color: var(--primary);
        }

        /* Hero Media Showcase */
        .hero-media {
            position: relative;
            animation: fadeInUp 0.8s ease 0.4s forwards;
            opacity: 0;
        }

        .media-showcase {
            position: relative;
            border-radius: 24px;
            overflow: hidden;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            box-shadow:
                0 32px 64px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.05) inset;
        }

        .media-showcase::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 212, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }

        .media-slider {
            position: relative;
            aspect-ratio: 16/10;
        }

        .media-item {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .media-item.active {
            opacity: 1;
        }

        .media-item img,
        .media-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .media-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-dark) 100%);
        }

        .media-placeholder svg {
            width: 80px;
            height: 80px;
            color: var(--primary);
            opacity: 0.3;
        }

        /* Media Navigation Dots */
        .media-dots {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
        }

        .media-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .media-dot.active {
            background: var(--primary);
            width: 24px;
            border-radius: 4px;
        }

        /* Floating Elements */
        .hero-float {
            position: absolute;
            padding: 12px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            animation: floatBadge 3s ease-in-out infinite;
        }

        .hero-float-1 {
            top: 20%;
            right: -20px;
            animation-delay: 0s;
        }

        .hero-float-2 {
            bottom: 15%;
            left: -30px;
            animation-delay: -1.5s;
        }

        @@keyframes floatBadge {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .float-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .float-icon.success {
            background: rgba(0, 255, 136, 0.15);
            color: var(--neon-green);
        }

        .float-icon.info {
            background: rgba(0, 212, 255, 0.15);
            color: var(--primary);
        }

        /* Install Notice */
        .install-notice {
            margin-top: 24px;
            padding: 14px 18px;
            background: rgba(255, 170, 0, 0.08);
            border: 1px solid rgba(255, 170, 0, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13px;
            animation: fadeInUp 0.6s ease 0.5s forwards;
            opacity: 0;
        }

        .notice-icon {
            color: var(--accent);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .notice-content {
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .notice-title {
            color: var(--accent);
            font-weight: 600;
        }

        /* Hero Center Layout */
        .hero-content-center {
            display: flex;
            justify-content: center;
        }

        .hero-text-center {
            text-align: center;
            max-width: 800px;
        }

        .hero-text-center .hero-actions {
            justify-content: center;
        }

        .hero-text-center .install-notice {
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Hero Stats */
        .hero-stats {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 40px;
            padding: 32px 48px;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            margin-top: 40px;
            animation: fadeInUp 0.6s ease 0.6s forwards;
            opacity: 0;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .stat-number {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 700;
            background: var(--gradient-primary);
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
            background: var(--border-glass);
        }

        /* ==================== SHOWCASE SECTION ==================== */
        .showcase {
            background: linear-gradient(180deg, var(--bg-dark) 0%, rgba(15, 15, 25, 1) 50%, var(--bg-dark) 100%);
            padding: 100px 0 140px;
        }

        .showcase .container {
            max-width: 100%;
            padding: 0 40px;
        }

        .showcase-main {
            position: relative;
            max-width: 1800px;
            margin: 0 auto;
        }

        .showcase-viewer {
            position: relative;
            width: 100%;
            min-height: 700px;
            aspect-ratio: 16/10;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 28px;
            overflow: hidden;
            box-shadow:
                0 60px 120px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.05) inset,
                0 0 150px rgba(0, 212, 255, 0.12);
        }

        .showcase-item {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s ease;
            padding: 20px;
        }

        .showcase-item.active {
            opacity: 1;
            visibility: visible;
        }

        .showcase-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            cursor: zoom-in;
            transition: transform 0.3s ease;
            border-radius: 12px;
        }

        .showcase-image:hover {
            transform: scale(1.01);
        }

        .showcase-gif {
            border-radius: 12px;
        }

        .showcase-video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
            border-radius: 12px;
        }

        /* Showcase Navigation */
        .showcase-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 64px;
            height: 64px;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-primary);
            transition: all 0.3s ease;
            z-index: 10;
            backdrop-filter: blur(10px);
        }

        .showcase-nav svg {
            width: 28px;
            height: 28px;
        }

        .showcase-nav:hover {
            background: var(--primary);
            color: var(--bg-dark);
            border-color: var(--primary);
            transform: translateY(-50%) scale(1.1);
        }

        .showcase-prev {
            left: -32px;
        }

        .showcase-next {
            right: -32px;
        }

        /* Showcase Thumbnails */
        .showcase-thumbnails {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .showcase-thumb {
            position: relative;
            width: 160px;
            height: 100px;
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
            opacity: 0.6;
        }

        .showcase-thumb:hover {
            opacity: 1;
            transform: translateY(-6px);
        }

        .showcase-thumb.active {
            border-color: var(--primary);
            opacity: 1;
            box-shadow: 0 0 30px rgba(0, 212, 255, 0.4);
        }

        .showcase-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .thumb-video-icon {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
            color: white;
        }

        /* Showcase Counter */
        .showcase-counter {
            text-align: center;
            margin-top: 24px;
            font-size: 16px;
            color: var(--text-muted);
        }

        .showcase-counter span:first-child {
            color: var(--primary);
            font-weight: 600;
            font-size: 18px;
        }

        /* Lightbox for full-size viewing */
        .lightbox {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .lightbox.active {
            display: flex;
        }

        .lightbox-content {
            max-width: 95vw;
            max-height: 95vh;
            object-fit: contain;
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 48px;
            height: 48px;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .lightbox-close:hover {
            background: var(--primary);
            color: var(--bg-dark);
        }

        /* ==================== FEATURES SECTION ==================== */
        .section {
            padding: 120px 0;
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 64px;
        }

        .section-tag {
            display: inline-block;
            padding: 8px 18px;
            background: var(--gradient-glow);
            border: 1px solid var(--border-glass);
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .section-title {
            font-family: 'Space Grotesk', sans-serif;
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

        .features {
            background: linear-gradient(180deg, var(--bg-dark) 0%, rgba(20, 20, 30, 1) 50%, var(--bg-dark) 100%);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        .feature-card {
            position: relative;
            padding: 40px 28px;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--gradient-glow);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: rgba(0, 212, 255, 0.3);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.3);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            position: relative;
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-primary);
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 8px 24px rgba(0, 212, 255, 0.2);
        }

        .feature-icon svg {
            width: 28px;
            height: 28px;
            color: var(--bg-dark);
        }

        .feature-title {
            position: relative;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .feature-description {
            position: relative;
            font-size: 14px;
            line-height: 1.7;
            color: var(--text-secondary);
        }

        /* ==================== PRICING SECTION ==================== */
        .pricing {
            background:
                radial-gradient(ellipse at 0% 100%, rgba(0, 212, 255, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 100% 0%, rgba(139, 92, 246, 0.08) 0%, transparent 50%),
                var(--bg-dark);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .pricing-card {
            position: relative;
            padding: 40px 32px;
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            transition: all 0.4s ease;
            overflow: hidden;
        }

        .pricing-card:hover {
            transform: translateY(-8px);
        }

        .pricing-card.popular {
            background: linear-gradient(180deg, rgba(0, 212, 255, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
            border-color: rgba(0, 212, 255, 0.3);
            box-shadow: 0 24px 64px rgba(0, 212, 255, 0.15);
        }

        .popular-badge {
            position: absolute;
            top: -1px;
            left: 50%;
            transform: translateX(-50%);
            padding: 8px 24px;
            background: var(--gradient-primary);
            color: var(--bg-dark);
            font-size: 12px;
            font-weight: 700;
            border-radius: 0 0 12px 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .plan-header {
            margin-bottom: 24px;
        }

        .plan-name {
            font-family: 'Space Grotesk', sans-serif;
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
            font-family: 'Space Grotesk', sans-serif;
            font-size: 56px;
            font-weight: 700;
            line-height: 1;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            padding: 14px 0;
            border-bottom: 1px solid var(--border-glass);
            font-size: 14px;
            color: var(--text-secondary);
        }

        .plan-features li:last-child {
            border-bottom: none;
        }

        .check-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(0, 255, 136, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--neon-green);
            font-size: 12px;
            flex-shrink: 0;
        }

        .plan-btn {
            width: 100%;
            justify-content: center;
        }

        /* ==================== CONTACT SECTION ==================== */
        .contact {
            background: linear-gradient(180deg, rgba(20, 20, 30, 1) 0%, var(--bg-dark) 100%);
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
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            text-align: center;
            transition: all 0.4s ease;
        }

        .contact-card:hover {
            transform: translateY(-8px);
            border-color: rgba(0, 212, 255, 0.3);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
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
            background: var(--gradient-primary);
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
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 12px;
        }

        .contact-hint {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* ==================== FOOTER ==================== */
        .footer {
            padding: 48px 0;
            border-top: 1px solid var(--border-glass);
            text-align: center;
        }

        .footer-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

        .footer-links {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .footer-links a {
            font-size: 14px;
            color: var(--text-muted);
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .footer-copyright {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* ==================== RESPONSIVE ==================== */
        @@media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
                gap: 48px;
            }

            .hero-text {
                text-align: center;
                max-width: 100%;
            }

            .hero-actions {
                justify-content: center;
            }

            .hero-stats {
                gap: 24px;
                padding: 24px 32px;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .showcase .container {
                padding: 0 24px;
            }

            .showcase-viewer {
                min-height: 500px;
            }

            .showcase-nav {
                width: 50px;
                height: 50px;
            }

            .showcase-nav svg {
                width: 24px;
                height: 24px;
            }

            .showcase-prev {
                left: 12px;
            }

            .showcase-next {
                right: 12px;
            }

            .showcase-thumb {
                width: 130px;
                height: 82px;
            }
        }

        @@media (max-width: 768px) {
            .header {
                padding: 12px 0;
            }

            .nav-links {
                display: none;
            }

            .hero {
                padding: 120px 0 60px;
            }

            .section {
                padding: 80px 0;
            }

            .hero-stats {
                flex-direction: column;
                gap: 20px;
                padding: 24px;
            }

            .stat-divider {
                width: 60px;
                height: 1px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .hero-actions {
                flex-direction: column;
            }

            .showcase .container {
                padding: 0 16px;
            }

            .showcase-viewer {
                border-radius: 16px;
                min-height: 300px;
                aspect-ratio: 16/11;
            }

            .showcase-item {
                padding: 12px;
            }

            .showcase-nav {
                width: 44px;
                height: 44px;
            }

            .showcase-nav svg {
                width: 20px;
                height: 20px;
            }

            .showcase-thumbnails {
                gap: 10px;
                margin-top: 24px;
            }

            .showcase-thumb {
                width: 90px;
                height: 60px;
                border-radius: 10px;
                border-width: 2px;
            }
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            cursor: pointer;
            padding: 8px;
        }

        @@media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <div class="header-content">
                <a href="{{ url('/') }}" class="logo">
                    @if($siteLogo)
                    <div class="logo-icon">
                        <img src="{{ $siteLogo }}" alt="{{ $siteName }}">
                    </div>
                    @endif
                    <span class="logo-text">{{ $siteName }}</span>
                </a>
                <nav>
                    <ul class="nav-links">
                        <li><a href="#showcase">演示</a></li>
                        <li><a href="#features">功能</a></li>
                        <li><a href="#pricing">价格</a></li>
                        <li><a href="#contact">联系我们</a></li>
                        <li><a href="{{ route('download.latest') }}" class="nav-download">立即下载</a></li>
                    </ul>
                </nav>
                <button class="mobile-menu-btn" aria-label="菜单">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg">
            <div class="hero-orb hero-orb-1"></div>
            <div class="hero-orb hero-orb-2"></div>
            <div class="hero-orb hero-orb-3"></div>
            <div class="hero-grid"></div>
        </div>
        <div class="container">
            <div class="hero-content hero-content-center">
                <div class="hero-text hero-text-center">
                    <div class="hero-badge">
                        <span class="hero-badge-dot"></span>
                        <span>{{ $siteSlogan }}</span>
                    </div>
                    <h1 class="hero-title">
                        <span class="hero-title-gradient">{{ $siteName }}</span>
                    </h1>
                    <p class="hero-description">{{ $siteDescription }}</p>
                    <div class="hero-actions">
                        <a href="{{ route('download.latest') }}" class="btn btn-primary">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            下载客户端
                        </a>
                        <a href="#showcase" class="btn btn-outline">查看演示</a>
                        <a href="#features" class="btn btn-outline">了解更多</a>
                    </div>
                    <div class="install-notice">
                        <div class="notice-icon">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="notice-content">
                            <span class="notice-title">安装提示：</span>
                            首次安装时，部分杀毒软件可能会误报。请点击"信任"或添加白名单后继续安装。
                        </div>
                    </div>
                    <!-- Stats -->
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
        </div>
    </section>

    @php
    $hasMedia = !empty($heroMedia) && count($heroMedia) > 0;
    $hasVideo = !empty($heroVideoUrl);
    @endphp

    <!-- Showcase Section - 产品展示 -->
    @if($hasMedia || $hasVideo)
    <section id="showcase" class="section showcase">
        <div class="container">
            <div class="section-header">
                <span class="section-tag">产品展示</span>
                <h2 class="section-title">软件界面预览</h2>
                <p class="section-subtitle">直观的操作界面，让弹幕打印变得简单高效</p>
            </div>

            <!-- 主展示区 - 大图/视频轮播 -->
            <div class="showcase-main">
                <div class="showcase-viewer" id="showcaseViewer">
                    @if($hasMedia)
                    @foreach($heroMedia as $index => $media)
                    <div class="showcase-item {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}">
                        @if($media['type'] === 'video')
                        <video controls autoplay muted loop playsinline class="showcase-video">
                            <source src="{{ $media['url'] }}" type="video/mp4">
                            您的浏览器不支持视频播放
                        </video>
                        @elseif($media['type'] === 'gif')
                        <img src="{{ $media['url'] }}" alt="产品演示 {{ $index + 1 }}" class="showcase-image showcase-gif" loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                        @else
                        <img src="{{ $media['url'] }}" alt="产品截图 {{ $index + 1 }}" class="showcase-image" loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                        @endif
                    </div>
                    @endforeach
                    @elseif($hasVideo)
                    <div class="showcase-item active" data-index="0">
                        <video controls autoplay muted loop playsinline class="showcase-video">
                            <source src="{{ $heroVideoUrl }}" type="video/mp4">
                            您的浏览器不支持视频播放
                        </video>
                    </div>
                    @endif
                </div>

                <!-- 导航控制 -->
                @if($hasMedia && count($heroMedia) > 1)
                <button class="showcase-nav showcase-prev" id="showcasePrev">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button class="showcase-nav showcase-next" id="showcaseNext">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                @endif
            </div>

            <!-- 缩略图导航 -->
            @if($hasMedia && count($heroMedia) > 1)
            <div class="showcase-thumbnails" id="showcaseThumbnails">
                @foreach($heroMedia as $index => $media)
                <div class="showcase-thumb {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}">
                    @if($media['type'] === 'video')
                    <div class="thumb-video-icon">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z" />
                        </svg>
                    </div>
                    @endif
                    <img src="{{ $media['url'] }}" alt="缩略图 {{ $index + 1 }}" loading="lazy">
                </div>
                @endforeach
            </div>
            @endif

            <!-- 展示计数 -->
            @if($hasMedia && count($heroMedia) > 1)
            <div class="showcase-counter">
                <span id="showcaseCurrentIndex">1</span> / <span>{{ count($heroMedia) }}</span>
            </div>
            @endif
        </div>
    </section>
    @endif

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
                <div class="pricing-card {{ $plan->code === 'pro' ? 'popular' : '' }}">
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
                    <button class="btn plan-btn {{ $plan->code === 'pro' ? 'btn-primary' : 'btn-outline' }}">
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
                        <img src="{{ $contactWechatQrcode }}" alt="微信二维码" loading="lazy">
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
                        <img src="{{ $contactQqQrcode }}" alt="QQ二维码" loading="lazy">
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
            <div class="footer-content">
                <div class="footer-links">
                    <a href="#showcase">产品演示</a>
                    <a href="#features">功能介绍</a>
                    <a href="#pricing">价格方案</a>
                    <a href="#contact">联系我们</a>
                    <a href="{{ route('download.latest') }}">下载客户端</a>
                </div>
                <p class="footer-copyright">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Lightbox for full-size viewing -->
    <div class="lightbox" id="lightbox">
        <button class="lightbox-close" id="lightboxClose">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <img class="lightbox-content" id="lightboxImage" src="" alt="放大预览">
    </div>

    <script>
        // Header scroll effect
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Showcase Slider
        const showcaseItems = document.querySelectorAll('.showcase-item');
        const showcaseThumbs = document.querySelectorAll('.showcase-thumb');
        const showcasePrev = document.getElementById('showcasePrev');
        const showcaseNext = document.getElementById('showcaseNext');
        const showcaseCounter = document.getElementById('showcaseCurrentIndex');

        if (showcaseItems.length > 0) {
            let currentShowcaseIndex = 0;
            const totalShowcaseItems = showcaseItems.length;

            function showShowcaseSlide(index) {
                // Wrap around
                if (index < 0) index = totalShowcaseItems - 1;
                if (index >= totalShowcaseItems) index = 0;
                currentShowcaseIndex = index;

                showcaseItems.forEach((item, i) => {
                    item.classList.toggle('active', i === index);
                });
                showcaseThumbs.forEach((thumb, i) => {
                    thumb.classList.toggle('active', i === index);
                });
                if (showcaseCounter) {
                    showcaseCounter.textContent = index + 1;
                }
            }

            // Navigation buttons
            if (showcasePrev) {
                showcasePrev.addEventListener('click', () => {
                    showShowcaseSlide(currentShowcaseIndex - 1);
                });
            }
            if (showcaseNext) {
                showcaseNext.addEventListener('click', () => {
                    showShowcaseSlide(currentShowcaseIndex + 1);
                });
            }

            // Thumbnail clicks
            showcaseThumbs.forEach((thumb, index) => {
                thumb.addEventListener('click', () => {
                    showShowcaseSlide(index);
                });
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    showShowcaseSlide(currentShowcaseIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    showShowcaseSlide(currentShowcaseIndex + 1);
                }
            });

            // Auto-advance (optional - every 8 seconds)
            // setInterval(() => {
            //     showShowcaseSlide(currentShowcaseIndex + 1);
            // }, 8000);
        }

        // Lightbox functionality
        const lightbox = document.getElementById('lightbox');
        const lightboxImage = document.getElementById('lightboxImage');
        const lightboxClose = document.getElementById('lightboxClose');

        document.querySelectorAll('.showcase-image').forEach(img => {
            img.addEventListener('click', () => {
                lightboxImage.src = img.src;
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        });

        if (lightboxClose) {
            lightboxClose.addEventListener('click', () => {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            });
        }

        if (lightbox) {
            lightbox.addEventListener('click', (e) => {
                if (e.target === lightbox) {
                    lightbox.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        // Close lightbox with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && lightbox && lightbox.classList.contains('active')) {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .pricing-card, .contact-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>

</html>