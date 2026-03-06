# Push Notification Token Registration for ZoneMinder

## Problem

zmNg currently registers FCM push tokens with the Event Server (ES) via websocket.
When using ZM's native `EventStartCommand` to invoke `zm_detect` directly, zm_detect
has no way to know who to send push notifications to. Storing tokens in ZM's database
makes them accessible to any ZM-integrated tool.

## Architecture

```
zmNg (mobile app)
  |
  |  REST API (JWT/session auth)
  v
ZoneMinder API (CakePHP) ---- Notifications table (MySQL)
                                       |
                                       |  reads tokens via pyzm
                                       v
zm_detect.py ---- objectconfig.yml (fcm_v1_url + fcm_v1_key)
  |
  |  HTTP POST (FCM proxy)
  v
Cloud Function -> FCM -> Device
```

## Component 1: Database — `Notifications` table

Migration version: 1.39.2

```sql
CREATE TABLE `Notifications` (
  `Id`              int unsigned    NOT NULL AUTO_INCREMENT,
  `UserId`          int unsigned    NOT NULL,
  `Token`           varchar(512)    NOT NULL,
  `Platform`        enum('android','ios','web') NOT NULL,
  `MonitorList`     text            DEFAULT NULL,
  `Interval`        int unsigned    NOT NULL DEFAULT 0,
  `PushState`       enum('enabled','disabled') NOT NULL DEFAULT 'enabled',
  `AppVersion`      varchar(32)     DEFAULT NULL,
  `BadgeCount`      int             NOT NULL DEFAULT 0,
  `LastNotifiedAt`  datetime        DEFAULT NULL,
  `CreatedOn`       datetime        DEFAULT NULL,
  `UpdatedOn`       timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Notifications_Token_idx` (`Token`),
  KEY `Notifications_UserId_idx` (`UserId`)
) ENGINE=InnoDB;
```

Column notes:
- `UserId` — FK to Users.Id. Users see own tokens, admins see all.
- `Token` — FCM registration token. UNIQUE with upsert on re-register.
- `Platform` — android, ios, or web. Determines FCM payload format.
- `MonitorList` — Comma-separated monitor IDs. NULL = all monitors.
- `Interval` — Per-token throttle in seconds. 0 = no throttle.
- `PushState` — Master enable/disable toggle.
- `LastNotifiedAt` — Last time a push was sent to this token. Used with `Interval` for throttling.
- `CreatedOn` / `UpdatedOn` — Follow ZM timestamp conventions.

## Component 2: ZM CakePHP API — `NotificationsController`

Standard REST resource following existing ZM controller patterns (StatesController, GroupsController).

Route: `Router::mapResources('notifications')` in routes.php

Endpoints:
- `GET /api/notifications.json` — List tokens (admin: all, user: own)
- `GET /api/notifications/{id}.json` — View single token (scoped)
- `POST /api/notifications.json` — Create or upsert token (auto-sets UserId from session)
- `PUT /api/notifications/{id}.json` — Update filters, pushstate, interval, etc.
- `DELETE /api/notifications/{id}.json` — Delete token (scoped)

Authorization:
- Admin users bypass scoping (can see/edit/delete all tokens)
- Non-admin users can only access rows where `UserId` matches their own User.Id
- POST auto-populates `UserId` from the authenticated session
- Upsert: if POST contains a Token that already exists, update the existing row (only if owned by same user or admin)

Model: `Notification.php` with `$useTable = 'Notifications'`, `$primaryKey = 'Id'`

## Component 3: pyzm — `Notification` model + ZMClient methods

Following pyzm's existing dataclass + client pattern:

Model (`pyzm/models/zm.py`):
```python
@dataclass
class Notification:
    id: int
    user_id: int = 0
    token: str = ""
    platform: str = ""
    monitor_list: str | None = None
    interval: int = 0
    push_state: str = "enabled"
    app_version: str | None = None
    badge_count: int = 0
    last_notified_at: datetime | None = None
    _raw: dict = field(default_factory=dict, repr=False, compare=False)
    _client: ZMClient | None = field(default=None, repr=False, compare=False)

    @classmethod
    def from_api_dict(cls, data: dict, client=None) -> "Notification":
        ...

    def monitors(self) -> list[int] | None:
        """Parse MonitorList into list of ints. None = all monitors."""
        if not self.monitor_list:
            return None
        return [int(m) for m in self.monitor_list.split(",")]
```

Client methods (`pyzm/client.py`):
- `notifications()` — fetch all tokens (zm_detect uses this)
- `notification(id)` — fetch single token
- `_create_notification(**kwargs)` — POST
- `_update_notification(id, **kwargs)` — PUT
- `_delete_notification(id)` — DELETE
- `_update_notification_last_sent(id)` — PUT to update LastNotifiedAt

## Component 4: zm_detect — Push notification sender

Config additions to `objectconfig.yml`:
```yaml
push:
  enabled: yes
  fcm_v1_url: https://us-central1-zmng-b7af6.cloudfunctions.net/send_push
  fcm_v1_key: <jwt_token>
```

After detection completes:
1. Query `Notifications` table via `zm.notifications()`
2. Filter: `PushState = 'enabled'` AND monitor ID in token's `MonitorList` (or MonitorList is NULL)
3. Throttle: skip if `LastNotifiedAt` is not None and `(now - LastNotifiedAt) < Interval`
4. For each qualifying token, HTTP POST to `fcm_v1_url` with payload:
   ```json
   {
     "token": "<fcm_token>",
     "title": "<MonitorName> Alarm",
     "body": "<detection_results>",
     "sound": "default",
     "badge": "<incremented_badge_count>",
     "data": {
       "mid": "<monitor_id>",
       "eid": "<event_id>",
       "monitorName": "<monitor_name>",
       "cause": "<detection_cause>",
       "notification_foreground": "true"
     },
     "android": { "priority": "high" },
     "ios": { "thread_id": "ZoneMinder_<mid>" }
   }
   ```
5. Update `LastNotifiedAt` and `BadgeCount` in DB via pyzm

FCM mode: proxy only (cloud function).

## Component 5 (future): zmNg client changes

Spec to be written after server components are done. zmNg will need to:
- Call ZM API to register/update/delete tokens instead of ES websocket
- Manage monitor filter UI against the new API
- Handle token refresh by calling PUT on the API

## Implementation order

1. DB migration + schema update (ZM repo)
2. CakePHP model + controller + route (ZM repo)
3. pyzm Notification model + ZMClient methods (pyzm repo)
4. zm_detect push sender (zmeventnotification repo)
5. zmNg client spec (separate doc)
