<?php
/**
 * =====================================================
 * Privacy Policy & Terms of Service
 * ChatApp - Legal Pages
 * =====================================================
 */
define('APP_RUNNING', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy & Terms of Service - ChatApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #0a0a0f; color: #e0e0e0; font-family: 'Inter', sans-serif; }
        .terms-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .terms-card { background: #12121a; border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 40px; margin-bottom: 30px; }
        .terms-card h1 { color: #fff; font-size: 28px; margin-bottom: 8px; }
        .terms-card h2 { color: #00d4aa; font-size: 20px; margin-top: 30px; margin-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px; }
        .terms-card h3 { color: #ccc; font-size: 16px; margin-top: 20px; margin-bottom: 8px; }
        .terms-card p, .terms-card li { color: #b0b0b0; line-height: 1.8; font-size: 14px; }
        .terms-card ul { padding-left: 20px; }
        .terms-card li { margin-bottom: 6px; }
        .terms-card .subtitle { color: #888; font-size: 14px; margin-bottom: 24px; }
        .terms-card a { color: #00d4aa; text-decoration: none; }
        .terms-card a:hover { text-decoration: underline; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #00d4aa; text-decoration: none; margin-bottom: 20px; font-size: 14px; }
        .back-link:hover { text-decoration: underline; color: #00ffcc; }
        .highlight-box { background: rgba(0,212,170,0.08); border: 1px solid rgba(0,212,170,0.2); border-radius: 8px; padding: 16px; margin: 16px 0; }
        .highlight-box p { color: #00d4aa; margin: 0; }
        .last-updated { color: #666; font-size: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="terms-container">
        <a href="javascript:history.back()" class="back-link">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>

        <!-- Privacy Policy -->
        <div class="terms-card" id="privacy">
            <h1><i class="fas fa-shield-halved"></i> Privacy Policy</h1>
            <p class="last-updated">Last Updated: <?php echo date('F d, Y'); ?></p>
            <p class="subtitle">Your privacy is important to us. This policy explains how ChatApp collects, uses, and protects your information.</p>

            <h2>1. Information We Collect</h2>
            <h3>Account Information</h3>
            <ul>
                <li>Username and email address (required for registration)</li>
                <li>Profile picture and bio (optional)</li>
                <li>Password (stored securely using bcrypt hashing)</li>
            </ul>

            <h3>Messages & Content</h3>
            <ul>
                <li>All messages are <strong>end-to-end encrypted</strong> using AES-256-CBC encryption</li>
                <li>Messages are stored in encrypted form in our database</li>
                <li>Only you and the recipient can read your messages</li>
                <li>We cannot access or read your encrypted messages</li>
            </ul>

            <h3>Usage Data</h3>
            <ul>
                <li>Login/logout activity and timestamps</li>
                <li>IP address and browser information (for security purposes)</li>
                <li>Online status and last seen timestamp</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <ul>
                <li>To provide and maintain the ChatApp service</li>
                <li>To enable communication between users</li>
                <li>To ensure security and prevent unauthorized access</li>
                <li>To improve our service and user experience</li>
                <li>To send important service-related notifications</li>
            </ul>

            <h2>3. Data Protection</h2>
            <div class="highlight-box">
                <p><i class="fas fa-lock"></i> <strong>Message Encryption:</strong> All messages are encrypted using AES-256-CBC before being stored in the database. Your messages remain private and secure.</p>
            </div>
            <ul>
                <li>We use industry-standard encryption for message storage</li>
                <li>Passwords are hashed using bcrypt and never stored in plain text</li>
                <li>CSRF protection on all form submissions</li>
                <li>Session-based authentication with secure cookie settings</li>
                <li>Rate limiting to prevent abuse</li>
            </ul>

            <h2>4. Data Sharing</h2>
            <ul>
                <li>We do <strong>not</strong> sell your personal information to third parties</li>
                <li>We do <strong>not</strong> share your messages with anyone</li>
                <li>We may disclose information only if required by law</li>
                <li>We may share data with service providers who assist in operating our platform (under strict confidentiality)</li>
            </ul>

            <h2>5. Data Retention</h2>
            <ul>
                <li>Your account data is retained as long as your account is active</li>
                <li>Messages are retained until you choose to delete them</li>
                <li>Activity logs are retained for security auditing purposes</li>
                <li>You may request deletion of your account and data at any time</li>
            </ul>

            <h2>6. Your Rights</h2>
            <ul>
                <li>Access your personal data</li>
                <li>Correct inaccurate data</li>
                <li>Request deletion of your account and data</li>
                <li>Export your data</li>
                <li>Object to processing of your data</li>
            </ul>

            <h2>7. Cookies</h2>
            <p>ChatApp uses session cookies to maintain your login state. These cookies are essential for the service to function and are not used for tracking or advertising purposes.</p>

            <h2>8. Children's Privacy</h2>
            <p>ChatApp is not intended for users under the age of 13. We do not knowingly collect information from children under 13.</p>

            <h2>9. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of any significant changes by posting the new policy on this page with an updated "Last Updated" date.</p>

            <h2>10. Contact Us</h2>
            <p>If you have any questions about this Privacy Policy, please contact us through the ChatApp support channel.</p>
        </div>

        <!-- Terms of Service -->
        <div class="terms-card" id="terms">
            <h1><i class="fas fa-file-contract"></i> Terms of Service</h1>
            <p class="last-updated">Last Updated: <?php echo date('F d, Y'); ?></p>
            <p class="subtitle">Please read these terms carefully before using ChatApp.</p>

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing or using ChatApp, you agree to be bound by these Terms of Service. If you do not agree to these terms, you may not use the service.</p>

            <h2>2. Description of Service</h2>
            <p>ChatApp is a messaging platform that allows users to communicate with each other through direct messages and group chats. The service includes features such as:</p>
            <ul>
                <li>Direct messaging between friends</li>
                <li>Group chat creation and participation</li>
                <li>Media sharing (images, videos, files)</li>
                <li>User profiles and friend management</li>
                <li>Chat lock for privacy</li>
            </ul>

            <h2>3. User Accounts</h2>
            <ul>
                <li>You must be at least 13 years old to create an account</li>
                <li>You are responsible for maintaining the security of your account</li>
                <li>You must provide accurate and complete registration information</li>
                <li>One account per person; duplicate accounts are not permitted</li>
                <li>You must not share your account credentials with others</li>
            </ul>

            <h2>4. User Conduct</h2>
            <h3>You agree NOT to:</h3>
            <ul>
                <li>Harass, bully, or intimidate other users</li>
                <li>Send spam, chain letters, or unsolicited messages</li>
                <li>Upload malicious content or viruses</li>
                <li>Impersonate another person or entity</li>
                <li>Violate any applicable laws or regulations</li>
                <li>Attempt to gain unauthorized access to other accounts or systems</li>
                <li>Use the service for any illegal purpose</li>
                <li>Share content that is offensive, harmful, or inappropriate</li>
                <li>Automate or bot access to the service without permission</li>
            </ul>

            <h2>5. Content Ownership</h2>
            <ul>
                <li>You retain ownership of content you send through ChatApp</li>
                <li>By sending content, you grant us a limited license to store and deliver that content</li>
                <li>You are responsible for the content you share</li>
                <li>We reserve the right to remove content that violates these terms</li>
            </ul>

            <h2>6. Privacy & Encryption</h2>
            <div class="highlight-box">
                <p><i class="fas fa-lock"></i> <strong>Your messages are private.</strong> All messages are encrypted using AES-256-CBC encryption. We cannot read your messages.</p>
            </div>
            <ul>
                <li>See our <a href="#privacy">Privacy Policy</a> for details on data collection and use</li>
                <li>Chat lock feature adds an extra layer of privacy for sensitive conversations</li>
                <li>You are responsible for maintaining the security of your account</li>
            </ul>

            <h2>7. Termination</h2>
            <ul>
                <li>We may suspend or terminate your account for violations of these terms</li>
                <li>You may delete your account at any time through account settings</li>
                <li>Upon termination, your data will be deleted in accordance with our Privacy Policy</li>
            </ul>

            <h2>8. Disclaimer of Warranties</h2>
            <p>ChatApp is provided "as is" without warranties of any kind. We do not guarantee that the service will be uninterrupted, secure, or error-free.</p>

            <h2>9. Limitation of Liability</h2>
            <p>To the maximum extent permitted by law, ChatApp shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of the service.</p>

            <h2>10. Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. Continued use of the service after changes constitutes acceptance of the new terms.</p>

            <h2>11. Governing Law</h2>
            <p>These terms shall be governed by and construed in accordance with applicable laws.</p>

            <h2>12. Contact</h2>
            <p>For questions about these Terms of Service, please contact us through the ChatApp support channel.</p>
        </div>

        <div style="text-align: center; padding: 20px 0; color: #555; font-size: 12px;">
            <p>&copy; <?php echo date('Y'); ?> ChatApp. All rights reserved.</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
