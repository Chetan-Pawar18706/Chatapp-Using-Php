# ChatApp API Documentation

## Base URL
```
http://localhost/chatapp/api
```

## Authentication
Most endpoints require authentication via session cookies or Bearer token in the Authorization header:
```
Authorization: Bearer <session_token>
```

## Rate Limiting
- **General endpoints**: 60 requests per minute per IP
- **Authentication endpoints**: 5 attempts per 15 minutes per IP
- **Password reset**: 3 requests per hour per IP

---

## Authentication Endpoints

### POST /login.php
User login with email and password.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| email | string | Yes | User email address |
| password | string | Yes | User password |

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 1,
        "username": "johndoe",
        "email": "john@example.com",
        "avatar": "uploads/avatars/1.jpg"
    },
    "token": "session_token_here"
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Invalid email or password"
}
```

**Rate Limit:** 5 attempts per 15 minutes

---

### POST /register.php
Register a new user account.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| username | string | Yes | Unique username (3-30 characters) |
| email | string | Yes | Valid email address |
| password | string | Yes | Password (min 8 characters) |
| password_confirmation | string | Yes | Password confirmation |

**Response:**
```json
{
    "success": true,
    "message": "Registration successful",
    "user": {
        "id": 1,
        "username": "johndoe",
        "email": "john@example.com"
    },
    "token": "session_token_here"
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Registration failed",
    "errors": {
        "email": "Email already exists",
        "username": "Username already taken"
    }
}
```

**Rate Limit:** 3 registrations per hour per IP

---

### POST /logout.php
Logout current user and destroy session.

**Parameters:** None

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

**Authentication Required:** Yes

---

### POST /forgot-password.php
Request a password reset email.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| email | string | Yes | Registered email address |

**Response:**
```json
{
    "success": true,
    "message": "If the email exists, a reset link has been sent"
}
```

**Rate Limit:** 3 requests per hour

---

### POST /reset-password.php
Reset password using token from email.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| token | string | Yes | Reset token from email |
| password | string | Yes | New password (min 8 characters) |
| password_confirmation | string | Yes | Password confirmation |

**Response:**
```json
{
    "success": true,
    "message": "Password reset successful"
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Invalid or expired token"
}
```

---

### GET /check-auth.php
Check if current user is authenticated.

**Parameters:** None

**Response (Authenticated):**
```json
{
    "authenticated": true,
    "user": {
        "id": 1,
        "username": "johndoe",
        "email": "john@example.com",
        "avatar": "uploads/avatars/1.jpg"
    }
}
```

**Response (Not Authenticated):**
```json
{
    "authenticated": false
}
```

---

## Friends Endpoints

### POST /add-friend.php
Send a friend request to another user.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | Target user ID |

**Response:**
```json
{
    "success": true,
    "message": "Friend request sent"
}
```

**Authentication Required:** Yes

**Error Responses:**
- `"Already friends"` - Users are already friends
- `"Friend request pending"` - Request already sent
- `"Cannot add yourself"` - Trying to add self
- `"User not found"` - Target user doesn't exist

---

### POST /send-friend-request.php
Alternative endpoint for sending friend requests.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | Target user ID |
| friend_code | string | No | User's friend code (alternative to user_id) |

**Response:**
```json
{
    "success": true,
    "message": "Friend request sent"
}
```

**Authentication Required:** Yes

---

### POST /accept-friend.php
Accept a received friend request.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| friendship_id | int | Yes | Friendship request ID |

**Response:**
```json
{
    "success": true,
    "message": "Friend request accepted"
}
```

**Authentication Required:** Yes

---

### POST /reject-friend.php
Reject a received friend request.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| friendship_id | int | Yes | Friendship request ID |

**Response:**
```json
{
    "success": true,
    "message": "Friend request rejected"
}
```

**Authentication Required:** Yes

---

### POST /respond-friend.php
Respond to a friend request (accept/reject).

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| friendship_id | int | Yes | Friendship request ID |
| action | string | Yes | "accept" or "reject" |

**Response:**
```json
{
    "success": true,
    "message": "Friend request accepted"
}
```

**Authentication Required:** Yes

---

### POST /cancel-request.php
Cancel a sent friend request.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| friendship_id | int | Yes | Friendship request ID |

**Response:**
```json
{
    "success": true,
    "message": "Friend request cancelled"
}
```

**Authentication Required:** Yes

---

### POST /remove-friend.php
Remove a friend from friends list.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | Friend's user ID |

**Response:**
```json
{
    "success": true,
    "message": "Friend removed"
}
```

**Authentication Required:** Yes

---

### POST /block-user.php
Block a user.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | User ID to block |

**Response:**
```json
{
    "success": true,
    "message": "User blocked"
}
```

**Authentication Required:** Yes

---

### POST /unblock-user.php
Unblock a previously blocked user.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | User ID to unblock |

**Response:**
```json
{
    "success": true,
    "message": "User unblocked"
}
```

**Authentication Required:** Yes

---

### GET /get-friends.php
Get list of current user's friends.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| search | string | No | Search friends by username |
| page | int | No | Page number (default: 1) |
| limit | int | No | Results per page (default: 20) |

**Response:**
```json
{
    "success": true,
    "friends": [
        {
            "id": 2,
            "username": "janedoe",
            "email": "jane@example.com",
            "avatar": "uploads/avatars/2.jpg",
            "is_online": true,
            "last_seen": "2026-07-24 10:30:00"
        }
    ],
    "total": 50,
    "page": 1,
    "total_pages": 3
}
```

**Authentication Required:** Yes

---

### GET /get-friend-requests.php
Get pending friend requests.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | No | "received" (default), "sent", or "all" |

**Response:**
```json
{
    "success": true,
    "requests": [
        {
            "id": 1,
            "user_id": 3,
            "username": "bobsmith",
            "avatar": "uploads/avatars/3.jpg",
            "status": "pending",
            "created_at": "2026-07-24 09:00:00"
        }
    ],
    "count": 5
}
```

**Authentication Required:** Yes

---

### GET /friend-requests.php
Alternative endpoint for getting friend requests.

**Parameters:** Same as `/get-friend-requests.php`

**Response:** Same as `/get-friend-requests.php`

**Authentication Required:** Yes

---

### GET /get-mutual-friends.php
Get mutual friends between current user and another user.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | Target user ID |

**Response:**
```json
{
    "success": true,
    "mutual_friends": [
        {
            "id": 4,
            "username": "alicebrown",
            "avatar": "uploads/avatars/4.jpg"
        }
    ],
    "count": 3
}
```

**Authentication Required:** Yes

---

## Messages Endpoints

### POST /send-message.php
Send a personal message to another user.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| receiver_id | int | Yes | Recipient user ID |
| content | string | Yes | Message content |
| reply_to_id | int | No | ID of message being replied to |

**Response:**
```json
{
    "success": true,
    "message": {
        "id": 123,
        "sender_id": 1,
        "receiver_id": 2,
        "content": "Hello!",
        "message_type": "text",
        "created_at": "2026-07-24 10:30:00"
    }
}
```

**Authentication Required:** Yes

**Error Responses:**
- `"You are blocked"` - Sender is blocked by recipient
- `"User not found"` - Recipient doesn't exist
- `"Cannot message yourself"` - Trying to message self

---

### POST /send-message-media.php
Send a message with media attachment.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| receiver_id | int | Yes | Recipient user ID |
| content | string | No | Message caption |
| media | file | Yes | File to upload (max 10MB) |
| reply_to_id | int | No | ID of message being replied to |

**Response:**
```json
{
    "success": true,
    "message": {
        "id": 124,
        "sender_id": 1,
        "receiver_id": 2,
        "content": "Check this out",
        "message_type": "media",
        "media": {
            "id": 45,
            "file_name": "abc123.jpg",
            "original_name": "photo.jpg",
            "file_path": "uploads/media/abc123.jpg",
            "file_type": "image/jpeg"
        },
        "created_at": "2026-07-24 10:35:00"
    }
}
```

**Authentication Required:** Yes

**Allowed File Types:** jpg, jpeg, png, gif, webp, mp4, pdf, doc, docx

---

### GET /get-messages.php
Get messages between current user and another user.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | Other user's ID |
| page | int | No | Page number (default: 1) |
| limit | int | No | Messages per page (default: 50) |
| before | timestamp | No | Get messages before this timestamp |

**Response:**
```json
{
    "success": true,
    "messages": [
        {
            "id": 123,
            "sender_id": 1,
            "receiver_id": 2,
            "content": "Hello!",
            "message_type": "text",
            "is_read": true,
            "reply_to": null,
            "media": null,
            "created_at": "2026-07-24 10:30:00"
        }
    ],
    "has_more": true,
    "total": 150
}
```

**Authentication Required:** Yes

---

### POST /delete-message.php
Delete a message (soft delete).

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| message_id | int | Yes | Message ID to delete |
| delete_for | string | No | "me" (default) or "everyone" |

**Response:**
```json
{
    "success": true,
    "message": "Message deleted"
}
```

**Authentication Required:** Yes

**Note:** "delete_for everyone" only works within 24 hours of sending.

---

### GET /get-typing.php
Get typing status for a chat.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | Chat partner's user ID |

**Response:**
```json
{
    "success": true,
    "is_typing": true,
    "last_typing_at": "2026-07-24 10:31:00"
}
```

**Authentication Required:** Yes

---

### POST /update-typing.php
Update current user's typing status.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | Chat partner's user ID |
| is_typing | bool | Yes | Typing status |

**Response:**
```json
{
    "success": true
}
```

**Authentication Required:** Yes

---

### POST /mark-read.php
Mark messages as read.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | Sender's user ID |
| message_ids | array | No | Specific message IDs (all if empty) |

**Response:**
```json
{
    "success": true,
    "marked_count": 5
}
```

**Authentication Required:** Yes

---

## Groups Endpoints

### POST /create-group.php
Create a new group chat.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| name | string | Yes | Group name (3-50 characters) |
| description | string | No | Group description |
| members | array | Yes | Array of user IDs to add |
| avatar | file | No | Group avatar image |

**Response:**
```json
{
    "success": true,
    "group": {
        "id": 1,
        "name": "Friends Chat",
        "description": "A group for friends",
        "avatar": "uploads/groups/1.jpg",
        "created_by": 1,
        "members_count": 5
    }
}
```

**Authentication Required:** Yes

---

### GET /get-groups.php
Get groups the current user belongs to.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| search | string | No | Search groups by name |
| page | int | No | Page number |
| limit | int | No | Results per page |

**Response:**
```json
{
    "success": true,
    "groups": [
        {
            "id": 1,
            "name": "Friends Chat",
            "avatar": "uploads/groups/1.jpg",
            "members_count": 5,
            "last_message": "Hey everyone!",
            "last_message_at": "2026-07-24 10:30:00",
            "unread_count": 3
        }
    ],
    "total": 10
}
```

**Authentication Required:** Yes

---

### GET /groups.php
Alternative endpoint for getting groups.

**Parameters:** Same as `/get-groups.php`

**Response:** Same as `/get-groups.php`

**Authentication Required:** Yes

---

### GET /group-info.php
Get detailed information about a group.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| group_id | int | Yes | Group ID |

**Response:**
```json
{
    "success": true,
    "group": {
        "id": 1,
        "name": "Friends Chat",
        "description": "A group for friends",
        "avatar": "uploads/groups/1.jpg",
        "created_by": 1,
        "created_at": "2026-07-20 08:00:00",
        "members": [
            {
                "id": 1,
                "username": "johndoe",
                "avatar": "uploads/avatars/1.jpg",
                "role": "admin",
                "joined_at": "2026-07-20 08:00:00"
            }
        ],
        "members_count": 5
    }
}
```

**Authentication Required:** Yes

---

### POST /invite-members.php
Invite users to a group.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| group_id | int | Yes | Group ID |
| user_ids | array | Yes | Array of user IDs to invite |

**Response:**
```json
{
    "success": true,
    "message": "Members invited successfully",
    "invited_count": 3
}
```

**Authentication Required:** Yes

**Permission:** Admin or moderator only

---

### POST /leave-group.php
Leave a group.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| group_id | int | Yes | Group ID |

**Response:**
```json
{
    "success": true,
    "message": "Left group successfully"
}
```

**Authentication Required:** Yes

**Note:** Group creators cannot leave; they must transfer ownership first.

---

### POST /remove-member.php
Remove a member from a group.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| group_id | int | Yes | Group ID |
| user_id | int | Yes | User ID to remove |

**Response:**
```json
{
    "success": true,
    "message": "Member removed"
}
```

**Authentication Required:** Yes

**Permission:** Admin only

---

### POST /update-role.php
Update a member's role in a group.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| group_id | int | Yes | Group ID |
| user_id | int | Yes | User ID |
| role | string | Yes | New role: "admin", "moderator", or "member" |

**Response:**
```json
{
    "success": true,
    "message": "Role updated"
}
```

**Authentication Required:** Yes

**Permission:** Admin only

---

### POST /send-group-message.php
Send a message to a group.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| group_id | int | Yes | Group ID |
| message | string | Yes | Message content |
| media | file | No | File attachment |
| reply_to | int | No | Message ID being replied to |

**Response:**
```json
{
    "success": true,
    "message": {
        "id": 456,
        "group_id": 1,
        "user_id": 1,
        "message": "Hello group!",
        "media": null,
        "reply_to": null,
        "created_at": "2026-07-24 10:30:00"
    }
}
```

**Authentication Required:** Yes

**Permission:** Group members only

---

### GET /get-group-messages.php
Get messages from a group.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| group_id | int | Yes | Group ID |
| page | int | No | Page number |
| limit | int | No | Messages per page |
| before | timestamp | No | Get messages before timestamp |

**Response:**
```json
{
    "success": true,
    "messages": [
        {
            "id": 456,
            "group_id": 1,
            "user_id": 1,
            "username": "johndoe",
            "avatar": "uploads/avatars/1.jpg",
            "message": "Hello group!",
            "media": null,
            "reply_to": null,
            "created_at": "2026-07-24 10:30:00"
        }
    ],
    "has_more": true
}
```

**Authentication Required:** Yes

---

### POST /delete-group-message.php
Delete a group message.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| message_id | int | Yes | Message ID |
| group_id | int | Yes | Group ID |

**Response:**
```json
{
    "success": true,
    "message": "Message deleted"
}
```

**Authentication Required:** Yes

**Permission:** Message author or group admin

---

### POST /mark-group-read.php
Mark group messages as read.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| group_id | int | Yes | Group ID |
| message_ids | array | No | Specific message IDs |

**Response:**
```json
{
    "success": true,
    "marked_count": 10
}
```

**Authentication Required:** Yes

---

## Media Endpoints

### POST /upload-media.php
Upload a media file.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| file | file | Yes | File to upload |
| category | string | No | "message", "profile", "group" (default: "message") |
| receiver_id | int | No | Recipient user ID (for message category) |
| group_id | int | No | Group ID (for group category) |

**Response:**
```json
{
    "success": true,
    "media": {
        "id": 45,
        "file_name": "abc123.jpg",
        "original_name": "photo.jpg",
        "file_path": "uploads/media/abc123.jpg",
        "thumbnail_path": "uploads/media/thumbnails/abc123.jpg",
        "file_size": 1024000,
        "file_type": "image/jpeg",
        "file_extension": "jpg"
    }
}
```

**Authentication Required:** Yes

**File Size Limit:** 10MB

**Allowed Types:** jpg, jpeg, png, gif, webp, mp4, pdf, doc, docx

---

### POST /upload-photo.php
Upload avatar or cover photo.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| photo | file | Yes | Image file |
| type | string | Yes | "avatar" or "cover" |

**Response:**
```json
{
    "success": true,
    "photo_url": "uploads/avatars/1_new.jpg"
}
```

**Authentication Required:** Yes

**Allowed Types:** jpg, jpeg, png, gif, webp

---

### GET /get-media.php
Get media files uploaded by user.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| category | string | No | Filter by category |
| page | int | No | Page number |
| limit | int | No | Results per page |

**Response:**
```json
{
    "success": true,
    "media": [
        {
            "id": 45,
            "file_name": "abc123.jpg",
            "original_name": "photo.jpg",
            "file_path": "uploads/media/abc123.jpg",
            "file_type": "image/jpeg",
            "file_size": 1024000,
            "category": "message",
            "created_at": "2026-07-24 10:30:00"
        }
    ],
    "total": 25
}
```

**Authentication Required:** Yes

---

### GET /download-media.php
Download a media file.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | int | Yes | Media ID |

**Response:** File download (binary)

**Authentication Required:** Yes

---

### GET /preview-media.php
Preview a media file (for images/videos).

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | int | Yes | Media ID |
| size | string | No | "thumb", "medium", "full" (default: "full") |

**Response:** File preview (binary)

**Authentication Required:** Yes

---

### POST /delete-media.php
Delete a media file.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | int | Yes | Media ID |

**Response:**
```json
{
    "success": true,
    "message": "Media deleted"
}
```

**Authentication Required:** Yes

**Permission:** Owner only

---

## Search Endpoints

### GET /global-search.php
Search across users, messages, and groups.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| q | string | Yes | Search query (min 2 characters) |
| type | string | No | "all", "users", "messages", "groups" (default: "all") |
| page | int | No | Page number |
| limit | int | No | Results per page |

**Response:**
```json
{
    "success": true,
    "results": {
        "users": [
            {
                "id": 2,
                "username": "janedoe",
                "avatar": "uploads/avatars/2.jpg",
                "type": "user"
            }
        ],
        "messages": [
            {
                "id": 123,
                "content": "Hello there!",
                "sender": "johndoe",
                "type": "message"
            }
        ],
        "groups": [
            {
                "id": 1,
                "name": "Friends Chat",
                "avatar": "uploads/groups/1.jpg",
                "type": "group"
            }
        ]
    },
    "total": 15
}
```

**Authentication Required:** Yes

---

### GET /search-users.php
Search for users.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| q | string | Yes | Search query |
| page | int | No | Page number |
| limit | int | No | Results per page |

**Response:**
```json
{
    "success": true,
    "users": [
        {
            "id": 2,
            "username": "janedoe",
            "email": "jane@example.com",
            "avatar": "uploads/avatars/2.jpg",
            "bio": "Hello world!",
            "is_online": true,
            "friend_status": "none"
        }
    ],
    "total": 10
}
```

**Authentication Required:** Yes

---

### GET /search-messages.php
Search messages by content.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| q | string | Yes | Search query |
| user_id | int | No | Search in chat with specific user |
| group_id | int | No | Search in specific group |
| page | int | No | Page number |

**Response:**
```json
{
    "success": true,
    "messages": [
        {
            "id": 123,
            "content": "Hello there!",
            "sender_id": 1,
            "sender_name": "johndoe",
            "chat_type": "personal",
            "chat_id": 2,
            "created_at": "2026-07-24 10:30:00"
        }
    ],
    "total": 25
}
```

**Authentication Required:** Yes

---

### GET /search-friends.php
Search within friends list.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| q | string | Yes | Search query |

**Response:**
```json
{
    "success": true,
    "friends": [
        {
            "id": 2,
            "username": "janedoe",
            "avatar": "uploads/avatars/2.jpg",
            "is_online": true
        }
    ]
}
```

**Authentication Required:** Yes

---

### GET|POST /recent-searches.php
Get or manage recent searches.

**GET Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| limit | int | No | Number of recent searches (default: 10) |

**GET Response:**
```json
{
    "success": true,
    "searches": [
        {
            "id": 1,
            "search_type": "user",
            "search_query": "john",
            "result_id": 2,
            "result_name": "johndoe",
            "created_at": "2026-07-24 10:30:00"
        }
    ]
}
```

**POST Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| search_type | string | Yes | "user", "message", "group" |
| search_query | string | Yes | Search query |
| result_id | int | No | Result ID |
| result_name | string | No | Result name |

**POST Response:**
```json
{
    "success": true,
    "message": "Search saved"
}
```

**Authentication Required:** Yes

---

## Notifications Endpoints

### GET /get-notifications.php
Get user notifications.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | int | No | Page number |
| limit | int | No | Results per page |
| unread_only | bool | No | Filter unread only |

**Response:**
```json
{
    "success": true,
    "notifications": [
        {
            "id": 1,
            "type": "friend_request",
            "title": "New Friend Request",
            "message": "John Doe sent you a friend request",
            "data": {
                "user_id": 1,
                "friendship_id": 5
            },
            "is_read": false,
            "created_at": "2026-07-24 10:30:00"
        }
    ],
    "unread_count": 5,
    "total": 50
}
```

**Authentication Required:** Yes

---

### POST /notification-actions.php
Mark notifications as read or delete them.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| action | string | Yes | "mark_read", "mark_all_read", "delete" |
| notification_ids | array | No | Specific notification IDs |

**Response:**
```json
{
    "success": true,
    "message": "Notifications updated",
    "affected_count": 5
}
```

**Authentication Required:** Yes

---

### GET|POST /notification-preferences.php
Get or update notification preferences.

**GET Response:**
```json
{
    "success": true,
    "preferences": {
        "friend_requests": true,
        "messages": true,
        "mentions": true,
        "group_invites": true,
        "group_messages": true,
        "system": true,
        "email_notifications": false,
        "push_notifications": true,
        "sound_enabled": true
    }
}
```

**POST Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| friend_requests | bool | No | Enable/disable friend request notifications |
| messages | bool | No | Enable/disable message notifications |
| mentions | bool | No | Enable/disable mention notifications |
| group_invites | bool | No | Enable/disable group invite notifications |
| group_messages | bool | No | Enable/disable group message notifications |
| system | bool | No | Enable/disable system notifications |
| email_notifications | bool | No | Enable/disable email notifications |
| push_notifications | bool | No | Enable/disable push notifications |
| sound_enabled | bool | No | Enable/disable notification sounds |

**POST Response:**
```json
{
    "success": true,
    "message": "Preferences updated"
}
```

**Authentication Required:** Yes

---

## Profile & Settings Endpoints

### POST /update-profile.php
Update user profile information.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| username | string | No | New username |
| bio | string | No | User bio (max 500 characters) |
| about | string | No | About section |
| avatar | file | No | New avatar image |
| cover_photo | file | No | New cover photo |

**Response:**
```json
{
    "success": true,
    "message": "Profile updated",
    "user": {
        "id": 1,
        "username": "johndoe",
        "bio": "Hello world!",
        "avatar": "uploads/avatars/1.jpg"
    }
}
```

**Authentication Required:** Yes

---

### POST /update-settings.php
Update user settings.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| theme | string | No | UI theme: "light", "dark", "auto" |
| language | string | No | Language code |
| timezone | string | No | Timezone identifier |
| settings | json | No | Additional settings object |

**Response:**
```json
{
    "success": true,
    "message": "Settings updated"
}
```

**Authentication Required:** Yes

---

### POST /change-password.php
Change user password.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| current_password | string | Yes | Current password |
| new_password | string | Yes | New password (min 8 characters) |
| new_password_confirmation | string | Yes | New password confirmation |

**Response:**
```json
{
    "success": true,
    "message": "Password changed successfully"
}
```

**Authentication Required:** Yes

**Error Response:**
```json
{
    "success": false,
    "message": "Current password is incorrect"
}
```

---

### POST /deactivate-account.php
Temporarily deactivate user account.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| password | string | Yes | Confirm password |

**Response:**
```json
{
    "success": true,
    "message": "Account deactivated. You can reactivate by logging in."
}
```

**Authentication Required:** Yes

---

### POST /delete-account.php
Permanently delete user account.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| password | string | Yes | Confirm password |
| confirmation | string | Yes | Type "DELETE" to confirm |

**Response:**
```json
{
    "success": true,
    "message": "Account deleted permanently"
}
```

**Authentication Required:** Yes

**Note:** This action is irreversible.

---

### GET /export-data.php
Export all user data (GDPR compliance).

**Parameters:** None

**Response:** JSON file download with all user data

**Authentication Required:** Yes

---

## Dashboard Endpoints

### GET /dashboard.php
Get dashboard statistics.

**Parameters:** None

**Response:**
```json
{
    "success": true,
    "stats": {
        "friends_count": 50,
        "pending_requests": 3,
        "unread_messages": 12,
        "groups_count": 5,
        "unread_notifications": 8,
        "total_messages_sent": 1250,
        "account_created": "2026-01-15"
    }
}
```

**Authentication Required:** Yes

---

### GET /profile.php
Get user profile information.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | No | User ID (default: current user) |

**Response:**
```json
{
    "success": true,
    "profile": {
        "id": 1,
        "username": "johndoe",
        "email": "john@example.com",
        "avatar": "uploads/avatars/1.jpg",
        "cover_photo": "uploads/covers/1.jpg",
        "bio": "Hello world!",
        "about": "I'm a developer",
        "is_online": true,
        "last_seen": "2026-07-24 10:30:00",
        "friends_count": 50,
        "mutual_friends": 5,
        "friend_status": "friends",
        "created_at": "2026-01-15"
    }
}
```

**Authentication Required:** Yes

---

### GET /chat-list.php
Get list of recent chats.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| page | int | No | Page number |
| limit | int | No | Results per page |

**Response:**
```json
{
    "success": true,
    "chats": [
        {
            "type": "personal",
            "user": {
                "id": 2,
                "username": "janedoe",
                "avatar": "uploads/avatars/2.jpg",
                "is_online": true
            },
            "last_message": {
                "content": "Hey!",
                "created_at": "2026-07-24 10:30:00",
                "is_read": false
            },
            "unread_count": 3
        },
        {
            "type": "group",
            "group": {
                "id": 1,
                "name": "Friends Chat",
                "avatar": "uploads/groups/1.jpg",
                "members_count": 5
            },
            "last_message": {
                "content": "Hello everyone!",
                "sender": "janedoe",
                "created_at": "2026-07-24 10:25:00",
                "is_read": true
            },
            "unread_count": 0
        }
    ]
}
```

**Authentication Required:** Yes

---

### GET /chat-user-info.php
Get detailed info about a chat partner.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | int | Yes | Chat partner's user ID |

**Response:**
```json
{
    "success": true,
    "user": {
        "id": 2,
        "username": "janedoe",
        "email": "jane@example.com",
        "avatar": "uploads/avatars/2.jpg",
        "cover_photo": "uploads/covers/2.jpg",
        "bio": "Hello world!",
        "about": "I'm a designer",
        "is_online": true,
        "last_seen": "2026-07-24 10:30:00",
        "friend_code": "JANE123",
        "mutual_friends_count": 5,
        "is_blocked": false,
        "is_blocking": false
    }
}
```

**Authentication Required:** Yes

---

### GET /recent-chats.php
Get recent chat conversations.

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| limit | int | No | Number of chats (default: 20) |

**Response:**
```json
{
    "success": true,
    "recent_chats": [
        {
            "user_id": 2,
            "username": "janedoe",
            "avatar": "uploads/avatars/2.jpg",
            "last_message": "Hey, how are you?",
            "last_message_time": "2026-07-24 10:30:00",
            "unread_count": 2
        }
    ]
}
```

**Authentication Required:** Yes

---

## Error Response Format

All error responses follow this format:

```json
{
    "success": false,
    "message": "Error description",
    "error_code": "ERROR_CODE",
    "errors": {}
}
```

### Common Error Codes
| Code | Description |
|------|-------------|
| UNAUTHORIZED | Authentication required |
| FORBIDDEN | Insufficient permissions |
| NOT_FOUND | Resource not found |
| VALIDATION_ERROR | Input validation failed |
| RATE_LIMITED | Too many requests |
| SERVER_ERROR | Internal server error |

---

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Rate Limited |
| 500 | Server Error |

---

## File Upload Guidelines

### Supported File Types
| Category | Extensions | Max Size |
|----------|------------|----------|
| Images | jpg, jpeg, png, gif, webp | 5MB |
| Videos | mp4 | 50MB |
| Documents | pdf, doc, docx | 10MB |

### Avatar/Cover Photos
- Supported: jpg, jpeg, png, gif, webp
- Max size: 5MB
- Recommended dimensions: 400x400 (avatar), 1200x400 (cover)

---

## WebSocket Events (Real-time)

For real-time features, connect to WebSocket endpoint:
```
ws://localhost/chatapp/ws
```

### Events
| Event | Direction | Description |
|-------|-----------|-------------|
| message.new | Receive | New message received |
| message.read | Receive | Message marked as read |
| typing.start | Send/Receive | User started typing |
| typing.stop | Send/Receive | User stopped typing |
| user.online | Receive | User came online |
| user.offline | Receive | User went offline |
| notification.new | Receive | New notification |
| friend.request | Receive | Friend request received |
| friend.accepted | Receive | Friend request accepted |
