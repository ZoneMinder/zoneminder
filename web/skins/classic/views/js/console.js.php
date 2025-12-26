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

// Variables for bootstrap-table
var canView = {
  'Stream': <?php echo canView('Stream') ? 'true' : 'false' ?>,
  'Events': <?php echo canView('Events') ? 'true' : 'false' ?>,
  'Monitors': <?php echo canView('Monitors') ? 'true' : 'false' ?>
};

var canEdit = {
  'Monitors': <?php echo canEdit('Monitors') ? 'true' : 'false' ?>,
  'Events': <?php echo canEdit('Events') ? 'true' : 'false' ?>
};

var ZM_WEB_EVENTS_VIEW = '<?php echo ZM_WEB_EVENTS_VIEW ?>';

