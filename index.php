<?php
/**
 * =====================================================
 * Landing / Welcome Page
 * ChatApp - First Impression
 * =====================================================
 */
define('APP_RUNNING', true);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';
session_initialize();

if (session_is_logged_in()) {
    header('Location: pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatApp - Connect Instantly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #818cf8;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-card-hover: #253349;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border: #334155;
            --success: #22c55e;
            --gradient-1: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a78bfa 100%);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* NAVBAR */
        .navbar-landing {
            position: fixed; top: 0; width: 100%; z-index: 1000;
            padding: 1rem 0; transition: all 0.3s ease; background: transparent;
        }
        .navbar-landing.scrolled {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 0.5rem 0;
        }
        .navbar-brand {
            font-size: 1.5rem; font-weight: 800; color: #fff !important;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .navbar-brand .brand-icon {
            width: 36px; height: 36px; background: var(--gradient-1);
            border-radius: 10px; display: flex; align-items: center;
            justify-content: center; font-size: 1rem;
        }
        .nav-link {
            color: var(--text-secondary) !important; font-weight: 500;
            transition: color 0.2s; padding: 0.5rem 1rem !important;
        }
        .nav-link:hover { color: #fff !important; }
        .btn-nav-login {
            background: transparent; color: #fff !important;
            border: 1px solid var(--border); padding: 0.5rem 1.25rem;
            border-radius: 8px; font-weight: 500; transition: all 0.2s;
            text-decoration: none;
        }
        .btn-nav-login:hover { border-color: var(--primary); background: rgba(99, 102, 241, 0.1); color: #fff !important; }
        .btn-nav-signup {
            background: var(--primary); color: #fff; border: none;
            padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600;
            transition: all 0.2s; text-decoration: none;
        }
        .btn-nav-signup:hover { background: var(--primary-hover); transform: translateY(-1px); color: #fff !important; }

        /* HERO */
        .hero {
            min-height: 100vh; display: flex; align-items: center;
            position: relative; padding: 8rem 0 4rem; overflow: hidden;
        }
        .hero::before {
            content: ''; position: absolute; top: -50%; left: -20%;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%);
            border-radius: 50%; pointer-events: none;
        }
        .hero::after {
            content: ''; position: absolute; bottom: -30%; right: -10%;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(139,92,246,0.08) 0%, transparent 70%);
            border-radius: 50%; pointer-events: none;
        }
        .hero-content { position: relative; z-index: 2; }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.4rem 1rem; background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 50px;
            font-size: 0.8rem; font-weight: 500; color: var(--primary-light);
            margin-bottom: 1.5rem;
        }
        .hero-badge .dot {
            width: 6px; height: 6px; background: var(--success);
            border-radius: 50%; animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .hero h1 { font-size: 3.5rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem; }
        .hero h1 .gradient-text {
            background: var(--gradient-1); -webkit-background-clip: text;
            -webkit-text-fill-color: transparent; background-clip: text;
        }
        .hero p {
            font-size: 1.15rem; color: var(--text-secondary); line-height: 1.7;
            max-width: 520px; margin-bottom: 2rem;
        }
        .hero-buttons { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn-hero-primary {
            background: var(--gradient-1); color: #fff; border: none;
            padding: 0.85rem 2rem; border-radius: 12px; font-size: 1rem;
            font-weight: 600; display: inline-flex; align-items: center;
            gap: 0.5rem; transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3); text-decoration: none;
        }
        .btn-hero-primary:hover {
            transform: translateY(-2px); box-shadow: 0 8px 30px rgba(99, 102, 241, 0.4); color: #fff;
        }
        .btn-hero-secondary {
            background: var(--bg-card); color: var(--text-primary);
            border: 1px solid var(--border); padding: 0.85rem 2rem; border-radius: 12px;
            font-size: 1rem; font-weight: 600; display: inline-flex;
            align-items: center; gap: 0.5rem; transition: all 0.3s; text-decoration: none;
        }
        .btn-hero-secondary:hover {
            border-color: var(--primary); background: var(--bg-card-hover);
            color: #fff; transform: translateY(-2px);
        }
        .hero-stats {
            display: flex; gap: 2.5rem; margin-top: 3rem;
            padding-top: 2rem; border-top: 1px solid var(--border);
        }
        .hero-stat h3 { font-size: 1.75rem; font-weight: 700; color: #fff; }
        .hero-stat p { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0; }

        /* PHONE MOCKUP */
        .hero-visual { position: relative; z-index: 2; display: flex; justify-content: center; }
        .phone-mockup {
            width: 280px; height: 560px; background: var(--bg-card);
            border-radius: 36px; border: 3px solid var(--border);
            overflow: hidden; position: relative;
            box-shadow: 0 0 60px rgba(99, 102, 241, 0.15), 0 25px 50px rgba(0,0,0,0.4);
        }
        .phone-notch {
            width: 120px; height: 25px; background: var(--bg-dark);
            border-radius: 0 0 16px 16px; margin: 0 auto; position: relative; z-index: 3;
        }
        .phone-screen { padding: 0.5rem; height: calc(100% - 25px); display: flex; flex-direction: column; }
        .phone-header { text-align: center; padding: 0.75rem 0; border-bottom: 1px solid var(--border); }
        .phone-header h6 { font-weight: 700; font-size: 0.85rem; margin: 0; }
        .phone-messages { flex: 1; padding: 0.75rem 0.25rem; overflow: hidden; }
        .msg {
            margin-bottom: 0.5rem; max-width: 80%; padding: 0.5rem 0.75rem;
            border-radius: 14px; font-size: 0.7rem; line-height: 1.4;
            animation: fadeInMsg 0.5s ease forwards; opacity: 0;
        }
        .msg-received { background: var(--bg-card-hover); color: var(--text-primary); border-bottom-left-radius: 4px; margin-right: auto; }
        .msg-sent { background: var(--primary); color: #fff; border-bottom-right-radius: 4px; margin-left: auto; }
        .msg:nth-child(1) { animation-delay: 0.3s; }
        .msg:nth-child(2) { animation-delay: 0.8s; }
        .msg:nth-child(3) { animation-delay: 1.3s; }
        .msg:nth-child(4) { animation-delay: 1.8s; }
        .msg:nth-child(5) { animation-delay: 2.3s; }
        .msg:nth-child(6) { animation-delay: 2.8s; }
        @keyframes fadeInMsg { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .typing-indicator {
            display: flex; gap: 3px; padding: 0.5rem 0.75rem;
            background: var(--bg-card-hover); border-radius: 14px;
            width: fit-content; margin-top: 0.25rem;
        }
        .typing-indicator span {
            width: 5px; height: 5px; background: var(--text-muted);
            border-radius: 50%; animation: typingBounce 1.4s infinite;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingBounce { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-4px); } }

        /* FEATURES */
        .features { padding: 6rem 0; background: var(--bg-card); }
        .section-label {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.35rem 0.9rem; background: rgba(99,102,241,0.1);
            border: 1px solid rgba(99,102,241,0.2); border-radius: 50px;
            font-size: 0.75rem; font-weight: 600; color: var(--primary-light);
            text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 1rem;
        }
        .section-title { font-size: 2.25rem; font-weight: 800; margin-bottom: 1rem; }
        .section-subtitle { color: var(--text-secondary); font-size: 1.05rem; max-width: 550px; margin: 0 auto 3rem; }
        .feature-card {
            background: var(--bg-dark); border: 1px solid var(--border);
            border-radius: 16px; padding: 2rem; transition: all 0.3s; height: 100%;
        }
        .feature-card:hover {
            border-color: var(--primary); transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(99,102,241,0.1);
        }
        .feature-icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem; margin-bottom: 1.25rem;
        }
        .feature-icon.purple { background: rgba(99,102,241,0.12); color: var(--primary-light); }
        .feature-icon.green { background: rgba(34,197,94,0.12); color: var(--success); }
        .feature-icon.blue { background: rgba(59,130,246,0.12); color: #60a5fa; }
        .feature-icon.orange { background: rgba(249,115,22,0.12); color: #fb923c; }
        .feature-icon.pink { background: rgba(236,72,153,0.12); color: #f472b6; }
        .feature-icon.cyan { background: rgba(6,182,212,0.12); color: #22d3ee; }
        .feature-card h5 { font-weight: 700; margin-bottom: 0.5rem; }
        .feature-card p { color: var(--text-secondary); font-size: 0.9rem; margin: 0; line-height: 1.6; }

        /* HOW IT WORKS */
        .how-it-works { padding: 6rem 0; }
        .step-card { text-align: center; padding: 2rem 1.5rem; position: relative; }
        .step-number {
            width: 60px; height: 60px; border-radius: 50%;
            background: var(--gradient-1); display: flex; align-items: center;
            justify-content: center; font-size: 1.25rem; font-weight: 800;
            color: #fff; margin: 0 auto 1.25rem;
            box-shadow: 0 4px 20px rgba(99,102,241,0.3);
        }
        .step-card h5 { font-weight: 700; margin-bottom: 0.5rem; }
        .step-card p { color: var(--text-secondary); font-size: 0.9rem; }
        .step-connector {
            position: absolute; top: 50px; right: -15%; width: 30%; height: 2px; background: var(--border);
        }
        .step-connector::after {
            content: ''; position: absolute; right: 0; top: -4px;
            border: 5px solid transparent; border-left-color: var(--border);
        }

        /* CTA */
        .cta-section { padding: 6rem 0; background: var(--bg-card); }
        .cta-box {
            background: var(--gradient-1); border-radius: 24px;
            padding: 4rem 3rem; text-align: center; position: relative; overflow: hidden;
        }
        .cta-box::before {
            content: ''; position: absolute; top: -50%; left: -25%;
            width: 150%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 60%);
            pointer-events: none;
        }
        .cta-box h2 { font-size: 2.25rem; font-weight: 800; color: #fff; margin-bottom: 1rem; position: relative; }
        .cta-box p { color: rgba(255,255,255,0.8); font-size: 1.1rem; max-width: 480px; margin: 0 auto 2rem; position: relative; }
        .btn-cta {
            background: #fff; color: var(--primary); border: none;
            padding: 0.85rem 2.5rem; border-radius: 12px; font-size: 1rem;
            font-weight: 700; display: inline-flex; align-items: center;
            gap: 0.5rem; transition: all 0.3s; position: relative; text-decoration: none;
        }
        .btn-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.2); color: var(--primary-hover); }

        /* FOOTER */
        .footer { padding: 3rem 0 1.5rem; border-top: 1px solid var(--border); }
        .footer-brand {
            font-size: 1.25rem; font-weight: 800; color: #fff;
            display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem;
        }
        .footer-brand .brand-icon {
            width: 30px; height: 30px; background: var(--gradient-1);
            border-radius: 8px; display: flex; align-items: center;
            justify-content: center; font-size: 0.8rem;
        }
        .footer p { color: var(--text-muted); font-size: 0.85rem; }
        .footer-links h6 {
            font-weight: 700; font-size: 0.85rem; text-transform: uppercase;
            letter-spacing: 0.05em; margin-bottom: 1rem; color: var(--text-secondary);
        }
        .footer-links a {
            color: var(--text-muted); text-decoration: none; font-size: 0.85rem;
            display: block; padding: 0.2rem 0; transition: color 0.2s;
        }
        .footer-links a:hover { color: var(--primary-light); }
        .footer-bottom { border-top: 1px solid var(--border); padding-top: 1.5rem; margin-top: 2rem; text-align: center; }
        .footer-bottom p { font-size: 0.8rem; }
        .footer-social a {
            width: 36px; height: 36px; border-radius: 8px; background: var(--bg-dark);
            border: 1px solid var(--border); display: inline-flex; align-items: center;
            justify-content: center; color: var(--text-muted); transition: all 0.2s; margin-left: 0.5rem;
        }
        .footer-social a:hover { border-color: var(--primary); color: var(--primary-light); background: rgba(99,102,241,0.1); }

        /* RESPONSIVE */
        @media (max-width: 991px) {
            .hero h1 { font-size: 2.5rem; }
            .hero-visual { margin-top: 3rem; }
            .phone-mockup { width: 240px; height: 480px; }
            .step-connector { display: none; }
        }
        @media (max-width: 767px) {
            .hero { padding: 6rem 0 3rem; }
            .hero h1 { font-size: 2rem; }
            .hero p { font-size: 1rem; }
            .hero-stats { gap: 1.5rem; }
            .hero-stat h3 { font-size: 1.25rem; }
            .section-title { font-size: 1.75rem; }
            .cta-box { padding: 2.5rem 1.5rem; }
            .cta-box h2 { font-size: 1.5rem; }
            .phone-mockup { width: 220px; height: 440px; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-landing" id="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <span class="brand-icon"><i class="fas fa-comments"></i></span>
            ChatApp
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <i class="fas fa-bars" style="color: #fff;"></i>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-nav-login" href="login.php">Log In</a>
                </li>
                <li class="nav-item ms-2">
                    <a class="btn btn-nav-signup" href="pages/register.php">Sign Up Free</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <div class="hero-badge">
                    <span class="dot"></span>
                    Real-time messaging platform
                </div>
                <h1>
                    Chat Without<br>
                    <span class="gradient-text">Boundaries</span>
                </h1>
                <p>
                    Connect with friends, build communities, and share moments instantly.
                    A modern chat experience with groups, media sharing, and end-to-end security.
                </p>
                <div class="hero-buttons">
                    <a href="pages/register.php" class="btn btn-hero-primary">
                        Get Started Free <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#features" class="btn btn-hero-secondary">
                        <i class="fas fa-play"></i> See Features
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <h3>100%</h3>
                        <p>Free to use</p>
                    </div>
                    <div class="hero-stat">
                        <h3>Real-time</h3>
                        <p>Instant delivery</p>
                    </div>
                    <div class="hero-stat">
                        <h3>Secure</h3>
                        <p>Encrypted sessions</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 hero-visual d-none d-lg-block">
                <div class="phone-mockup">
                    <div class="phone-notch"></div>
                    <div class="phone-screen">
                        <div class="phone-header">
                            <h6><i class="fas fa-lock" style="font-size:0.65rem;"></i> Alice</h6>
                        </div>
                        <div class="phone-messages">
                            <div class="msg msg-received">Hey! Are you coming to the meetup tonight?</div>
                            <div class="msg msg-sent">Yes! Wouldn't miss it</div>
                            <div class="msg msg-received">Awesome! See you at 7pm</div>
                            <div class="msg msg-sent">Can't wait!</div>
                            <div class="msg msg-received">I'll share the location in the group</div>
                            <div class="typing-indicator">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features" id="features">
    <div class="container">
        <div class="text-center">
            <div class="section-label"><i class="fas fa-bolt"></i> Features</div>
            <h2 class="section-title">Everything You Need</h2>
            <p class="section-subtitle">Built for modern conversations with all the tools you love.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon purple"><i class="fas fa-comments"></i></div>
                    <h5>Personal Chat</h5>
                    <p>One-on-one conversations with real-time delivery, read receipts, typing indicators, and emoji support.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon green"><i class="fas fa-users"></i></div>
                    <h5>Group Chat</h5>
                    <p>Create groups with friends, assign roles like admin and moderator, and manage members easily.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon blue"><i class="fas fa-share-nodes"></i></div>
                    <h5>File Sharing</h5>
                    <p>Share images, videos, documents, and archives with drag-and-drop upload and instant previews.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon orange"><i class="fas fa-bell"></i></div>
                    <h5>Smart Notifications</h5>
                    <p>Stay informed with notifications for messages, friend requests, group invites, and @mentions.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon pink"><i class="fas fa-magnifying-glass"></i></div>
                    <h5>Global Search</h5>
                    <p>Find users, groups, and messages instantly with full-text search and recent search history.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon cyan"><i class="fas fa-shield-halved"></i></div>
                    <h5>Secure & Private</h5>
                    <p>Session fingerprinting, rate limiting, login lockout, and encrypted passwords keep you safe.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="how-it-works" id="how-it-works">
    <div class="container">
        <div class="text-center">
            <div class="section-label"><i class="fas fa-rocket"></i> How It Works</div>
            <h2 class="section-title">Get Started in 3 Steps</h2>
            <p class="section-subtitle">Start chatting in under a minute.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h5>Create Account</h5>
                    <p>Sign up with your email and choose a unique username. It takes just a few seconds.</p>
                    <div class="step-connector d-none d-md-block"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h5>Add Friends</h5>
                    <p>Share your friend code or search by username to connect with people you know.</p>
                    <div class="step-connector d-none d-md-block"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h5>Start Chatting</h5>
                    <p>Send messages, share files, create groups, and enjoy real-time conversations.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <div class="cta-box">
            <h2>Ready to Start Chatting?</h2>
            <p>Join and connect with friends today. Free forever, no credit card needed.</p>
            <a href="pages/register.php" class="btn btn-cta">
                Create Free Account <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="footer-brand">
                    <span class="brand-icon"><i class="fas fa-comments"></i></span>
                    ChatApp
                </div>
                <p>A modern real-time chat application built with PHP, MySQL, and vanilla JavaScript.</p>
            </div>
            <div class="col-lg-2 col-md-4 mb-4">
                <div class="footer-links">
                    <h6>Product</h6>
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="pages/register.php">Sign Up</a>
                    <a href="login.php">Log In</a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-4">
                <div class="footer-links">
                    <h6>Support</h6>
                    <a href="#">Help Center</a>
                    <a href="#">Contact Us</a>
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-4">
                <div class="footer-links">
                    <h6>Connect</h6>
                    <a href="#"><i class="fab fa-github me-2"></i>GitHub</a>
                    <a href="#"><i class="fab fa-twitter me-2"></i>Twitter</a>
                    <a href="#"><i class="fab fa-discord me-2"></i>Discord</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> ChatApp. All rights reserved.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-github"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-discord"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.addEventListener('scroll', function() {
    const navbar = document.getElementById('navbar');
    navbar.classList.toggle('scrolled', window.scrollY > 50);
});
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
</script>
</body>
</html>
