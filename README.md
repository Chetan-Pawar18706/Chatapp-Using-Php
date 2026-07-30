# ChatApp

A modern real-time chat application built with PHP, MySQL, and vanilla JavaScript. Features personal messaging, group chats, stories, polls, voice messages, live location, streaks, media sharing, admin panel, and a comprehensive security layer.

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
- Instagram-style live search suggestions with privacy controls

### Personal Chat
- Real-time messaging with AJAX polling
- Emoji picker and message replies
- Delete messages (for me / for everyone)
- Typing indicators and message status (sent, delivered, read)
- Auto-delete messages (View Once / 12 Hours)
- Save important messages (Snapchat-style bookmark)

### Group Chat
- Create groups with name, description, and avatar
- Invite/remove members, leave groups
- Role-based permissions (Admin, Moderator, Member)
- @mention support with autocomplete

### Stories / Status
- Share photos, videos, or text status
- 24-hour auto-disappearing stories
- Color picker for text stories
- Viewers list with view counts
- Instagram-style story viewer with progress bar

### Polls in Chat
- Create polls with multiple options
- Single or multiple choice voting
- Anonymous voting option
- Configurable expiry time (1h, 6h, 12h, 24h, 2d, 7d)
- Real-time vote counts and percentages

### Voice Messages
- Record audio directly from browser
- Play/pause with progress bar
- Duration display
- Microphone permission handling

### Live Location Sharing
- Share real-time GPS location
- Configurable duration (1h, 6h, 12h, 24h)
- Works in both 1-on-1 and group chats
- Automatic expiry

### Snap Streaks
- Daily messaging streaks with friends
- Fire emoji streak counter
- Streak freeze protection
- Streak history tracking

### Auto-Delete Messages
- **View Once**: Delete immediately after receiver sees
- **12 Hours**: Delete 12 hours after receiver reads
- Based on read time (seen_at), not sent time
- Saved messages are exempt from auto-delete

### Media Module
- Drag-and-drop file uploads (images, videos, documents)
- Thumbnail generation for previews
- Gallery view with lightbox
- File type validation and size limits

### Profile and Settings
- Avatar and cover photo upload
- Bio and personal information editing
- Theme switcher (Dark, Light, Midnight, Ocean)
- Password change, privacy controls, account deactivation

### Privacy Controls
- **Who Can Message You**: Everyone / Friends / Nobody
- **Who Can See Your Online Status**: Everyone / Friends / Nobody
- **Who Can See Your Profile**: Everyone / Friends / Nobody
- **Who Can Find You in Search**: Everyone / Friends / Hide
- Show/hide read receipts and typing indicators

### Notification System
- Bell icon with unread count and dropdown
- Full notifications page with filtering
- Notification preferences (email, push, in-app)
- Mark as read / mark all as read

### Global Search
- Instagram-style live search dropdown
- Friends shown first with badge
- Privacy-aware search visibility
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
mysql -u root -p chatapp < database_combined.sql
```

Or in phpMyAdmin:
- Select the `chatapp` database
- Click **Import**
- Choose `database_combined.sql`
- Click **Go**

### 4. Configure Database Connection

Edit `config/database.php` and update the connection details with your own credentials.

### 5. Configure Session and Security

Review and adjust settings in:
- `config/session.php` - session lifetime, cookie settings
- `config/security.php` - CSRF, rate limiting, lockout thresholds
- `config/media.php` - upload limits, allowed file types

### 6. Set Directory Permissions

Ensure the following directories are writable by the web server:
```
storage/uploads/
storage/uploads/images/
storage/uploads/videos/
storage/uploads/documents/
storage/uploads/avatars/
storage/uploads/stories/
storage/uploads/voice/
storage/thumbnails/
```

### 7. Access the Application

Open your browser and navigate to:

```
http://localhost/chatapp
```

---

## Pages

| Page | URL | Description |
| --- | --- | --- |
| Landing | `index.php` | Welcome page with features overview |
| Login | `login.php` | User login |
| Register | `pages/register.php` | New user registration |
| Dashboard | `pages/dashboard.php` | Main dashboard with stats |
| Stories | `pages/stories.php` | View and create stories |
| Chat | `pages/chat.php` | Personal 1-on-1 chat |
| Group Chat | `pages/group-chat.php` | Group conversations |
| Friends | `pages/friends.php` | Friends list |
| Requests | `pages/requests.php` | Friend requests |
| Search | `pages/search.php` | Global search |
| Media | `pages/media.php` | Media gallery |
| Notifications | `pages/notifications.php` | Notifications page |
| Settings | `pages/settings.php` | Account settings |
| Profile | `pages/profile.php` | User profile |
| About | `pages/about.php` | About ChatApp |
| Terms | `pages/terms.php` | Privacy Policy & Terms |
| Forgot Password | `pages/forgot-password.php` | Password reset request |
| Reset Password | `pages/reset-password.php` | Password reset form |
| Admin Panel | `admin/index.php` | Admin dashboard |

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
| POST | `api/mark-read.php` | Mark messages as read |
| POST | `api/save-message.php` | Save/unsave a message |

### Group Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/groups.php?action=create` | Create a group |
| POST | `api/groups.php?action=invite` | Invite user to group |
| POST | `api/groups.php?action=leave` | Leave a group |
| POST | `api/groups.php?action=remove` | Remove member |
| POST | `api/send-group-message.php` | Send group message |
| GET | `api/get-group-messages.php` | Get group messages |

### Stories Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/create-story.php` | Create a story |
| GET | `api/get-stories.php` | Get stories feed |
| POST | `api/view-story.php` | Mark story as viewed |
| POST | `api/delete-story.php` | Delete a story |

### Polls Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/create-poll.php` | Create a poll |
| GET | `api/get-poll.php?id=X` | Get poll details |
| POST | `api/vote-poll.php` | Vote on a poll |

### Voice Message Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/upload-voice.php` | Upload voice message |

### Location Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/share-location.php` | Share live location |
| GET | `api/get-location.php` | Get shared locations |

### Streaks Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `api/get-streaks.php` | Get user streaks |
| POST | `api/update-streak.php` | Update streak on message |
| POST | `api/freeze-streak.php` | Buy streak freeze |

### Friend Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/friends.php?action=search` | Search users |
| POST | `api/friends.php?action=request` | Send friend request |
| POST | `api/friends.php?action=accept` | Accept request |
| POST | `api/friends.php?action=reject` | Reject request |
| POST | `api/friends.php?action=remove` | Remove friend |
| POST | `api/friends.php?action=block` | Block/unblock user |

### Media Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/upload-media.php` | Upload a file |
| GET | `api/media.php?action=list` | List media files |
| DELETE | `api/media.php?action=delete` | Delete a file |

### Search Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `api/search-suggestions.php?q=term` | Live search suggestions |
| GET | `api/global-search.php?q=term` | Global search |
| GET | `api/search-users.php?q=term` | Search users |

### Notification Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `api/notifications.php?action=list` | List notifications |
| POST | `api/notifications.php?action=read` | Mark as read |
| POST | `api/notifications.php?action=read_all` | Mark all as read |
| GET | `api/notifications.php?action=count` | Get unread count |

### Profile and Settings Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `api/upload-photo.php` | Update avatar/cover |
| POST | `api/update-settings.php` | Update all settings |
| POST | `api/update-profile.php` | Update profile info |

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

## Database Schema

The application uses 30+ database tables including:

| Table | Purpose |
| --- | --- |
| `users` | User accounts |
| `messages` | Direct messages |
| `group_messages` | Group messages |
| `friendships` | Friend relationships |
| `groups` | Chat groups |
| `group_members` | Group membership |
| `media` | Uploaded files |
| `stories` | User stories/status |
| `story_views` | Story view tracking |
| `polls` | Chat polls |
| `poll_options` | Poll options |
| `poll_votes` | Poll votes |
| `voice_messages` | Voice message data |
| `live_locations` | Shared locations |
| `streaks` | Messaging streaks |
| `saved_messages` | Saved/bookmarked messages |
| `notifications` | User notifications |
| `message_reactions` | Message reactions |
| `block_list` | Blocked users |
| `chat_locks` | Locked chats |

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

---

## Credits

Built with ❤️ using PHP, MySQL, Bootstrap, and vanilla JavaScript.
#   C h a t a p p - U s i n g - P h p  
 