# ChatApp

A real-time chat application built with PHP, MySQL, and vanilla JavaScript. Features personal messaging, group chats, media sharing, admin panel, and a comprehensive security layer.

---

## Features

### Authentication & User Management
- User registration and login with "Remember Me" functionality
- Password reset via email
- Session management with fingerprint validation

### Dashboard
- User statistics and activity overview
- Friend code system for adding contacts
- Quick access to settings

### Friend System
- Search users by name or friend code
- Send, accept, reject, and cancel friend requests
- Remove friends and block/unblock users

### Personal Chat
- Real-time messaging with AJAX polling
- Emoji picker and message replies
- Delete messages (single/bulk)
- Typing indicators and message status (sent, delivered, read)

### Group Chat
- Create groups with name, description, and avatar
- Invite/remove members, leave groups
- Role-based permissions (Admin, Moderator, Member)
- @mention support with autocomplete

### Media Module
- Drag-and-drop file uploads (images, videos, documents)
- Thumbnail generation for previews
- Gallery view with lightbox
- File type validation and size limits

### Profile & Settings
- Avatar and cover photo upload
- Bio and personal information editing
- Theme switcher (dark/light)
- Password change, privacy controls, account deactivation

### Notification System
- Bell icon with unread count and dropdown
- Full notifications page with filtering
- Notification preferences (email, push, in-app)
- Mark as read / mark all as read

### Global Search
- Search across users, friends, groups, and messages
- Recent searches history
- Keyboard shortcut (⌘K / Ctrl+K) quick search

### Admin Panel
- Dashboard with charts (Chart.js)
- User management (view, edit, ban, delete)
- Group moderation
- Message monitoring
- Reports management
- Blocked users list
- Activity logs
- System statistics
- Application settings

### Security Layer
- CSRF token protection on all forms
- Rate limiting on API endpoints
- Login attempt lockout after failed attempts
- Password history tracking
- Session fingerprint validation
- Content Security Policy (CSP) headers

---

## Tech Stack

| Layer        | Technology                          |
|-------------|-------------------------------------|
| Backend     | PHP 8.2+ with mysqli (procedural)   |
| Database    | MySQL 8+                            |
| Frontend    | Bootstrap 5                         |
| JavaScript  | Vanilla JS with AJAX polling        |
| Charts      | Chart.js                            |
| UI Theme    | Dark professional, mobile responsive|

---

## Requirements

- **PHP** 8.2 or higher
- **MySQL** 8.0 or higher
- **XAMPP** / **WAMP** / **LAMP** stack (or any PHP-capable server)
- **Composer** (not required, but optional for autoloading)
- Web browser with JavaScript enabled

### PHP Extensions
- `mysqli`
- `mbstring`
- `gd` (for image processing)
- `fileinfo`
- `session`

---

## Installation

### 1. Clone or Download the Project

```bash
git clone https://github.com/yourusername/chatapp.git
```

Or download and extract the ZIP file to your web server root (e.g., `C:\xampp\htdocs\chatapp`).

### 2. Start Your Server

Start Apache and MySQL from XAMPP Control Panel (or your equivalent).

### 3. Create the Database

1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`)
2. Create a new database named `chatapp`
3. Import the schema:

```bash
# Via command line (adjust paths as needed)
mysql -u root -p chatapp < database_import.sql
```

Or in phpMyAdmin:
- Select the `chatapp` database
- Click **Import**
- Choose `database_import.sql`
- Click **Go**

### 4. Configure Database Connection

Edit `config/database.php` and update the connection details:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chatapp');
?>
```

### 5. Configure Session & Security

Review and adjust settings in:
- `config/session.php` — session lifetime, cookie settings
- `config/security.php` — CSRF, rate limiting, lockout thresholds
- `config/media.php` — upload limits, allowed file types

### 6. Set Directory Permissions

Ensure the following directories are writable by the web server:

```bash
chmod -R 775 storage/
chmod -R 775 storage/uploads/
chmod -R 775 storage/thumbnails/
chmod -R 775 storage/temp/
```

On Windows, ensure the IUSR/IIS_IUSRS or Everyone group has Modify permissions on the `storage` directory.

### 7. Access the Application

Open your browser and navigate to:

```
http://localhost/chatapp
```

---

## Default Credentials

| Account  | Username     | Password    |
|----------|--------------|-------------|
| Admin    | superadmin   | Admin@123   |
| Demo User| demo         | User@123    |

> **Important:** Change these credentials immediately after first login in a production environment.

---

## Project Structure

```
chatapp/
├── config/
│   ├── database.php          # Database connection configuration
│   ├── session.php           # Session and cookie settings
│   ├── security.php          # Security settings (CSRF, rate limiting)
│   └── media.php             # Media upload configuration
│
├── includes/
│   ├── functions.php         # Core helper functions
│   ├── compat.php            # PHP compatibility shims
│   ├── security.php          # Security class (CSRF, rate limiting, etc.)
│   ├── notification_helpers.php   # Notification utility functions
│   ├── notification_component.php # Notification UI components
│   ├── media_helpers.php     # Media handling utilities
│   ├── search_helpers.php    # Search utility functions
│   └── quick_search_component.php # Quick search UI component
│
├── api/
│   ├── login.php             # User login endpoint
│   ├── register.php          # User registration endpoint
│   ├── logout.php            # User logout endpoint
│   ├── send-message.php      # Send chat message
│   ├── get-messages.php      # Retrieve chat messages
│   ├── friends.php           # Friend management endpoints
│   ├── groups.php            # Group management endpoints
│   ├── media.php             # Media upload/retrieval endpoints
│   ├── notifications.php     # Notification endpoints
│   ├── search.php            # Search endpoints
│   ├── profile.php           # Profile update endpoints
│   ├── settings.php          # Settings update endpoints
│   ├── admin.php             # Admin API endpoints
│   └── ... (50+ endpoints)
│
├── pages/
│   ├── dashboard.php         # User dashboard
│   ├── chat.php              # Personal chat interface
│   ├── group-chat.php        # Group chat interface
│   ├── settings.php          # User settings page
│   ├── media.php             # Media gallery page
│   ├── notifications.php     # Notifications page
│   ├── search.php            # Search results page
│   └── profile.php           # User profile page
│
├── admin/
│   ├── index.php             # Admin panel entry/redirect
│   ├── dashboard.php         # Admin dashboard with charts
│   ├── users.php             # User management
│   ├── groups.php            # Group management
│   ├── messages.php          # Message monitoring
│   ├── reports.php           # Reports management
│   ├── blocked.php           # Blocked users list
│   ├── logs.php              # Activity logs
│   ├── statistics.php        # System statistics
│   └── settings.php          # Application settings
│
├── assets/
│   ├── css/
│   │   ├── style.css         # Base/global styles
│   │   ├── dashboard.css     # Dashboard styles
│   │   ├── friends.css       # Friends page styles
│   │   ├── chat.css          # Chat interface styles
│   │   ├── groups.css        # Group chat styles
│   │   ├── media.css         # Media gallery styles
│   │   ├── settings.css      # Settings page styles
│   │   ├── notifications.css # Notifications styles
│   │   ├── search.css        # Search page styles
│   │   └── admin.css         # Admin panel styles
│   │
│   └── js/
│       ├── app.js            # Core application logic
│       ├── dashboard.js      # Dashboard functionality
│       ├── friends.js        # Friend system logic
│       ├── chat.js           # Chat functionality
│       ├── group-chat.js     # Group chat logic
│       ├── media.js          # Media upload/gallery logic
│       ├── notifications.js  # Notification handling
│       ├── search.js         # Search functionality
│       └── admin.js          # Admin panel logic
│
├── storage/
│   ├── uploads/              # User-uploaded files
│   ├── thumbnails/           # Generated thumbnails
│   └── temp/                 # Temporary files
│
├── database_import.sql       # Complete database schema
├── index.php                 # Application entry point
└── README.md                 # This file
```

---

## API Documentation Overview

All API endpoints are located in the `/api` directory and accept/return JSON. Requests should include the CSRF token (obtained from the session) in the `X-CSRF-Token` header or as a POST parameter.

### Authentication Endpoints

| Method | Endpoint          | Description              |
|--------|-------------------|--------------------------|
| POST   | `api/login.php`   | Authenticate user        |
| POST   | `api/register.php`| Register new user        |
| GET    | `api/logout.php`  | Destroy session          |

### Chat Endpoints

| Method | Endpoint             | Description                    |
|--------|----------------------|--------------------------------|
| POST   | `api/send-message.php` | Send a message               |
| GET    | `api/get-messages.php` | Retrieve messages (polling)  |
| DELETE | `api/delete-message.php` | Delete a message           |
| POST   | `api/typing.php`       | Send typing indicator       |

### Friend Endpoints

| Method | Endpoint                   | Description              |
|--------|----------------------------|--------------------------|
| POST   | `api/friends.php?action=search`  | Search users      |
| POST   | `api/friends.php?action=request` | Send friend request|
| POST   | `api/friends.php?action=accept`  | Accept request     |
| POST   | `api/friends.php?action=reject`  | Reject request     |
| POST   | `api/friends.php?action=remove`  | Remove friend      |
| POST   | `api/friends.php?action=block`   | Block/unblock user |

### Group Endpoints

| Method | Endpoint                    | Description              |
|--------|-----------------------------|--------------------------|
| POST   | `api/groups.php?action=create`  | Create a group      |
| POST   | `api/groups.php?action=invite`  | Invite user to group |
| POST   | `api/groups.php?action=leave`   | Leave a group        |
| POST   | `api/groups.php?action=remove`  | Remove member        |
| POST   | `api/groups.php?action=message` | Send group message   |

### Media Endpoints

| Method | Endpoint             | Description              |
|--------|----------------------|--------------------------|
| POST   | `api/media.php?action=upload` | Upload a file    |
| GET    | `api/media.php?action=list`   | List media files |
| DELETE | `api/media.php?action=delete` | Delete a file    |

### Notification Endpoints

| Method | Endpoint                        | Description              |
|--------|---------------------------------|--------------------------|
| GET    | `api/notifications.php?action=list`     | List notifications |
| POST   | `api/notifications.php?action=read`     | Mark as read       |
| POST   | `api/notifications.php?action=read_all` | Mark all as read   |
| GET    | `api/notifications.php?action=count`    | Get unread count   |

### Search Endpoints

| Method | Endpoint              | Description              |
|--------|-----------------------|--------------------------|
| GET    | `api/search.php?q=term` | Global search          |
| GET    | `api/search.php?action=recent` | Recent searches |

### Profile & Settings Endpoints

| Method | Endpoint                   | Description              |
|--------|----------------------------|--------------------------|
| POST   | `api/profile.php?action=avatar`    | Update avatar   |
| POST   | `api/profile.php?action=cover`     | Update cover photo |
| POST   | `api/profile.php?action=bio`       | Update bio       |
| POST   | `api/settings.php?action=password` | Change password  |
| POST   | `api/settings.php?action=privacy`  | Update privacy   |
| POST   | `api/settings.php?action=theme`    | Switch theme     |

### Admin Endpoints

| Method | Endpoint                         | Description              |
|--------|----------------------------------|--------------------------|
| GET    | `api/admin.php?action=stats`     | Dashboard statistics     |
| GET    | `api/admin.php?action=users`     | List all users           |
| POST   | `api/admin.php?action=ban_user`  | Ban/unban user           |
| GET    | `api/admin.php?action=groups`    | List all groups          |
| GET    | `api/admin.php?action=reports`   | List reports             |
| GET    | `api/admin.php?action=logs`      | Activity logs            |

---

## Admin Panel Access

Navigate to `/admin` or `/admin/index.php` after logging in with an admin account.

The admin panel provides:

- **Dashboard** — Overview with user growth charts, message volume, and active sessions
- **Users** — View, search, edit, ban, or delete user accounts
- **Groups** — Monitor and moderate all groups
- **Messages** — View message history across the platform
- **Reports** — Review and resolve user reports
- **Blocked** — Manage the global block list
- **Logs** — View system activity and security logs
- **Statistics** — Detailed analytics and metrics
- **Settings** — Configure application-wide settings

---

## Security Features

| Feature                | Description                                                    |
|------------------------|----------------------------------------------------------------|
| CSRF Protection        | All forms include CSRF tokens; validated on every POST request |
| Rate Limiting          | API endpoints throttled to prevent abuse                       |
| Login Lockout          | Accounts locked after configurable failed attempt threshold    |
| Password History       | Prevents reuse of recent passwords                             |
| Session Fingerprint    | Sessions bound to browser/IP to prevent hijacking              |
| Content Security Policy| CSP headers restrict resource loading to trusted sources       |
| Input Validation       | All user input is sanitized and validated server-side          |
| File Upload Validation | MIME type checking, file size limits, and rename on upload     |
| SQL Injection Prevention| Parameterized queries via mysqli prepared statements           |
| XSS Prevention         | Output escaping on all rendered content                        |

---

## Configuration

### Database (`config/database.php`)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chatapp');
```

### Session (`config/session.php`)

Adjust session lifetime, cookie parameters, and secure cookie settings based on your environment (HTTP vs HTTPS).

### Security (`config/security.php`)

Configure rate limiting thresholds, lockout duration, CSRF token expiry, and CSP directives.

### Media (`config/media.php`)

Set maximum file upload size, allowed MIME types, thumbnail dimensions, and storage paths.

---

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -m 'Add your feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Open a Pull Request

### Guidelines

- Follow existing code conventions (procedural PHP, no OOP except the Security class)
- Test all changes against the existing API endpoints
- Ensure mobile responsiveness for any UI changes
- Update this README if adding new features or changing configuration

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## Support

For issues and questions, please open an issue on the GitHub repository or contact the development team.
#   C h a t a p p - U s i n g - P h p  
 