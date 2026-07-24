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

### Profile and Settings
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
- Keyboard shortcut (Ctrl+K) quick search

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

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.2+ with mysqli (procedural) |
| Database | MySQL 8+ |
| Frontend | Bootstrap 5 |
| JavaScript | Vanilla JS with AJAX polling |
| Charts | Chart.js |
| UI Theme | Dark professional, mobile responsive |

---

## Requirements

- **PHP** 8.2 or higher
- **MySQL** 8.0 or higher
- **XAMPP** / **WAMP** / **LAMP** stack (or any PHP-capable server)
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

Or download and extract the ZIP file to your web server root.

### 2. Start Your Server

Start Apache and MySQL from XAMPP Control Panel (or your equivalent).

### 3. Create the Database

1. Open phpMyAdmin (`http://localhost/phpmyadmin`)
2. Create a new database named `chatapp`
3. Import the schema:

```bash
mysql -u root -p chatapp < database_import.sql
```

Or in phpMyAdmin:
- Select the `chatapp` database
- Click **Import**
- Choose `database_import.sql`
- Click **Go**

### 4. Configure Database Connection

Edit `config/database.php` and update the connection details with your own credentials.

### 5. Configure Session and Security

Review and adjust settings in:
- `config/session.php` - session lifetime, cookie settings
- `config/security.php` - CSRF, rate limiting, lockout thresholds
- `config/media.php` - upload limits, allowed file types

### 6. Set Directory Permissions

Ensure the following directories are writable by the web server.

### 7. Access the Application

Open your browser and navigate to:

```
http://localhost/chatapp
```

---

## API Documentation

All API endpoints are located in the `/api` directory and accept/return JSON. Requests should include the CSRF token in the `X-CSRF-Token` header or as a POST parameter.

### Authentication Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/login.php` | Authenticate user |
| POST | `api/register.php` | Register new user |
| GET | `api/logout.php` | Destroy session |

### Chat Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/send-message.php` | Send a message |
| GET | `api/get-messages.php` | Retrieve messages (polling) |
| DELETE | `api/delete-message.php` | Delete a message |
| POST | `api/typing.php` | Send typing indicator |

### Friend Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/friends.php?action=search` | Search users |
| POST | `api/friends.php?action=request` | Send friend request |
| POST | `api/friends.php?action=accept` | Accept request |
| POST | `api/friends.php?action=reject` | Reject request |
| POST | `api/friends.php?action=remove` | Remove friend |
| POST | `api/friends.php?action=block` | Block/unblock user |

### Group Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/groups.php?action=create` | Create a group |
| POST | `api/groups.php?action=invite` | Invite user to group |
| POST | `api/groups.php?action=leave` | Leave a group |
| POST | `api/groups.php?action=remove` | Remove member |
| POST | `api/groups.php?action=message` | Send group message |

### Media Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/media.php?action=upload` | Upload a file |
| GET | `api/media.php?action=list` | List media files |
| DELETE | `api/media.php?action=delete` | Delete a file |

### Notification Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `api/notifications.php?action=list` | List notifications |
| POST | `api/notifications.php?action=read` | Mark as read |
| POST | `api/notifications.php?action=read_all` | Mark all as read |
| GET | `api/notifications.php?action=count` | Get unread count |

### Search Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `api/search.php?q=term` | Global search |
| GET | `api/search.php?action=recent` | Recent searches |

### Profile and Settings Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/profile.php?action=avatar` | Update avatar |
| POST | `api/profile.php?action=cover` | Update cover photo |
| POST | `api/profile.php?action=bio` | Update bio |
| POST | `api/settings.php?action=password` | Change password |
| POST | `api/settings.php?action=privacy` | Update privacy |
| POST | `api/settings.php?action=theme` | Switch theme |

### Admin Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `api/admin.php?action=stats` | Dashboard statistics |
| GET | `api/admin.php?action=users` | List all users |
| POST | `api/admin.php?action=ban_user` | Ban/unban user |
| GET | `api/admin.php?action=groups` | List all groups |
| GET | `api/admin.php?action=reports` | List reports |
| GET | `api/admin.php?action=logs` | Activity logs |

---

## Admin Panel Access

Navigate to `/admin` or `/admin/index.php` after logging in with an admin account.

The admin panel provides:
- **Dashboard** - Overview with user growth charts, message volume, and active sessions
- **Users** - View, search, edit, ban, or delete user accounts
- **Groups** - Monitor and moderate all groups
- **Messages** - View message history across the platform
- **Reports** - Review and resolve user reports
- **Blocked** - Manage the global block list
- **Logs** - View system activity and security logs
- **Statistics** - Detailed analytics and metrics
- **Settings** - Configure application-wide settings

---

## Security Features

| Feature | Description |
| --- | --- |
| CSRF Protection | All forms include CSRF tokens; validated on every POST request |
| Rate Limiting | API endpoints throttled to prevent abuse |
| Login Lockout | Accounts locked after configurable failed attempt threshold |
| Password History | Prevents reuse of recent passwords |
| Session Fingerprint | Sessions bound to browser/IP to prevent hijacking |
| Content Security Policy | CSP headers restrict resource loading to trusted sources |
| Input Validation | All user input is sanitized and validated server-side |
| File Upload Validation | MIME type checking, file size limits, and rename on upload |
| SQL Injection Prevention | Parameterized queries via mysqli prepared statements |
| XSS Prevention | Output escaping on all rendered content |

---

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

### Guidelines
- Follow existing code conventions (procedural PHP, no OOP except the Security class)
- Test all changes against the existing API endpoints
- Ensure mobile responsiveness for any UI changes

---

## License

This project is licensed under the MIT License.
#   C h a t a p p - U s i n g - P h p  
 #   C h a t a p p - U s i n g - P h p  
 