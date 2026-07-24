# ChatApp Database ER Diagram

## Entity-Relationship Diagram

```mermaid
erDiagram
    users {
        int id PK
        varchar username
        varchar email
        varchar password
        varchar friend_code
        varchar avatar
        varchar cover_photo
        text bio
        text about
        boolean is_online
        varchar user_status
        varchar theme
        json settings
        varchar timezone
        varchar language
        boolean is_active
        timestamp deactivated_at
        timestamp last_seen
        timestamp last_password_change
        varchar reset_token
        timestamp reset_token_expires
        varchar remember_token
        boolean email_verified
        varchar verification_token
        varchar status
        timestamp created_at
        timestamp updated_at
    }

    friendships {
        int id PK
        int user_id FK
        int friend_id FK
        varchar status
        timestamp created_at
        timestamp updated_at
    }

    messages {
        int id PK
        int sender_id FK
        int receiver_id FK
        int group_id FK
        text content
        varchar message_type
        int reply_to_id FK
        int media_id FK
        boolean is_read
        boolean is_deleted
        boolean deleted_for_sender
        boolean deleted_for_receiver
        timestamp delivered_at
        timestamp seen_at
        timestamp created_at
        timestamp updated_at
    }

    groups {
        int id PK
        varchar name
        text description
        varchar avatar
        int created_by FK
        varchar status
        timestamp created_at
        timestamp updated_at
    }

    group_members {
        int id PK
        int group_id FK
        int user_id FK
        varchar role
        timestamp joined_at
    }

    group_messages {
        int id PK
        int group_id FK
        int user_id FK
        text message
        int media_id FK
        int reply_to FK
        timestamp created_at
    }

    media {
        int id PK
        int user_id FK
        varchar file_name
        varchar original_name
        varchar file_path
        varchar thumbnail_path
        bigint file_size
        varchar file_type
        varchar file_extension
        varchar category
        int receiver_id FK
        int group_id FK
        timestamp created_at
    }

    notifications {
        int id PK
        int user_id FK
        int sender_id FK
        varchar type
        varchar title
        text message
        json data
        boolean is_read
        timestamp read_at
        timestamp created_at
    }

    notification_preferences {
        int id PK
        int user_id FK UK
        boolean friend_requests
        boolean messages
        boolean mentions
        boolean group_invites
        boolean group_messages
        boolean system
        boolean email_notifications
        boolean push_notifications
        boolean sound_enabled
        timestamp created_at
        timestamp updated_at
    }

    recent_searches {
        int id PK
        int user_id FK
        varchar search_type
        varchar search_query
        int result_id
        varchar result_name
        json result_data
        timestamp created_at
    }

    typing_status {
        int id PK
        int user_id FK
        int chat_with_user_id FK
        int group_id FK
        boolean is_typing
        timestamp last_typing_at
    }

    block_list {
        int id PK
        int user_id FK
        int blocked_user_id FK
        timestamp created_at
    }

    group_notifications {
        int id PK
        int group_id FK
        int user_id FK
        varchar notification_type
        text message
        int created_by FK
        boolean is_read
        timestamp created_at
    }

    group_messages_read {
        int id PK
        int group_id FK
        int message_id FK
        int user_id FK
        timestamp read_at
    }

    user_reports {
        int id PK
        int reporter_id FK
        int reported_user_id FK
        varchar reason
        text description
        varchar status
        int reviewed_by
        timestamp reviewed_at
        text resolution_notes
        timestamp created_at
        timestamp updated_at
    }

    user_sessions {
        int id PK
        int user_id FK
        varchar session_token
        varchar ip_address
        varchar user_agent
        timestamp expires_at
        timestamp created_at
    }

    activity_log {
        int id PK
        int user_id FK
        varchar action
        varchar ip_address
        varchar user_agent
        json details
        timestamp created_at
    }

    rate_limits {
        int id PK
        varchar ip_address
        varchar action_type
        int attempts
        timestamp first_attempt_at
        timestamp last_attempt_at
    }

    login_attempts {
        int id PK
        varchar identifier
        varchar ip_address
        varchar user_agent
        timestamp attempted_at
    }

    login_lockouts {
        int id PK
        varchar identifier
        timestamp locked_at
        timestamp locked_until
    }

    password_history {
        int id PK
        int user_id FK
        varchar password_hash
        timestamp created_at
    }

    security_log {
        int id PK
        varchar event
        varchar ip_address
        varchar user_agent
        int user_id FK
        json details
        timestamp created_at
    }

    admin_users {
        int id PK
        varchar username
        varchar email
        varchar password
        varchar full_name
        varchar role
        varchar avatar
        boolean is_active
        timestamp last_login
        int login_attempts
        timestamp locked_until
        varchar remember_token
        timestamp created_at
        timestamp updated_at
    }

    admin_activity_log {
        int id PK
        int admin_id FK
        varchar action
        varchar target_type
        int target_id
        json details
        varchar ip_address
        varchar user_agent
        timestamp created_at
    }

    system_settings {
        int id PK
        varchar setting_key
        varchar setting_value
        varchar setting_type
        text description
        int updated_by
        timestamp updated_at
    }

    users ||--o{ friendships : "user_id"
    users ||--o{ friendships : "friend_id"
    users ||--o{ messages : "sender_id"
    users ||--o{ messages : "receiver_id"
    users ||--o{ groups : "created_by"
    users ||--o{ group_members : "user_id"
    users ||--o{ group_messages : "user_id"
    users ||--o{ media : "user_id"
    users ||--o{ media : "receiver_id"
    users ||--o{ notifications : "user_id"
    users ||--o{ notifications : "sender_id"
    users ||--o| notification_preferences : "user_id"
    users ||--o{ recent_searches : "user_id"
    users ||--o{ typing_status : "user_id"
    users ||--o{ typing_status : "chat_with_user_id"
    users ||--o{ block_list : "user_id"
    users ||--o{ block_list : "blocked_user_id"
    users ||--o{ group_notifications : "user_id"
    users ||--o{ group_notifications : "created_by"
    users ||--o{ group_messages_read : "user_id"
    users ||--o{ user_reports : "reporter_id"
    users ||--o{ user_reports : "reported_user_id"
    users ||--o{ user_sessions : "user_id"
    users ||--o{ activity_log : "user_id"
    users ||--o{ password_history : "user_id"
    users ||--o{ security_log : "user_id"

    groups ||--o{ group_members : "group_id"
    groups ||--o{ group_messages : "group_id"
    groups ||--o{ media : "group_id"
    groups ||--o{ group_notifications : "group_id"
    groups ||--o{ group_messages_read : "group_id"
    groups ||--o{ messages : "group_id"

    group_messages ||--o{ group_messages_read : "message_id"
    group_messages ||--o{ group_messages : "reply_to"
    group_messages ||--o{ media : "media_id"

    messages ||--o{ messages : "reply_to_id"
    messages ||--o| media : "media_id"

    admin_users ||--o{ admin_activity_log : "admin_id"
```

## Table Relationships Summary

### Core Tables
- **users** - Central table containing all user information
- **friendships** - Many-to-many relationship between users for friend connections
- **messages** - Direct messages between users
- **groups** - Group chat containers
- **group_members** - Many-to-many relationship between users and groups
- **group_messages** - Messages within groups

### Media & Notifications
- **media** - File attachments for messages and profiles
- **notifications** - User notifications
- **notification_preferences** - User notification settings (one-to-one with users)
- **group_notifications** - Group-specific notifications

### Security & Logging
- **user_sessions** - Active user sessions
- **activity_log** - User activity tracking
- **rate_limits** - API rate limiting
- **login_attempts** - Login attempt tracking
- **login_lockouts** - Account lockout management
- **password_history** - Password change history
- **security_log** - Security event logging

### Admin
- **admin_users** - Administrator accounts
- **admin_activity_log** - Admin action tracking
- **system_settings** - System configuration

### Supporting Tables
- **recent_searches** - User search history
- **typing_status** - Real-time typing indicators
- **block_list** - User blocking relationships
- **group_messages_read** - Group message read receipts
- **user_reports** - User report/complaint tracking
