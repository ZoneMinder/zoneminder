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
var ZM_WEB_LIST_THUMBS = <?php echo ZM_WEB_LIST_THUMBS ? 'true' : 'false' ?>;

