@php
    $appUrl = rtrim(config('app.frontend_url', 'http://localhost:9000'), '/');
    $loginUrl = $appUrl . '/#/login';
    $registerUrl = $appUrl . '/#/register';
@endphp

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Compritapp organiza tus listas de compras, calcula precios por kilo o unidad y comparte el carrito con quien quieras usando un PIN.">
        <meta name="theme-color" content="#a52835">

        <title>Compritapp — Tus compras, en orden</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

        <style>
            *, *::before, *::after { box-sizing: border-box; }

            :root {
                --primary: #a52835;
                --primary-dark: #8f2230;
                --accent: #2f5d62;
                --accent-soft: #e8f1f0;
                --ink: #171412;
                --muted: #5f6368;
                --subtle: #8a847c;
                --bg: #fbfaf8;
                --surface: #ffffff;
                --border: #e7e2db;
                --radius: 20px;
                --font-display: 'Fraunces', Georgia, 'Times New Roman', serif;
                --font-body: 'DM Sans', 'Segoe UI', system-ui, sans-serif;
                --font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
                --shadow: 0 24px 60px -32px rgba(23, 20, 18, .4);
            }

            html { scroll-behavior: smooth; }

            body {
                margin: 0;
                background: var(--bg);
                color: var(--ink);
                font-family: var(--font-body);
                font-size: 16px;
                line-height: 1.6;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            img, svg { display: block; max-width: 100%; }
            a { color: inherit; text-decoration: none; }
            p { margin: 0; }

            .container {
                width: min(1120px, 100% - 48px);
                margin-inline: auto;
            }

            .skip-link {
                position: absolute;
                top: -100px;
                left: 16px;
                z-index: 100;
                padding: 10px 16px;
                border-radius: 999px;
                background: var(--ink);
                color: #fff;
                font-size: 14px;
            }

            .skip-link:focus { top: 16px; }

            /* Header */
            .site-header {
                position: sticky;
                top: 0;
                z-index: 60;
                border-bottom: 1px solid transparent;
                transition: background-color .3s ease, border-color .3s ease, backdrop-filter .3s ease;
            }

            .site-header.is-scrolled {
                background: rgba(251, 250, 248, .88);
                backdrop-filter: blur(16px);
                border-color: var(--border);
            }

            .header-inner {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                padding: 16px 0;
            }

            .brand {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                font-family: var(--font-mono);
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .16em;
                text-transform: uppercase;
            }

            .brand__mark {
                display: grid;
                place-items: center;
                width: 38px;
                height: 38px;
                border-radius: 12px;
                background: var(--primary);
                color: #fff;
            }

            .site-nav {
                display: flex;
                align-items: center;
                gap: 30px;
                font-size: 14.5px;
                font-weight: 500;
                color: var(--muted);
            }

            .site-nav a { transition: color .2s ease; }
            .site-nav a:hover { color: var(--ink); }

            .header-actions {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .nav-toggle {
                display: none;
                place-items: center;
                width: 42px;
                height: 42px;
                border: 1px solid var(--border);
                border-radius: 12px;
                background: transparent;
                color: var(--ink);
                cursor: pointer;
            }

            /* Buttons */
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 12px 22px;
                border: 1px solid transparent;
                border-radius: 999px;
                font-family: var(--font-body);
                font-size: 14px;
                font-weight: 600;
                line-height: 1.2;
                white-space: nowrap;
                cursor: pointer;
                transition: transform .2s ease, background-color .2s ease, border-color .2s ease, color .2s ease;
            }

            .btn--primary { background: var(--primary); color: #fff; }
            .btn--primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
            .btn--ghost { border-color: var(--border); color: var(--ink); background: transparent; }
            .btn--ghost:hover { border-color: var(--ink); }
            .btn--light { background: #fff; color: var(--primary); }
            .btn--light:hover { background: #f4ede4; transform: translateY(-1px); }
            .btn--outline-light { border-color: rgba(255, 255, 255, .45); color: #fff; }
            .btn--outline-light:hover { border-color: #fff; }

            /* Hero */
            .hero {
                position: relative;
                overflow: hidden;
                padding: 60px 0 32px;
            }

            .hero__grid {
                display: grid;
                grid-template-columns: minmax(0, 1.04fr) minmax(0, .96fr);
                gap: 56px;
                align-items: center;
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 7px 14px;
                border: 1px solid var(--border);
                border-radius: 999px;
                background: var(--surface);
                font-family: var(--font-mono);
                font-size: 10.5px;
                font-weight: 500;
                letter-spacing: .12em;
                text-transform: uppercase;
                color: var(--muted);
            }

            .badge::before {
                content: '';
                width: 7px;
                height: 7px;
                border-radius: 50%;
                background: var(--accent);
            }

            .hero h1 {
                margin: 20px 0 0;
                font-family: var(--font-display);
                font-size: clamp(2.7rem, 5.6vw, 4.5rem);
                font-weight: 600;
                letter-spacing: -.03em;
                line-height: 1.02;
            }

            .hero h1 em {
                font-style: italic;
                color: var(--accent);
            }

            .hero__lead {
                max-width: 520px;
                margin-top: 22px;
                color: var(--muted);
                font-size: 17px;
                line-height: 1.65;
            }

            .hero__actions {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 30px;
            }

            .hero__note {
                margin-top: 18px;
                font-family: var(--font-mono);
                font-size: 11px;
                letter-spacing: .08em;
                color: var(--subtle);
            }

            .hero__visual {
                position: relative;
                display: flex;
                justify-content: center;
                padding: 24px 0;
            }

            .blob {
                position: absolute;
                border-radius: 50%;
                filter: blur(70px);
                opacity: .5;
                pointer-events: none;
            }

            .blob--red {
                width: 340px;
                height: 340px;
                right: 8%;
                top: 2%;
                background: rgba(165, 40, 53, .28);
            }

            .blob--teal {
                width: 280px;
                height: 280px;
                left: 2%;
                bottom: 4%;
                background: rgba(47, 93, 98, .22);
            }

            /* Phone mockups */
            .phone {
                position: relative;
                width: 286px;
                max-width: 100%;
                aspect-ratio: 9 / 18.6;
                padding: 10px;
                border-radius: 44px;
                background: var(--ink);
                box-shadow: 0 40px 90px -40px rgba(23, 20, 18, .55);
            }

            .phone__notch {
                position: absolute;
                top: 10px;
                left: 50%;
                z-index: 3;
                width: 90px;
                height: 22px;
                border-radius: 0 0 14px 14px;
                background: var(--ink);
                transform: translateX(-50%);
            }

            .phone__screen {
                position: relative;
                display: flex;
                flex-direction: column;
                height: 100%;
                overflow: hidden;
                padding: 16px;
                border-radius: 34px;
                background: #fff;
                color: var(--ink);
            }

            .phone--hero { animation: floaty 7s ease-in-out infinite; }

            .float-card {
                position: absolute;
                z-index: 3;
                padding: 13px 15px;
                border: 1px solid var(--border);
                border-radius: 16px;
                background: var(--surface);
                box-shadow: var(--shadow);
                animation: floaty 8s ease-in-out infinite;
            }

            .float-card--stats {
                top: 8%;
                left: -7%;
                min-width: 156px;
            }

            .float-card--pin {
                right: -6%;
                bottom: 9%;
                min-width: 166px;
                animation-delay: -3.2s;
            }

            .float-card__label {
                display: block;
                font-family: var(--font-mono);
                font-size: 9px;
                font-weight: 500;
                letter-spacing: .12em;
                text-transform: uppercase;
                color: var(--muted);
            }

            .float-card strong {
                display: block;
                margin-top: 4px;
                font-family: var(--font-display);
                font-size: 22px;
                font-weight: 600;
                line-height: 1;
            }

            .float-card--pin strong {
                font-family: var(--font-mono);
                font-size: 17px;
                letter-spacing: .08em;
                color: var(--accent);
            }

            .spark {
                display: flex;
                align-items: flex-end;
                gap: 3px;
                height: 26px;
                margin-top: 10px;
            }

            .spark i {
                flex: 1;
                border-radius: 2px;
                background: var(--accent-soft);
            }

            .spark i.is-on { background: var(--accent); }

            /* Mock internals */
            .statusbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8px;
                font-family: var(--font-mono);
                font-size: 8.5px;
                color: var(--subtle);
            }

            .statusbar__icons { letter-spacing: 1px; }

            .mock-eyebrow {
                font-family: var(--font-mono);
                font-size: 8px;
                font-weight: 500;
                letter-spacing: .16em;
                text-transform: uppercase;
                color: var(--muted);
            }

            .mock-title {
                margin-top: 2px;
                font-family: var(--font-display);
                font-size: 20px;
                font-weight: 600;
                line-height: 1.1;
            }

            .mock-sub {
                margin-top: 2px;
                font-size: 9.5px;
                color: var(--muted);
            }

            .progress {
                height: 6px;
                margin-top: 9px;
                overflow: hidden;
                border-radius: 999px;
                background: #eee7df;
            }

            .progress span {
                display: block;
                height: 100%;
                border-radius: inherit;
                background: var(--accent);
            }

            .mock-list {
                display: flex;
                flex-direction: column;
                gap: 7px;
                margin-top: 10px;
            }

            .mock-row {
                display: flex;
                align-items: center;
                gap: 7px;
                padding: 7px 8px;
                border-radius: 10px;
                background: #f6f3ef;
                font-size: 9.5px;
            }

            .mock-row .check {
                display: grid;
                place-items: center;
                flex: none;
                width: 14px;
                height: 14px;
                border: 1px solid #d8d2c9;
                border-radius: 50%;
                font-size: 8px;
                color: transparent;
            }

            .mock-row.is-done .check {
                border-color: var(--accent);
                background: var(--accent);
                color: #fff;
            }

            .mock-row.is-done .mock-name {
                color: var(--subtle);
                text-decoration: line-through;
            }

            .mock-name {
                flex: 1;
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .mock-price {
                font-size: 9px;
                font-weight: 600;
            }

            .mock-total {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-top: auto;
                padding: 10px 12px;
                border-radius: 12px;
                background: var(--primary);
                color: #fff;
                font-size: 9.5px;
            }

            .mock-total strong {
                font-family: var(--font-display);
                font-size: 15px;
                font-weight: 600;
            }

            .mock-hero {
                padding: 11px;
                border-radius: 13px;
                background: var(--primary);
                color: #fff;
            }

            .mock-hero .mock-eyebrow { color: rgba(255, 255, 255, .72); }

            .mock-hero__amount {
                margin-top: 3px;
                font-family: var(--font-display);
                font-size: 21px;
                font-weight: 600;
                line-height: 1;
            }

            .mock-accent {
                margin-top: 8px;
                padding: 11px;
                border-radius: 13px;
                background: var(--accent);
                color: #fff;
            }

            .mock-accent .mock-eyebrow { color: rgba(255, 255, 255, .72); }

            .mock-accent__value {
                margin-top: 3px;
                font-family: var(--font-display);
                font-size: 19px;
                font-weight: 600;
                line-height: 1;
            }

            .mock-bars {
                display: flex;
                align-items: flex-end;
                gap: 5px;
                height: 56px;
                margin-top: 12px;
                padding: 0 2px;
            }

            .mock-bars span {
                flex: 1;
                border-radius: 4px 4px 2px 2px;
                background: #ede7df;
            }

            .mock-bars span.is-on { background: var(--accent); }

            .mock-card {
                margin-top: 8px;
                padding: 10px;
                border-radius: 12px;
                background: #f6f3ef;
            }

            .mock-card__row {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .mock-avatar {
                display: grid;
                place-items: center;
                flex: none;
                width: 26px;
                height: 26px;
                border-radius: 9px;
                background: var(--accent-soft);
                color: var(--accent);
            }

            .mock-card__name {
                font-size: 10px;
                font-weight: 600;
            }

            .mock-card__muted {
                font-size: 8px;
                color: var(--muted);
            }

            .mock-pill-row {
                display: flex;
                gap: 5px;
                margin-top: 8px;
            }

            .mock-pill {
                padding: 5px 8px;
                border-radius: 999px;
                background: #fff;
                border: 1px solid var(--border);
                font-size: 8px;
                font-weight: 600;
            }

            .mock-pill.is-active {
                background: var(--primary);
                border-color: var(--primary);
                color: #fff;
            }

            /* Sections */
            .section { padding: 96px 0; }

            .section-head { max-width: 660px; }

            .eyebrow {
                display: flex;
                align-items: center;
                gap: 10px;
                font-family: var(--font-mono);
                font-size: 11px;
                font-weight: 500;
                letter-spacing: .2em;
                text-transform: uppercase;
                color: var(--accent);
            }

            .eyebrow::before {
                content: '';
                width: 24px;
                height: 1px;
                background: currentColor;
            }

            .section-title {
                margin: 14px 0 0;
                font-family: var(--font-display);
                font-size: clamp(2rem, 4vw, 3rem);
                font-weight: 600;
                letter-spacing: -.025em;
                line-height: 1.08;
            }

            .section-lead {
                max-width: 560px;
                margin-top: 16px;
                color: var(--muted);
                font-size: 16px;
            }

            /* Stats strip */
            .stats {
                border-top: 1px solid var(--border);
                border-bottom: 1px solid var(--border);
                background: rgba(255, 255, 255, .5);
            }

            .stats__grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
            }

            .stat {
                padding: 30px 24px;
                border-left: 1px solid var(--border);
            }

            .stat:first-child { border-left: 0; padding-left: 0; }

            .stat strong {
                display: block;
                font-family: var(--font-display);
                font-size: clamp(1.9rem, 3.6vw, 2.8rem);
                font-weight: 600;
                letter-spacing: -.02em;
                line-height: 1;
            }

            .stat span {
                display: block;
                margin-top: 6px;
                font-family: var(--font-mono);
                font-size: 10.5px;
                letter-spacing: .1em;
                text-transform: uppercase;
                color: var(--muted);
            }

            /* Features */
            .features__grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
                margin-top: 44px;
            }

            .feature {
                padding: 26px;
                border: 1px solid var(--border);
                border-radius: var(--radius);
                background: var(--surface);
                transition: transform .3s ease, border-color .3s ease, box-shadow .3s ease;
            }

            .feature:hover {
                transform: translateY(-4px);
                border-color: #d8d0c6;
                box-shadow: var(--shadow);
            }

            .feature__icon {
                display: grid;
                place-items: center;
                width: 44px;
                height: 44px;
                border-radius: 14px;
                background: var(--accent-soft);
                color: var(--accent);
            }

            .feature__kicker {
                margin-top: 20px;
                font-family: var(--font-mono);
                font-size: 10px;
                font-weight: 500;
                letter-spacing: .12em;
                text-transform: uppercase;
                color: var(--muted);
            }

            .feature h3 {
                margin: 6px 0 0;
                font-family: var(--font-display);
                font-size: 20px;
                font-weight: 600;
                letter-spacing: -.01em;
                line-height: 1.2;
            }

            .feature p {
                margin-top: 8px;
                color: var(--muted);
                font-size: 14.5px;
            }

            /* Showcase */
            .showcase {
                position: relative;
                width: min(1120px, 100% - 32px);
                margin: 24px auto;
                padding: 80px 24px;
                overflow: hidden;
                border-radius: 32px;
                background: var(--ink);
                color: #f4f1ea;
            }

            .showcase::after {
                content: '';
                position: absolute;
                top: -180px;
                right: -120px;
                width: 420px;
                height: 420px;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(47, 93, 98, .5), transparent 70%);
                pointer-events: none;
            }

            .showcase .eyebrow { color: #8fc0ba; }
            .showcase .section-lead { color: rgba(244, 241, 234, .68); }

            .showcase__grid {
                position: relative;
                z-index: 1;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                align-items: end;
                gap: 28px;
                margin-top: 52px;
            }

            .showcase__item {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 16px;
            }

            .showcase .phone {
                width: 240px;
                border: 1px solid #3a3733;
                background: #0e0d0c;
                box-shadow: 0 40px 80px -30px rgba(0, 0, 0, .85);
            }

            .showcase .phone__notch { background: #0e0d0c; }

            .screen-label {
                font-family: var(--font-mono);
                font-size: 10px;
                font-weight: 500;
                letter-spacing: .18em;
                text-transform: uppercase;
                color: rgba(244, 241, 234, .55);
            }

            /* Steps */
            .steps__grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 28px;
                margin-top: 48px;
            }

            .step {
                padding-top: 20px;
                border-top: 1px solid var(--border);
            }

            .step__num {
                font-family: var(--font-display);
                font-size: 36px;
                font-weight: 600;
                line-height: 1;
                color: var(--accent);
            }

            .step h3 {
                margin: 14px 0 0;
                font-family: var(--font-display);
                font-size: 21px;
                font-weight: 600;
                letter-spacing: -.01em;
            }

            .step p {
                margin-top: 6px;
                color: var(--muted);
                font-size: 14.5px;
            }

            /* CTA */
            .cta-band { padding: 24px 0 96px; }

            .cta-card {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 36px;
                overflow: hidden;
                padding: 56px;
                border-radius: 32px;
                background: var(--primary);
                color: #fff;
            }

            .cta-card::after {
                content: '';
                position: absolute;
                top: -90px;
                right: -70px;
                width: 320px;
                height: 320px;
                border-radius: 50%;
                background: rgba(255, 255, 255, .08);
            }

            .cta-card h2 {
                margin: 0;
                font-family: var(--font-display);
                font-size: clamp(1.9rem, 3.4vw, 2.7rem);
                font-weight: 600;
                letter-spacing: -.02em;
                line-height: 1.08;
            }

            .cta-card p {
                max-width: 480px;
                margin-top: 12px;
                color: rgba(255, 255, 255, .78);
                font-size: 15.5px;
            }

            .cta-actions {
                position: relative;
                z-index: 1;
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
            }

            /* Footer */
            .site-footer {
                padding: 44px 0;
                border-top: 1px solid var(--border);
            }

            .footer-inner {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 24px;
                flex-wrap: wrap;
            }

            .footer-brand {
                display: flex;
                align-items: center;
                gap: 10px;
                font-family: var(--font-mono);
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .14em;
                text-transform: uppercase;
            }

            .footer-nav {
                display: flex;
                flex-wrap: wrap;
                gap: 22px;
                font-size: 14px;
                color: var(--muted);
            }

            .footer-nav a:hover { color: var(--ink); }

            .footer-copy {
                font-family: var(--font-mono);
                font-size: 10.5px;
                letter-spacing: .08em;
                color: var(--subtle);
            }

            /* Motion */
            .reveal {
                opacity: 0;
                transform: translateY(26px);
                transition: opacity .8s ease, transform .8s cubic-bezier(.2, .7, .2, 1);
                transition-delay: var(--delay, 0ms);
            }

            .reveal.is-visible {
                opacity: 1;
                transform: none;
            }

            @keyframes floaty {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-12px); }
            }

            /* Responsive */
            @media (max-width: 960px) {
                .hero__grid { grid-template-columns: 1fr; gap: 40px; }
                .hero__lead { max-width: 620px; }
                .hero__visual { padding-top: 8px; }
                .features__grid { grid-template-columns: repeat(2, 1fr); }
                .showcase__grid { grid-template-columns: 1fr; justify-items: center; gap: 44px; }
                .steps__grid { grid-template-columns: 1fr; gap: 32px; }
                .cta-card { flex-direction: column; align-items: flex-start; }
            }

            @media (max-width: 860px) {
                .site-nav {
                    position: absolute;
                    top: calc(100% + 4px);
                    right: 0;
                    left: 0;
                    display: none;
                    flex-direction: column;
                    align-items: stretch;
                    gap: 2px;
                    padding: 12px;
                    border: 1px solid var(--border);
                    border-radius: 16px;
                    background: var(--surface);
                    box-shadow: var(--shadow);
                }

                .site-nav.is-open { display: flex; }

                .site-nav a { padding: 10px 12px; border-radius: 10px; }
                .site-nav a:hover { background: #f6f3ef; }

                .nav-toggle { display: inline-grid; }
                .header-actions .btn--ghost { display: none; }
            }

            @media (max-width: 720px) {
                .section { padding: 72px 0; }
                .showcase { padding: 64px 20px; border-radius: 26px; }
                .cta-card { padding: 40px 28px; border-radius: 26px; }
                .float-card--stats { left: -2%; }
                .float-card--pin { right: -2%; }
            }

            @media (max-width: 640px) {
                .hero { padding-top: 40px; }
                .hero h1 { font-size: clamp(2.3rem, 10vw, 3rem); }
                .hero__lead { font-size: 16px; }
                .features__grid { grid-template-columns: 1fr; }
                .stats__grid { grid-template-columns: 1fr; }
                .stat { padding: 20px 0; border-left: 0; border-top: 1px solid var(--border); }
                .stat:first-child { border-top: 0; }
                .phone { width: 260px; }
                .float-card { display: none; }
                .footer-inner { flex-direction: column; align-items: flex-start; }
                .cta-actions { width: 100%; }
                .cta-actions .btn { flex: 1 1 auto; }
            }

            @media (prefers-reduced-motion: reduce) {
                html { scroll-behavior: auto; }
                *, *::before, *::after { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; }
                .reveal { opacity: 1; transform: none; }
            }
        </style>
    </head>
    <body>
        <a class="skip-link" href="#main">Saltar al contenido</a>

        <header class="site-header" id="site-header">
            <div class="container header-inner">
                <a class="brand" href="#top" aria-label="Compritapp, ir al inicio">
                    <span class="brand__mark" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 7h12l-1.2 12.2a1.8 1.8 0 0 1-1.8 1.6H9a1.8 1.8 0 0 1-1.8-1.6L6 7Z"/>
                            <path d="M9 7a3 3 0 0 1 6 0"/>
                            <path d="M9.5 12h5"/>
                        </svg>
                    </span>
                    Compritapp
                </a>

                <nav class="site-nav" id="site-nav" aria-label="Principal">
                    <a href="#funciones">Funciones</a>
                    <a href="#la-app">La app</a>
                    <a href="#como-funciona">Cómo funciona</a>
                </nav>

                <div class="header-actions">
                    <a class="btn btn--ghost" href="{{ $loginUrl }}">Iniciar sesión</a>
                    <a class="btn btn--primary" href="{{ $registerUrl }}">Crear cuenta</a>
                    <button class="nav-toggle" type="button" aria-label="Abrir menú" aria-controls="site-nav" aria-expanded="false">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                            <path d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <main id="main">
            <section class="hero" id="top">
                <div class="container hero__grid">
                    <div>
                        <span class="badge reveal">Compras compartidas, sin vueltas</span>

                        <h1 class="reveal">
                            Tus compras, en orden.<br>
                            Tus gastos, <em>bajo control</em>.
                        </h1>

                        <p class="hero__lead reveal">
                            Compritapp organiza tus listas, suma precios por kilo o unidad y comparte
                            el carrito con quien quieras usando un PIN de 8 dígitos.
                        </p>

                        <div class="hero__actions reveal">
                            <a class="btn btn--primary" href="{{ $registerUrl }}">Crear cuenta gratis</a>
                            <a class="btn btn--ghost" href="#funciones">Ver funciones</a>
                        </div>

                        <p class="hero__note reveal">Gratis · Sin instalar · Colaborativo</p>
                    </div>

                    <div class="hero__visual reveal">
                        <div class="blob blob--red" aria-hidden="true"></div>
                        <div class="blob blob--teal" aria-hidden="true"></div>

                        <div class="phone phone--hero" aria-hidden="true">
                            <div class="phone__notch"></div>
                            <div class="phone__screen">
                                <div class="statusbar">
                                    <span>9:41</span>
                                    <span class="statusbar__icons">▮▮▮</span>
                                </div>

                                <div>
                                    <span class="mock-eyebrow">Lista activa</span>
                                    <div class="mock-title">Súper semanal</div>
                                    <div class="mock-sub">6 de 9 productos marcados</div>
                                    <div class="progress"><span style="width: 66%"></span></div>
                                </div>

                                <div class="mock-list">
                                    <div class="mock-row is-done">
                                        <span class="check">✓</span>
                                        <span class="mock-name">Leche entera</span>
                                        <span class="mock-price">$1.200</span>
                                    </div>
                                    <div class="mock-row is-done">
                                        <span class="check">✓</span>
                                        <span class="mock-name">Pan francés</span>
                                        <span class="mock-price">$800</span>
                                    </div>
                                    <div class="mock-row is-done">
                                        <span class="check">✓</span>
                                        <span class="mock-name">Manzanas · 1,5 kg</span>
                                        <span class="mock-price">$2.250</span>
                                    </div>
                                    <div class="mock-row">
                                        <span class="check">✓</span>
                                        <span class="mock-name">Café</span>
                                        <span class="mock-price">$3.100</span>
                                    </div>
                                    <div class="mock-row">
                                        <span class="check">✓</span>
                                        <span class="mock-name">Detergente</span>
                                        <span class="mock-price">$1.100</span>
                                    </div>
                                </div>

                                <div class="mock-total">
                                    <span>Total de la lista</span>
                                    <strong>$8.450</strong>
                                </div>
                            </div>
                        </div>

                        <div class="float-card float-card--stats">
                            <span class="float-card__label">Este mes</span>
                            <strong>$52.400</strong>
                            <div class="spark">
                                <i style="height: 38%"></i>
                                <i style="height: 62%" class="is-on"></i>
                                <i style="height: 48%"></i>
                                <i style="height: 84%" class="is-on"></i>
                                <i style="height: 56%"></i>
                            </div>
                        </div>

                        <div class="float-card float-card--pin">
                            <span class="float-card__label">PIN para compartir</span>
                            <strong>4821 9032</strong>
                        </div>
                    </div>
                </div>
            </section>

            <section class="stats" aria-label="Datos de la app">
                <div class="container stats__grid">
                    <div class="stat reveal">
                        <strong><span data-count="20">20</span></strong>
                        <span>categorías listas</span>
                    </div>
                    <div class="stat reveal">
                        <strong><span data-count="88">88</span></strong>
                        <span>productos de ejemplo</span>
                    </div>
                    <div class="stat reveal">
                        <strong>1</strong>
                        <span>PIN para compartir</span>
                    </div>
                </div>
            </section>

            <section class="section" id="funciones">
                <div class="container">
                    <div class="section-head reveal">
                        <div class="eyebrow">Funciones</div>
                        <h2 class="section-title">Todo lo que hace falta, nada de más.</h2>
                        <p class="section-lead">
                            Desde la lista del súper hasta el presupuesto del mes. Simple de usar,
                            pensado para compartir.
                        </p>
                    </div>

                    <div class="features__grid">
                        <article class="feature reveal">
                            <div class="feature__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M8 6h13M8 12h13M8 18h13"/>
                                    <path d="m3 6 1 1 2-2M3 12l1 1 2-2M3 18l1 1 2-2"/>
                                </svg>
                            </div>
                            <div class="feature__kicker">Listas</div>
                            <h3>Listas inteligentes</h3>
                            <p>Armá listas, marcá productos y retomá donde dejaste.</p>
                        </article>

                        <article class="feature reveal">
                            <div class="feature__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="3"/>
                                    <path d="M19 8v6M22 11h-6"/>
                                </svg>
                            </div>
                            <div class="feature__kicker">Colaboración</div>
                            <h3>Compartí con un PIN</h3>
                            <p>Sumá miembros a una lista con un código privado de 8 dígitos.</p>
                        </article>

                        <article class="feature reveal">
                            <div class="feature__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3v18"/>
                                    <path d="M5 7h14"/>
                                    <path d="M7 7 4 14a3 3 0 0 0 6 0L7 7ZM17 7l-3 7a3 3 0 0 0 6 0l-3-7Z"/>
                                </svg>
                            </div>
                            <div class="feature__kicker">Precios</div>
                            <h3>Por kilo o por unidad</h3>
                            <p>Cargá precios y calculá totales reales, incluso por peso.</p>
                        </article>

                        <article class="feature reveal">
                            <div class="feature__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>
                                </svg>
                            </div>
                            <div class="feature__kicker">Estadísticas</div>
                            <h3>Gastos con contexto</h3>
                            <p>Mirá cuánto gastás por mes, categoría y producto.</p>
                        </article>

                        <article class="feature reveal">
                            <div class="feature__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5"/>
                                    <rect x="14" y="3" width="7" height="7" rx="1.5"/>
                                    <rect x="3" y="14" width="7" height="7" rx="1.5"/>
                                    <rect x="14" y="14" width="7" height="7" rx="1.5"/>
                                </svg>
                            </div>
                            <div class="feature__kicker">Catálogo</div>
                            <h3>Tu catálogo, listo</h3>
                            <p>Categorías y productos cargados desde el primer día.</p>
                        </article>

                        <article class="feature reveal">
                            <div class="feature__icon" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                                    <path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                                </svg>
                            </div>
                            <div class="feature__kicker">Seguimiento</div>
                            <h3>Historial y avisos</h3>
                            <p>Consultá compras cerradas y enterate de cada movimiento.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="showcase" id="la-app">
                <div class="container">
                    <div class="section-head reveal">
                        <div class="eyebrow">La app</div>
                        <h2 class="section-title">Mirá la app por dentro.</h2>
                        <p class="section-lead">
                            Una interfaz clara, en español y con modo oscuro. Todo lo importante,
                            a un toque.
                        </p>
                    </div>

                    <div class="showcase__grid">
                        <div class="showcase__item reveal">
                            <div class="phone" aria-hidden="true">
                                <div class="phone__notch"></div>
                                <div class="phone__screen">
                                    <div class="statusbar"><span>9:41</span><span class="statusbar__icons">▮▮▮</span></div>
                                    <div>
                                        <span class="mock-eyebrow">Lista activa</span>
                                        <div class="mock-title">Compra del sábado</div>
                                        <div class="mock-sub">3 de 5 marcados</div>
                                        <div class="progress"><span style="width: 60%"></span></div>
                                    </div>
                                    <div class="mock-list">
                                        <div class="mock-row is-done"><span class="check">✓</span><span class="mock-name">Yogur natural</span><span class="mock-price">$1.450</span></div>
                                        <div class="mock-row is-done"><span class="check">✓</span><span class="mock-name">Tomates · 1 kg</span><span class="mock-price">$1.900</span></div>
                                        <div class="mock-row"><span class="check">✓</span><span class="mock-name">Queso cremoso</span><span class="mock-price">$2.700</span></div>
                                        <div class="mock-row"><span class="check">✓</span><span class="mock-name">Fideos</span><span class="mock-price">$1.300</span></div>
                                    </div>
                                    <div class="mock-total"><span>Total</span><strong>$7.350</strong></div>
                                </div>
                            </div>
                            <span class="screen-label">Mi lista</span>
                        </div>

                        <div class="showcase__item reveal">
                            <div class="phone" aria-hidden="true">
                                <div class="phone__notch"></div>
                                <div class="phone__screen">
                                    <div class="statusbar"><span>9:41</span><span class="statusbar__icons">▮▮▮</span></div>
                                    <div class="mock-hero">
                                        <div class="mock-eyebrow">Gastos del período</div>
                                        <div class="mock-hero__amount">$52.400</div>
                                        <div class="mock-sub" style="color: rgba(255,255,255,.72)">4 listas completadas</div>
                                    </div>
                                    <div class="mock-accent">
                                        <div class="mock-eyebrow">Cobertura</div>
                                        <div class="mock-accent__value">82%</div>
                                    </div>
                                    <div class="mock-bars">
                                        <span style="height: 30%"></span>
                                        <span style="height: 55%" class="is-on"></span>
                                        <span style="height: 42%"></span>
                                        <span style="height: 78%" class="is-on"></span>
                                        <span style="height: 60%"></span>
                                        <span style="height: 90%" class="is-on"></span>
                                    </div>
                                </div>
                            </div>
                            <span class="screen-label">Estadísticas</span>
                        </div>

                        <div class="showcase__item reveal">
                            <div class="phone" aria-hidden="true">
                                <div class="phone__notch"></div>
                                <div class="phone__screen">
                                    <div class="statusbar"><span>9:41</span><span class="statusbar__icons">▮▮▮</span></div>
                                    <div class="mock-card">
                                        <div class="mock-card__row">
                                            <span class="mock-avatar" aria-hidden="true">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="8" r="3.5"/>
                                                    <path d="M5 20a7 7 0 0 1 14 0"/>
                                                </svg>
                                            </span>
                                            <div>
                                                <div class="mock-card__name">Lucas</div>
                                                <div class="mock-card__muted">lucas@correo.com</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mock-card">
                                        <div class="mock-eyebrow">Tu PIN para compartir</div>
                                        <div class="mock-card__name" style="font-family: var(--font-mono); letter-spacing: .08em; margin-top: 4px;">4821 9032</div>
                                    </div>
                                    <div class="mock-card">
                                        <div class="mock-card__row" style="justify-content: space-between;">
                                            <span class="mock-card__name">Modo oscuro</span>
                                            <span class="mock-card__muted">●</span>
                                        </div>
                                    </div>
                                    <div class="mock-pill-row">
                                        <span class="mock-pill is-active">Por defecto</span>
                                        <span class="mock-pill">Lectura fácil</span>
                                    </div>
                                </div>
                            </div>
                            <span class="screen-label">Configuración</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="section" id="como-funciona">
                <div class="container">
                    <div class="section-head reveal">
                        <div class="eyebrow">Cómo funciona</div>
                        <h2 class="section-title">Empezar es simple.</h2>
                        <p class="section-lead">
                            Sin configuraciones eternas. Tu cuenta ya viene con categorías y
                            productos de ejemplo.
                        </p>
                    </div>

                    <div class="steps__grid">
                        <article class="step reveal">
                            <div class="step__num">01</div>
                            <h3>Creá tu cuenta</h3>
                            <p>En segundos y sin tarjeta. Tu espacio personal queda listo al instante.</p>
                        </article>
                        <article class="step reveal">
                            <div class="step__num">02</div>
                            <h3>Armá tu lista</h3>
                            <p>Agregá productos del catálogo o creá los tuyos con precio por kilo o unidad.</p>
                        </article>
                        <article class="step reveal">
                            <div class="step__num">03</div>
                            <h3>Compartí y comprá</h3>
                            <p>Sumá gente con tu PIN y seguí el total de la compra en vivo.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="cta-band">
                <div class="container">
                    <div class="cta-card reveal">
                        <div>
                            <h2>¿Arrancamos con la próxima compra?</h2>
                            <p>Creá tu cuenta, armá la lista y compartila. El resto es comprar.</p>
                        </div>
                        <div class="cta-actions">
                            <a class="btn btn--light" href="{{ $registerUrl }}">Crear cuenta gratis</a>
                            <a class="btn btn--outline-light" href="{{ $loginUrl }}">Ya tengo cuenta</a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="container footer-inner">
                <a class="footer-brand" href="#top">
                    <span class="brand__mark" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 7h12l-1.2 12.2a1.8 1.8 0 0 1-1.8 1.6H9a1.8 1.8 0 0 1-1.8-1.6L6 7Z"/>
                            <path d="M9 7a3 3 0 0 1 6 0"/>
                            <path d="M9.5 12h5"/>
                        </svg>
                    </span>
                    Compritapp
                </a>

                <nav class="footer-nav" aria-label="Secundario">
                    <a href="#funciones">Funciones</a>
                    <a href="#la-app">La app</a>
                    <a href="{{ $loginUrl }}">Iniciar sesión</a>
                    <a href="{{ $registerUrl }}">Crear cuenta</a>
                </nav>

                <div class="footer-copy">© {{ date('Y') }} Compritapp · Tus compras, en orden.</div>
            </div>
        </footer>

        <script>
            (function () {
                var header = document.getElementById('site-header');
                var nav = document.getElementById('site-nav');
                var toggle = document.querySelector('.nav-toggle');
                var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                function setHeaderState() {
                    if (!header) return;
                    header.classList.toggle('is-scrolled', window.scrollY > 10);
                }

                setHeaderState();
                window.addEventListener('scroll', setHeaderState, { passive: true });

                if (toggle && nav) {
                    toggle.addEventListener('click', function () {
                        var isOpen = nav.classList.toggle('is-open');
                        toggle.setAttribute('aria-expanded', String(isOpen));
                        toggle.setAttribute('aria-label', isOpen ? 'Cerrar menú' : 'Abrir menú');
                    });

                    nav.querySelectorAll('a').forEach(function (link) {
                        link.addEventListener('click', function () {
                            nav.classList.remove('is-open');
                            toggle.setAttribute('aria-expanded', 'false');
                            toggle.setAttribute('aria-label', 'Abrir menú');
                        });
                    });
                }

                var reveals = Array.prototype.slice.call(document.querySelectorAll('.reveal'));

                reveals.forEach(function (element, index) {
                    element.style.setProperty('--delay', Math.min(index % 6, 5) * 70 + 'ms');
                });

                if (reduceMotion || !('IntersectionObserver' in window)) {
                    reveals.forEach(function (element) { element.classList.add('is-visible'); });
                } else {
                    var revealObserver = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                revealObserver.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

                    reveals.forEach(function (element) { revealObserver.observe(element); });
                }

                var counters = Array.prototype.slice.call(document.querySelectorAll('[data-count]'));

                if (counters.length && !reduceMotion && 'IntersectionObserver' in window) {
                    var counterObserver = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (!entry.isIntersecting) return;

                            var element = entry.target;
                            var target = Number(element.dataset.count || 0);
                            var start = performance.now();
                            var duration = 1200;

                            function tick(now) {
                                var progress = Math.min((now - start) / duration, 1);
                                var eased = 1 - Math.pow(1 - progress, 3);
                                element.textContent = String(Math.round(target * eased));

                                if (progress < 1) {
                                    requestAnimationFrame(tick);
                                }
                            }

                            requestAnimationFrame(tick);
                            counterObserver.unobserve(element);
                        });
                    }, { threshold: 0.6 });

                    counters.forEach(function (element) { counterObserver.observe(element); });
                }
            })();
        </script>
    </body>
</html>
