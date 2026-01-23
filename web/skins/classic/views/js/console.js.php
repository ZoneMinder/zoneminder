const server_utc_offset = <?php
$tz = ini_get('date.timezone');
if (!$tz) {
  $tz = 'UTC';
  ZM\Warning('Timezone has not been set. Either select it in Options->System->Timezone or in php.ini');
}

$TimeZone = new DateTimeZone($tz);
$now = new DateTime('now', $TimeZone);
$offset = $TimeZone->getOffset($now);
echo $offset.'; // '.floor($offset / 3600).' hours '.PHP_EOL;
?>
var consoleRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_MAIN ?>;

<?php
if ( canEdit('System') && ZM_DYN_SHOW_DONATE_REMINDER ) {
  if ( ZM_DYN_DONATE_REMINDER_TIME > 0 ) {
    if ( ZM_DYN_DONATE_REMINDER_TIME < time() ) $showDonatePopup = true;
  } else {
    $nextReminder = time() + 30*24*60*60;
    dbQuery("UPDATE Config SET Value = '".$nextReminder."' WHERE Name = 'ZM_DYN_DONATE_REMINDER_TIME'");
  }
}
?>
var showDonatePopup = <?php echo isset($showDonatePopup )?'true':'false' ?>;

var ZM_WEB_EVENTS_VIEW = '<?php echo ZM_WEB_EVENTS_VIEW ?>';

