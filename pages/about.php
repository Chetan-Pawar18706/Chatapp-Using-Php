<?php
/**
 * =====================================================
 * About Page
 * ChatApp - About Us
 * =====================================================
 */
define('APP_RUNNING', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - ChatApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border: #334155;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .about-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
            color: #818cf8;
        }

        .hero-section {
            text-align: center;
            padding: 60px 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 50px;
        }

        .hero-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
            color: white;
        }

        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #f1f5f9 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-section p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .section {
            margin-bottom: 50px;
        }

        .section h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-primary);
        }

        .section p {
            color: var(--text-secondary);
            margin-bottom: 16px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 20px;
            color: var(--primary);
        }

        .feature-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .feature-card p {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0;
        }

        .tech-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .tech-badge {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tech-badge i {
            color: var(--primary);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .stat-box {
            text-align: center;
            padding: 20px;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .team-section {
            text-align: center;
            padding: 40px 0;
        }

        .team-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 36px;
            color: white;
            font-weight: 700;
        }

        .team-name {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .team-role {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .cta-box {
            text-align: center;
            background: linear-gradient(135deg, rgba(99,102,241,0.1) 0%, rgba(139,92,246,0.1) 100%);
            border: 1px solid rgba(99,102,241,0.3);
            border-radius: 16px;
            padding: 40px;
            margin-top: 50px;
        }

        .cta-box h2 {
            font-size: 1.5rem;
            margin-bottom: 12px;
        }

        .cta-box p {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .btn-cta {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-cta:hover {
            background: var(--primary-hover);
            color: white;
            transform: translateY(-2px);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid var(--border);
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .hero-section h1 { font-size: 1.8rem; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="about-container">
        <a href="javascript:history.back()" class="back-link">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>

        <!-- Hero -->
        <div class="hero-section">
            <div class="hero-icon">
                <i class="fas fa-comments"></i>
            </div>
            <h1>About ChatApp</h1>
            <p>A modern real-time chat application designed to bring people together. Built with love using PHP, MySQL, and vanilla JavaScript.</p>
        </div>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-number">25+</div>
                <div class="stat-label">Database Tables</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">50+</div>
                <div class="stat-label">API Endpoints</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">10+</div>
                <div class="stat-label">Pages</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">100%</div>
                <div class="stat-label">Free & Open Source</div>
            </div>
        </div>

        <!-- Mission -->
        <div class="section">
            <h2><i class="fas fa-bullseye" style="color: var(--primary);"></i> Our Mission</h2>
            <p>ChatApp was created with a simple goal: make communication faster, easier, and more fun. Whether you're chatting with friends, collaborating with teams, or sharing moments through stories, ChatApp provides everything you need in one beautiful interface.</p>
            <p>We believe everyone deserves access to modern communication tools without paying premium prices. That's why ChatApp is completely free and open source.</p>
        </div>

        <!-- Features -->
        <div class="section">
            <h2><i class="fas fa-star" style="color: #f59e0b;"></i> Key Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-message"></i></div>
                    <h3>Real-time Chat</h3>
                    <p>Send and receive messages instantly with typing indicators and read receipts.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h3>Group Chat</h3>
                    <p>Create groups, invite friends, and manage conversations with roles and permissions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-circle-play"></i></div>
                    <h3>Stories</h3>
                    <p>Share photos, videos, and text status that disappear after 24 hours.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                    <h3>Polls</h3>
                    <p>Create polls in chats, let friends vote, and see real-time results.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-microphone"></i></div>
                    <h3>Voice Messages</h3>
                    <p>Record and send voice notes when typing isn't convenient.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-location-dot"></i></div>
                    <h3>Live Location</h3>
                    <p>Share your real-time location with friends for meetups.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-fire"></i></div>
                    <h3>Streaks</h3>
                    <p>Keep the conversation going daily and build streaks with friends.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bookmark"></i></div>
                    <h3>Save Messages</h3>
                    <p>Save important messages to keep them forever, even with auto-delete on.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                    <h3>Privacy Controls</h3>
                    <p>Control who can message you, see your status, or find you in search.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-photo-film"></i></div>
                    <h3>Media Sharing</h3>
                    <p>Share images, videos, documents with thumbnail previews and gallery.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-lock"></i></div>
                    <h3>Chat Lock</h3>
                    <p>Lock specific chats with a password for extra privacy.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-palette"></i></div>
                    <h3>Themes</h3>
                    <p>Choose from Dark, Light, Midnight, or Ocean themes.</p>
                </div>
            </div>
        </div>

        <!-- Tech Stack -->
        <div class="section">
            <h2><i class="fas fa-code" style="color: #22c55e;"></i> Tech Stack</h2>
            <div class="tech-stack">
                <div class="tech-badge"><i class="fab fa-php"></i> PHP 8.2+</div>
                <div class="tech-badge"><i class="fas fa-database"></i> MySQL 8+</div>
                <div class="tech-badge"><i class="fab fa-bootstrap"></i> Bootstrap 5</div>
                <div class="tech-badge"><i class="fab fa-js"></i> Vanilla JavaScript</div>
                <div class="tech-badge"><i class="fas fa-chart-line"></i> Chart.js</div>
                <div class="tech-badge"><i class="fab fa-font-awesome"></i> Font Awesome 6</div>
                <div class="tech-badge"><i class="fas fa-palette"></i> CSS3</div>
                <div class="tech-badge"><i class="fab fa-html5"></i> HTML5</div>
            </div>
        </div>

        <!-- Security -->
        <div class="section">
            <h2><i class="fas fa-shield-halved" style="color: #ef4444;"></i> Security</h2>
            <p>Security is our top priority. ChatApp includes:</p>
            <ul style="color: var(--text-secondary); padding-left: 20px;">
                <li>CSRF token protection on all forms</li>
                <li>Rate limiting on API endpoints</li>
                <li>Login attempt lockout after failed attempts</li>
                <li>Password history tracking</li>
                <li>Session fingerprint validation</li>
                <li>Content Security Policy (CSP) headers</li>
                <li>SQL injection prevention with prepared statements</li>
                <li>XSS prevention with output escaping</li>
                <li>File upload validation with MIME checking</li>
            </ul>
        </div>

        <!-- CTA -->
        <div class="cta-box">
            <h2>Ready to Start Chatting?</h2>
            <p>Join ChatApp today and connect with friends. Free forever, no credit card needed.</p>
            <a href="register.php" class="btn-cta">
                Get Started <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Footer Links -->
        <div class="footer-links">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="terms.php">Privacy Policy</a>
            <a href="terms.php#terms">Terms of Service</a>
            <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>
