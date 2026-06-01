<?php
ini_set('display_errors', '0');

if ( canView('Monitors') || (isset($_REQUEST['mid']) && $_REQUEST['mid'] !== '' && canView('Monitors', $_REQUEST['mid'])) ) {
  $mid = isset($_REQUEST['mid']) ? $_REQUEST['mid'] : null;
  if ($mid === null || $mid === '') {
    ajaxError(translate('RequestMissing') . ' "mid".');
  }

  switch ( $_REQUEST['action'] ) {
  case 'validateName' :
    require_once('includes/Monitor.php');
    $monitor = new ZM\Monitor($mid);
    $filterRegexp = $monitor->GetDefaults()['Name']['filter_regexp'];
    $result = true;
    $badChars = [];
    $message = '';

    if (isset($_REQUEST['monitorName']) && $_REQUEST['monitorName'] !== '') {
      $monitorName = $_REQUEST['monitorName'];
      $trimmedMonitorName = trim($monitorName);
      $cleanedMonitorName = preg_replace($filterRegexp, '', $trimmedMonitorName);
      if ($trimmedMonitorName != $cleanedMonitorName){
        preg_match_all($filterRegexp, $trimmedMonitorName, $badChars);
        $result = false;
        $message = translate('BadNameCharsList') . ' "' . implode('","', array_unique($badChars[0])) . '".~~' . translate('BadNameChars');
      }
      ajaxResponse(array('response'=>$result, 'monitorName'=>$monitorName, 'cleanedMonitorName'=>$cleanedMonitorName, 'badChars'=>$badChars, 'messageBadNameChars'=>$message));
    } else {
      ajaxError(translate('ErrorVerifyingMonitorName'));
    }
    break;
  } // end switch action
} // end if canEdit('Monitors')

ajaxError(translate('UnrecognisedAction').' "'.$_REQUEST['action'].'" '.translate('or').' '.translate('insufficientPermissionsUser').' "'.$user->Username().'"');
?>
