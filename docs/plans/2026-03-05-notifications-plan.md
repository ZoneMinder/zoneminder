# Push Notification Token Registration — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Enable zm_detect to send FCM push notifications directly by storing device tokens in ZM's database via a new REST API.

**Architecture:** New `Notifications` table in ZM DB, CakePHP REST controller with user-scoped access, pyzm `Notification` model for Python access, and push-sending logic in zm_detect using FCM proxy mode.

**Tech Stack:** MySQL, CakePHP 2.x, Python 3 (pyzm dataclasses + requests), FCM HTTP v1 via cloud function proxy.

**Repos:**
- ZM: `/home/arjunrc/fiddle/zm/pliablepixels_ZoneMinder.git` (branch: `notifications-api`)
- pyzm: `/home/arjunrc/fiddle/pyzm`
- ES: `/home/arjunrc/fiddle/zmeventnotification`

**Design doc:** `docs/plans/2026-03-05-notifications-design.md`

---

### Task 1: Database — Add Notifications table to fresh-install schema

**Files:**
- Modify: `/home/arjunrc/fiddle/zm/pliablepixels_ZoneMinder.git/db/zm_create.sql.in` (after line 1330)

**Step 1: Add CREATE TABLE after Events_Tags table**

Insert after line 1330 (after `Events_Tags` closing), before the `source` lines:

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
) ENGINE=@ZM_MYSQL_ENGINE@;
```

**Step 2: Verify syntax**

Run: `grep -A 20 'CREATE TABLE .Notifications' db/zm_create.sql.in`
Expected: The full CREATE TABLE statement as above.

**Step 3: Commit**

```bash
git add db/zm_create.sql.in
git commit -m "feat: add Notifications table to fresh-install schema refs #4684"
```

---

### Task 2: Database — Create migration file for existing installs

**Files:**
- Create: `/home/arjunrc/fiddle/zm/pliablepixels_ZoneMinder.git/db/zm_update-1.39.2.sql`

**Step 1: Write migration file**

```sql
--
-- Add Notifications table for FCM push token registration
--

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'Notifications'
   AND table_schema = DATABASE()) > 0,
  "SELECT 'Notifications table already exists'",
  "CREATE TABLE `Notifications` (
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
  ) ENGINE=InnoDB"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
```

**Step 2: Test migration against local DB**

Run: `mysql -u zmuser -pzmpass zm < db/zm_update-1.39.2.sql`
Expected: No errors. Run again to verify idempotency (should print "Notifications table already exists").

**Step 3: Verify table exists**

Run: `mysql -u zmuser -pzmpass zm -e "DESCRIBE Notifications;"`
Expected: All columns listed with correct types.

**Step 4: Commit**

```bash
git add db/zm_update-1.39.2.sql
git commit -m "feat: add migration for Notifications table refs #4684"
```

---

### Task 3: CakePHP — Create Notification model

**Files:**
- Create: `/home/arjunrc/fiddle/zm/pliablepixels_ZoneMinder.git/web/api/app/Model/Notification.php`

**Step 1: Write model file**

Reference: `web/api/app/Model/State.php`

```php
<?php
App::uses('AppModel', 'Model');

class Notification extends AppModel {

  public $useTable = 'Notifications';
  public $primaryKey = 'Id';
  public $displayField = 'Token';

  public $validate = array(
    'Token' => array(
      'notBlank' => array(
        'rule' => array('notBlank'),
        'message' => 'Token is required',
      ),
    ),
    'Platform' => array(
      'inList' => array(
        'rule' => array('inList', array('android', 'ios', 'web')),
        'message' => 'Platform must be android, ios, or web',
      ),
    ),
    'PushState' => array(
      'inList' => array(
        'rule' => array('inList', array('enabled', 'disabled')),
        'message' => 'PushState must be enabled or disabled',
        'allowEmpty' => true,
      ),
    ),
  );

}
```

**Step 2: Commit**

```bash
git add web/api/app/Model/Notification.php
git commit -m "feat: add Notification CakePHP model refs #4684"
```

---

### Task 4: CakePHP — Create NotificationsController with user-scoped CRUD

**Files:**
- Create: `/home/arjunrc/fiddle/zm/pliablepixels_ZoneMinder.git/web/api/app/Controller/NotificationsController.php`

**Step 1: Write controller**

Reference: `web/api/app/Controller/StatesController.php` for pattern, `web/api/app/Controller/UsersController.php` for `$user->Id()` scoping.

```php
<?php
App::uses('AppController', 'Controller');

class NotificationsController extends AppController {

  public $components = array('RequestHandler');

  /**
   * Return true if the authenticated user is an admin (System=Edit).
   */
  private function _isAdmin() {
    global $user;
    return (!$user) || ($user->System() == 'Edit');
  }

  /**
   * Return the authenticated user's Id, or null.
   */
  private function _userId() {
    global $user;
    return $user ? $user->Id() : null;
  }

  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    $canView = (!$user) || ($user->System() != 'None');
    if (!$canView) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
  }

  /**
   * index — list notifications. Admin sees all, user sees own.
   */
  public function index() {
    $conditions = array();
    if (!$this->_isAdmin()) {
      $conditions['Notification.UserId'] = $this->_userId();
    }
    $notifications = $this->Notification->find('all', array(
      'conditions' => $conditions,
      'recursive' => -1,
    ));
    $this->set(array(
      'notifications' => $notifications,
      '_serialize' => array('notifications'),
    ));
  }

  /**
   * view — show single notification. Scoped to user unless admin.
   */
  public function view($id = null) {
    $this->Notification->id = $id;
    if (!$this->Notification->exists()) {
      throw new NotFoundException(__('Invalid notification'));
    }
    $notification = $this->Notification->find('first', array(
      'conditions' => array('Notification.Id' => $id),
      'recursive' => -1,
    ));
    if (!$this->_isAdmin() && $notification['Notification']['UserId'] != $this->_userId()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
    $this->set(array(
      'notification' => $notification,
      '_serialize' => array('notification'),
    ));
  }

  /**
   * add — create or upsert. Auto-sets UserId from session.
   * If Token already exists for this user, updates instead of creating.
   */
  public function add() {
    if (!$this->request->is('post')) {
      throw new BadRequestException(__('POST required'));
    }

    $data = $this->request->data;
    if (isset($data['Notification'])) {
      $data = $data['Notification'];
    }

    // Force UserId to the authenticated user (admin can override)
    if (!$this->_isAdmin() || !isset($data['UserId'])) {
      $data['UserId'] = $this->_userId();
    }

    if (!isset($data['CreatedOn'])) {
      $data['CreatedOn'] = date('Y-m-d H:i:s');
    }

    // Upsert: check if token already exists
    if (isset($data['Token'])) {
      $existing = $this->Notification->find('first', array(
        'conditions' => array('Notification.Token' => $data['Token']),
        'recursive' => -1,
      ));
      if ($existing) {
        // Only allow upsert if same user or admin
        if (!$this->_isAdmin() && $existing['Notification']['UserId'] != $this->_userId()) {
          throw new UnauthorizedException(__('Token belongs to another user'));
        }
        $this->Notification->id = $existing['Notification']['Id'];
        unset($data['CreatedOn']);  // Don't overwrite creation date on upsert
      } else {
        $this->Notification->create();
      }
    } else {
      $this->Notification->create();
    }

    if ($this->Notification->save(array('Notification' => $data))) {
      $notification = $this->Notification->find('first', array(
        'conditions' => array('Notification.Id' => $this->Notification->id),
        'recursive' => -1,
      ));
      $this->set(array(
        'notification' => $notification,
        '_serialize' => array('notification'),
      ));
    } else {
      $this->response->statusCode(400);
      $this->set(array(
        'message' => __('Could not save notification'),
        'errors' => $this->Notification->validationErrors,
        '_serialize' => array('message', 'errors'),
      ));
    }
  }

  /**
   * edit — update notification fields. Scoped to user unless admin.
   */
  public function edit($id = null) {
    $this->Notification->id = $id;
    if (!$this->Notification->exists()) {
      throw new NotFoundException(__('Invalid notification'));
    }

    $existing = $this->Notification->find('first', array(
      'conditions' => array('Notification.Id' => $id),
      'recursive' => -1,
    ));
    if (!$this->_isAdmin() && $existing['Notification']['UserId'] != $this->_userId()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }

    if ($this->request->is(array('post', 'put'))) {
      $data = $this->request->data;
      if (isset($data['Notification'])) {
        $data = $data['Notification'];
      }
      // Don't allow changing UserId unless admin
      if (!$this->_isAdmin()) {
        unset($data['UserId']);
      }
      if ($this->Notification->save(array('Notification' => $data))) {
        $notification = $this->Notification->find('first', array(
          'conditions' => array('Notification.Id' => $id),
          'recursive' => -1,
        ));
        $this->set(array(
          'notification' => $notification,
          '_serialize' => array('notification'),
        ));
      } else {
        $this->response->statusCode(400);
        $this->set(array(
          'message' => __('Could not save notification'),
          'errors' => $this->Notification->validationErrors,
          '_serialize' => array('message', 'errors'),
        ));
      }
    }
  }

  /**
   * delete — remove notification. Scoped to user unless admin.
   */
  public function delete($id = null) {
    $this->Notification->id = $id;
    if (!$this->Notification->exists()) {
      throw new NotFoundException(__('Invalid notification'));
    }
    $this->request->allowMethod('post', 'delete');

    $existing = $this->Notification->find('first', array(
      'conditions' => array('Notification.Id' => $id),
      'recursive' => -1,
    ));
    if (!$this->_isAdmin() && $existing['Notification']['UserId'] != $this->_userId()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }

    if ($this->Notification->delete()) {
      $this->set(array(
        'message' => __('Notification deleted'),
        '_serialize' => array('message'),
      ));
    } else {
      $this->response->statusCode(400);
      $this->set(array(
        'message' => __('Could not delete notification'),
        '_serialize' => array('message'),
      ));
    }
  }

}
```

**Step 2: Commit**

```bash
git add web/api/app/Controller/NotificationsController.php
git commit -m "feat: add NotificationsController with user-scoped CRUD refs #4684"
```

---

### Task 5: CakePHP — Register route

**Files:**
- Modify: `/home/arjunrc/fiddle/zm/pliablepixels_ZoneMinder.git/web/api/app/Config/routes.php` (line 47, after `zones`)

**Step 1: Add mapResources call**

Add after line 47 (`Router::mapResources('zones');`):

```php
	Router::mapResources('notifications');
```

**Step 2: Commit**

```bash
git add web/api/app/Config/routes.php
git commit -m "feat: register notifications REST route refs #4684"
```

---

### Task 6: Test ZM API manually

**Step 1: Deploy API files to live web root**

```bash
echo "doctoral" | sudo -S cp web/api/app/Model/Notification.php /usr/share/zoneminder/www/api/app/Model/
echo "doctoral" | sudo -S cp web/api/app/Controller/NotificationsController.php /usr/share/zoneminder/www/api/app/Controller/
echo "doctoral" | sudo -S cp web/api/app/Config/routes.php /usr/share/zoneminder/www/api/app/Config/
```

**Step 2: Apply migration**

```bash
mysql -u zmuser -pzmpass zm < db/zm_update-1.39.2.sql
```

**Step 3: Clear CakePHP cache**

```bash
echo "doctoral" | sudo -S rm -rf /usr/share/zoneminder/www/api/app/tmp/cache/models/*
echo "doctoral" | sudo -S rm -rf /usr/share/zoneminder/www/api/app/tmp/cache/persistent/*
```

**Step 4: Get auth token**

```bash
TOKEN=$(curl -s -k -X POST "http://localhost/zm/api/host/login.json" \
  -d "user=admin&pass=admin" | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")
echo $TOKEN
```

Expected: A JWT token string.

**Step 5: Test POST (create)**

```bash
curl -s -k -X POST "http://localhost/zm/api/notifications.json?token=$TOKEN" \
  -d "Notification[Token]=test_fcm_token_123" \
  -d "Notification[Platform]=android" \
  -d "Notification[MonitorList]=1,2" \
  -d "Notification[Interval]=60" \
  -d "Notification[AppVersion]=1.0.0" | python3 -m json.tool
```

Expected: JSON with the created notification including auto-populated `UserId`, `Id`, `CreatedOn`.

**Step 6: Test GET (index)**

```bash
curl -s -k "http://localhost/zm/api/notifications.json?token=$TOKEN" | python3 -m json.tool
```

Expected: JSON array with the notification created above.

**Step 7: Test PUT (update MonitorList)**

```bash
# Use the Id from the POST response
curl -s -k -X PUT "http://localhost/zm/api/notifications/1.json?token=$TOKEN" \
  -d "Notification[MonitorList]=1,2,3" | python3 -m json.tool
```

Expected: Updated notification with `MonitorList` = "1,2,3".

**Step 8: Test POST upsert (same token)**

```bash
curl -s -k -X POST "http://localhost/zm/api/notifications.json?token=$TOKEN" \
  -d "Notification[Token]=test_fcm_token_123" \
  -d "Notification[Platform]=ios" \
  -d "Notification[MonitorList]=5" | python3 -m json.tool
```

Expected: Same `Id` as before, updated `Platform` and `MonitorList`.

**Step 9: Test DELETE**

```bash
curl -s -k -X DELETE "http://localhost/zm/api/notifications/1.json?token=$TOKEN"
```

Expected: Success message.

**Step 10: Verify deletion**

```bash
curl -s -k "http://localhost/zm/api/notifications.json?token=$TOKEN" | python3 -m json.tool
```

Expected: Empty array.

---

### Task 7: pyzm — Add Notification dataclass

**Files:**
- Modify: `/home/arjunrc/fiddle/pyzm/pyzm/models/zm.py` (after Event class, around line 221)

**Step 1: Add Notification dataclass**

Insert after the Event class (after line 220):

```python
@dataclass
class Notification:
    """A ZoneMinder push notification token registration.

    Represents a device registered to receive FCM push notifications
    for ZoneMinder events.
    """
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

    def raw(self) -> dict:
        return self._raw

    def monitors(self) -> list[int] | None:
        """Parse MonitorList into list of ints. None = all monitors."""
        if not self.monitor_list:
            return None
        return [int(m.strip()) for m in self.monitor_list.split(",") if m.strip()]

    def should_notify(self, monitor_id: int) -> bool:
        """Check if this token should receive notifications for the given monitor."""
        if self.push_state != "enabled":
            return False
        monitors = self.monitors()
        if monitors is None:
            return True  # NULL = all monitors
        return monitor_id in monitors

    def is_throttled(self) -> bool:
        """Check if this token is currently throttled."""
        if self.interval <= 0 or self.last_notified_at is None:
            return False
        elapsed = (datetime.now() - self.last_notified_at).total_seconds()
        return elapsed < self.interval

    def delete(self) -> None:
        self._require_client()
        self._client._delete_notification(self.id)

    def update_last_sent(self, badge: int | None = None) -> None:
        """Update LastNotifiedAt to now and optionally set BadgeCount."""
        self._require_client()
        self._client._update_notification_last_sent(self.id, badge)

    def _require_client(self) -> None:
        if self._client is None:
            raise RuntimeError(
                "Notification not bound to a ZMClient. "
                "Use zm.notifications() to get bound Notification objects."
            )

    @classmethod
    def from_api_dict(cls, data: dict, client: ZMClient | None = None) -> Notification:
        """Build from a ZM API Notification JSON dict."""
        n = data.get("Notification", data)
        return cls(
            id=int(n.get("Id", 0)),
            user_id=int(n.get("UserId", 0)),
            token=n.get("Token", ""),
            platform=n.get("Platform", ""),
            monitor_list=n.get("MonitorList"),
            interval=int(n.get("Interval", 0)),
            push_state=n.get("PushState", "enabled"),
            app_version=n.get("AppVersion"),
            badge_count=int(n.get("BadgeCount", 0)),
            last_notified_at=_parse_dt(n.get("LastNotifiedAt")),
            _raw=data,
            _client=client,
        )
```

**Step 2: Commit**

```bash
cd /home/arjunrc/fiddle/pyzm
git add pyzm/models/zm.py
git commit -m "feat: add Notification dataclass for push token registration"
```

---

### Task 8: pyzm — Add ZMClient notification methods

**Files:**
- Modify: `/home/arjunrc/fiddle/pyzm/pyzm/client.py` (after `event()` method, around line 219)
- Modify: `/home/arjunrc/fiddle/pyzm/pyzm/__init__.py` (add export)

**Step 1: Add client methods after `event()` (around line 219)**

```python
    # ------------------------------------------------------------------
    # Notifications
    # ------------------------------------------------------------------

    def notifications(self) -> list[Notification]:
        """Fetch all push notification token registrations."""
        data = self._api.get("notifications.json")
        items = data.get("notifications", []) if data else []
        return [Notification.from_api_dict(n, client=self) for n in items]

    def notification(self, notification_id: int) -> Notification:
        """Fetch a single notification registration by ID."""
        data = self._api.get(f"notifications/{notification_id}.json")
        if data and data.get("notification"):
            return Notification.from_api_dict(data["notification"], client=self)
        raise ValueError(f"Notification {notification_id} not found")

    def _create_notification(self, **kwargs) -> dict:
        """Create or upsert a notification registration."""
        data = {f"Notification[{k}]": v for k, v in kwargs.items()}
        return self._api.post("notifications.json", data=data)

    def _update_notification(self, notification_id: int, **kwargs) -> dict:
        """Update fields on a notification registration."""
        data = {f"Notification[{k}]": v for k, v in kwargs.items()}
        return self._api.put(f"notifications/{notification_id}.json", data=data)

    def _delete_notification(self, notification_id: int) -> None:
        """Delete a notification registration."""
        self._api.delete(f"notifications/{notification_id}.json")

    def _update_notification_last_sent(self, notification_id: int, badge: int | None = None) -> None:
        """Update LastNotifiedAt to now and optionally set BadgeCount."""
        from datetime import datetime
        data = {"LastNotifiedAt": datetime.now().strftime("%Y-%m-%d %H:%M:%S")}
        if badge is not None:
            data["BadgeCount"] = str(badge)
        self._update_notification(notification_id, **data)
```

**Step 2: Add import at top of client.py**

Add to the existing import from `pyzm.models.zm`:

```python
from pyzm.models.zm import Notification
```

(Find the existing `from pyzm.models.zm import ...` line and add `Notification` to it.)

**Step 3: Add export to `__init__.py`**

In `/home/arjunrc/fiddle/pyzm/pyzm/__init__.py`, add `"Notification"` to the `__all__` list (line 18-28) and add the import:

```python
from pyzm.models.zm import Notification
```

Add `"Notification"` to `__all__`.

**Step 4: Commit**

```bash
cd /home/arjunrc/fiddle/pyzm
git add pyzm/client.py pyzm/__init__.py
git commit -m "feat: add ZMClient notification methods and export"
```

---

### Task 9: zm_detect — Add push notification config and sender

**Files:**
- Modify: `/home/arjunrc/fiddle/zmeventnotification/hook/objectconfig.yml` (after line 78, after animation section)
- Create: `/home/arjunrc/fiddle/zmeventnotification/zmes_hook_helpers/push.py`
- Modify: `/home/arjunrc/fiddle/zmeventnotification/hook/zm_detect.py` (after line 187, after tagging)

**Step 1: Add push config section to objectconfig.yml**

Insert after line 78 (after `animation:` section):

```yaml

# Push notifications via FCM cloud function proxy
# zm_detect reads registered tokens from ZM's Notifications table (via pyzm)
# and sends push notifications directly after detection.
push:
  enabled: "no"
  # Cloud function proxy URL and authorization key
  fcm_v1_url: "https://us-central1-zmng-b7af6.cloudfunctions.net/send_push"
  fcm_v1_key: "!FCM_V1_KEY"
  # Replace previous push for same monitor (collapses notifications)
  replace_push_messages: "yes"
  # Include event image URL in push notification
  include_picture: "yes"
  # Android notification priority
  android_priority: "high"
  # Android TTL in seconds (omit to use FCM default)
  #android_ttl: 60
```

**Step 2: Create push.py helper module**

```python
"""Push notification sender for zm_detect.

Reads registered tokens from ZM's Notifications table via pyzm,
filters by monitor, checks throttle, and sends via FCM cloud function proxy.
"""

import json
import requests
from datetime import datetime


def send_push_notifications(zm, config, monitor_id, event_id, monitor_name, cause, logger):
    """Send FCM push notifications to all qualifying registered tokens.

    Args:
        zm: pyzm ZMClient instance (already authenticated).
        config: dict with push config keys (fcm_v1_url, fcm_v1_key, etc.).
        monitor_id: int, the monitor that triggered the event.
        event_id: int/str, the event ID.
        monitor_name: str, human-readable monitor name.
        cause: str, detection result string (e.g. "person detected").
        logger: pyzm logger instance.
    """
    if config.get('push', {}).get('enabled') != 'yes':
        logger.Debug(1, 'push: disabled in config, skipping')
        return

    push_cfg = config.get('push', {})
    fcm_url = push_cfg.get('fcm_v1_url')
    fcm_key = push_cfg.get('fcm_v1_key')

    if not fcm_url or not fcm_key:
        logger.Error('push: fcm_v1_url or fcm_v1_key not configured')
        return

    try:
        notifications = zm.notifications()
    except Exception as e:
        logger.Error('push: failed to fetch notifications from ZM API: {}'.format(e))
        return

    if not notifications:
        logger.Debug(1, 'push: no registered tokens found')
        return

    mid = int(monitor_id)
    sent_count = 0

    for notif in notifications:
        if not notif.should_notify(mid):
            logger.Debug(2, 'push: skipping token ...{} (monitor {} not in filter)'.format(
                notif.token[-6:] if len(notif.token) > 6 else notif.token, mid))
            continue

        if notif.is_throttled():
            logger.Debug(2, 'push: skipping token ...{} (throttled, interval={}s)'.format(
                notif.token[-6:] if len(notif.token) > 6 else notif.token, notif.interval))
            continue

        badge = notif.badge_count + 1
        title = '{} Alarm'.format(monitor_name)
        body = cause if cause else 'Event {} on {}'.format(event_id, monitor_name)

        payload = {
            'token': notif.token,
            'title': title,
            'body': body,
            'sound': 'default',
            'badge': badge,
            'data': {
                'mid': str(mid),
                'eid': str(event_id),
                'monitorName': monitor_name,
                'cause': cause or '',
                'notification_foreground': 'true',
            },
        }

        # Platform-specific fields (proxy format, matching ES FCM.pm proxy mode)
        if notif.platform == 'android':
            payload['android'] = {
                'icon': 'ic_stat_notification',
                'priority': push_cfg.get('android_priority', 'high'),
            }
            ttl = push_cfg.get('android_ttl')
            if ttl:
                payload['android']['ttl'] = str(ttl)
            if push_cfg.get('replace_push_messages') == 'yes':
                payload['android']['tag'] = 'zmninjapush'
            if notif.app_version and notif.app_version != 'unknown':
                payload['android']['channel'] = 'zmninja'

        elif notif.platform == 'ios':
            payload['ios'] = {
                'thread_id': 'zmninja_alarm',
                'headers': {
                    'apns-priority': '10',
                    'apns-push-type': 'alert',
                },
            }
            if push_cfg.get('replace_push_messages') == 'yes':
                payload['ios']['headers']['apns-collapse-id'] = 'zmninjapush'

        # Send via proxy
        try:
            logger.Debug(1, 'push: sending to token ...{} ({})'.format(
                notif.token[-6:] if len(notif.token) > 6 else notif.token, notif.platform))

            resp = requests.post(
                fcm_url,
                headers={
                    'Content-Type': 'application/json',
                    'Authorization': fcm_key,
                },
                data=json.dumps(payload),
                timeout=10,
            )

            if resp.ok:
                logger.Debug(1, 'push: FCM proxy returned 200 for token ...{}'.format(
                    notif.token[-6:] if len(notif.token) > 6 else notif.token))
                # Update last sent time and badge count
                try:
                    notif.update_last_sent(badge=badge)
                except Exception as e:
                    logger.Debug(1, 'push: failed to update LastNotifiedAt: {}'.format(e))
                sent_count += 1
            else:
                body_text = resp.text
                logger.Error('push: FCM proxy error for token ...{}: {}'.format(
                    notif.token[-6:] if len(notif.token) > 6 else notif.token, body_text))
                # If token is invalid, delete it from ZM
                if 'not a valid FCM' in body_text or 'entity was not found' in body_text:
                    logger.Debug(1, 'push: removing invalid token ...{}'.format(
                        notif.token[-6:] if len(notif.token) > 6 else notif.token))
                    try:
                        notif.delete()
                    except Exception as e:
                        logger.Debug(1, 'push: failed to delete invalid token: {}'.format(e))

        except Exception as e:
            logger.Error('push: exception sending to token ...{}: {}'.format(
                notif.token[-6:] if len(notif.token) > 6 else notif.token, e))

    logger.Debug(1, 'push: sent {} notifications for event {} on monitor {}'.format(
        sent_count, event_id, monitor_name))
```

**Step 3: Add push call to zm_detect.py**

Insert after line 187 (after the tagging block), before the animation block (line 189):

```python
    # --- Push notifications ---
    if g.config.get('push', {}).get('enabled') == 'yes' and args.get('eventid') and args.get('monitorid'):
        try:
            from zmes_hook_helpers.push import send_push_notifications
            mon = zm.monitor(int(args['monitorid']))
            mon_name = mon.name if mon else 'Monitor {}'.format(args['monitorid'])
            send_push_notifications(
                zm, g.config, args['monitorid'], args['eventid'],
                mon_name, pred, g.logger)
        except Exception as e:
            g.logger.Error('Push notification error: {}'.format(e))
```

Note: Check how `zm.monitor()` works in pyzm. The `monitors()` method returns cached monitors, so we can get the name. If the method signature differs, adjust accordingly.

**Step 4: Commit**

```bash
cd /home/arjunrc/fiddle/zmeventnotification
git add hook/objectconfig.yml zmes_hook_helpers/push.py hook/zm_detect.py
git commit -m "feat: add direct FCM push notifications to zm_detect"
```

---

### Task 10: Test end-to-end push notification flow

**Step 1: Register a test token via API**

```bash
TOKEN=$(curl -s -k -X POST "http://localhost/zm/api/host/login.json" \
  -d "user=admin&pass=admin" | python3 -c "import sys,json; print(json.load(sys.stdin)['access_token'])")

curl -s -k -X POST "http://localhost/zm/api/notifications.json?token=$TOKEN" \
  -d "Notification[Token]=test_fcm_device_token" \
  -d "Notification[Platform]=android" \
  -d "Notification[MonitorList]=1" \
  -d "Notification[Interval]=0" \
  -d "Notification[AppVersion]=1.0.0" | python3 -m json.tool
```

**Step 2: Enable push in objectconfig.yml**

Set `push.enabled: "yes"` and configure `fcm_v1_key` with a valid key.

**Step 3: Run zm_detect with --fakeit for a test event**

```bash
python3 hook/zm_detect.py -c hook/objectconfig.yml -e <EVENT_ID> -m 1 -r "test" -n --fakeit "person"
```

Expected: Log output showing "push: sending to token ..." and either a 200 (if key is valid) or an auth error (which confirms the flow works up to the FCM call).

**Step 4: Verify LastNotifiedAt was updated**

```bash
curl -s -k "http://localhost/zm/api/notifications.json?token=$TOKEN" | python3 -m json.tool
```

Expected: `LastNotifiedAt` should be populated with a recent timestamp (if the push succeeded).

---

### Task 11: Final commit — update design doc

**Step 1: Verify all changes across repos**

```bash
cd /home/arjunrc/fiddle/zm/pliablepixels_ZoneMinder.git && git log --oneline -5
cd /home/arjunrc/fiddle/pyzm && git log --oneline -3
cd /home/arjunrc/fiddle/zmeventnotification && git log --oneline -3
```

**Step 2: Update design doc status**

Add "Implementation complete" note to the design doc. Ready for zmNg client spec (Component 5).
