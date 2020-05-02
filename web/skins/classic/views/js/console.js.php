var consoleRefreshTimeout = <?php echo 1000*ZM_WEB_REFRESH_MAIN ?>;

<?php
if ( canEdit('System') ) {
  if ( ZM_CHECK_FOR_UPDATES && ZM_DYN_LAST_VERSION && ( verNum(ZM_VERSION) < verNum(ZM_DYN_LAST_VERSION) ) && ( verNum(ZM_DYN_CURR_VERSION) < verNum(ZM_DYN_LAST_VERSION) ) && ( ZM_DYN_NEXT_REMINDER < time() ) ) {
    $showVersionPopup = true;
    $nextReminder = time() + 60*60;
    // limit popups to one per hour instead of on every console refresh.
    dbQuery("UPDATE Config SET Value = '".$nextReminder."' WHERE Name = 'ZM_DYN_NEXT_REMINDER'");

  } else if ( ZM_DYN_SHOW_DONATE_REMINDER ) {
    if ( ZM_DYN_DONATE_REMINDER_TIME > 0 ) {
      if ( ZM_DYN_DONATE_REMINDER_TIME < time() ) {
        $showDonatePopup = true;
      }
    } else {
      $nextReminder = time() + 30*24*60*60;
      dbQuery("UPDATE Config SET Value = '".$nextReminder."' WHERE Name = 'ZM_DYN_DONATE_REMINDER_TIME'");
    }
  }
} // end if canEdit('System')
?>
var showVersionPopup = <?php echo isset($showVersionPopup )?'true':'false' ?>;
var showDonatePopup = <?php echo isset($showDonatePopup )?'true':'false' ?>;
