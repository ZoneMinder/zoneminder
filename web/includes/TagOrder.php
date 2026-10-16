<?php
//
// ZoneMinder per-user tag ordering
//
// Stores each user's personal "recently used tags" list in User_Preferences
// as a JSON array of Tag Ids (never Names -- names are free-text and can
// collide case-insensitively or shadow Object.prototype keys on the client,
// so Id is the only safe key). This lives server-side so the ordering is
// correct on any device/browser the user logs into, not just the one that
// wrote it.
//
namespace ZM;

require_once('database.php');

class TagOrder {
  const MAX_ENTRIES = 50;
  const PREF_NAME = 'tagOrder';

  // Returns an array of Tag Ids, most-recently-used first. Always an array,
  // never throws -- corrupt/non-array/non-numeric stored data is discarded
  // rather than propagated, so a bad row heals itself on next write instead
  // of silently freezing (this was bug #4 in the client-side version).
  public static function load($userId) {
    $row = dbFetchOne(
      'SELECT Value FROM User_Preferences WHERE UserId = ? AND Name = ?',
      NULL,
      array($userId, self::PREF_NAME)
    );
    if (!$row || !isset($row['Value'])) return array();

    $decoded = json_decode($row['Value'], true);
    if (!is_array($decoded)) return array();

    $ids = array_map('intval', $decoded);
    $ids = array_filter($ids, function($id) { return $id > 0; });
    return array_values($ids);
  }

  // Moves $tagId to the front of the user's recency list, trims to
  // MAX_ENTRIES, and persists it.
  public static function recordUsage($userId, $tagId) {
    $tagId = (int)$tagId;
    if ($tagId <= 0) return;

    $order = self::load($userId);
    $order = array_values(array_diff($order, array($tagId)));
    array_unshift($order, $tagId);
    if (count($order) > self::MAX_ENTRIES) {
      $order = array_slice($order, 0, self::MAX_ENTRIES);
    }
    $json = json_encode($order);

    // (UserId, Name) is unique, so let the db decide insert-vs-update instead
    // of racing a SELECT against a concurrent request from the same user.
    dbQuery(
      'INSERT INTO User_Preferences (UserId, Name, Value) VALUES (?, ?, ?)'.
      ' ON DUPLICATE KEY UPDATE Value = VALUES(Value)',
      array($userId, self::PREF_NAME, $json)
    );
  }
}
