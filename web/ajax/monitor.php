<?php
ini_set('display_errors', '0');

if ( canView('Monitors') || (isset($_REQUEST['mid']) && $_REQUEST['mid'] !== '' && canView('Monitors', $_REQUEST['mid'])) ) {
  $mid = isset($_REQUEST['mid']) ? $_REQUEST['mid'] : null;
  if ($mid === null || $mid === '') {
    ajaxError(translate('RequestMissing') . ' "mid".');
  }

  $action = $_REQUEST['action'] ?? '';
  if ($action === '') {
    ajaxError(translate('RequestMissing') . ' "action".');
  }

  switch ( $action ) {
  case 'validateName' :
    require_once('includes/Monitor.php');
    $monitor = new ZM\Monitor($mid);
    $filterRegexp = $monitor->getDefaults()['Name']['filter_regexp'];
    $result = true;
    $badChars = [];
    $message = '';

    if (isset($_REQUEST['monitorName']) && is_string($_REQUEST['monitorName']) && $_REQUEST['monitorName'] !== '') {
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
  case 'audioLevel' :
    // Live reading for the level meter in the monitor editor's audio
    // settings. Polling this is also what keeps zmc measuring: a monitor with
    // AudioDetection off does not decode audio until asked, and stops again a
    // few seconds after the polling does. See Monitor::AudioLevelWanted.
    require_once('includes/Monitor.php');
    $monitor = new ZM\Monitor($mid);
    if (!$monitor->Id()) {
      ajaxError('Not found: monitor id '.validHtmlStr($mid));
      break;
    }
    if (!$monitor->canView()) {
      ajaxError(translate('insufficientPermissionsUser').' "'.validHtmlStr($user->Username()).'"');
      break;
    }

    $monitor->requestAudioLevel();
    $level = $monitor->audioLevel();
    ajaxResponse(array(
      // null rather than 0 when zmc is not running, so the meter can say so
      // instead of showing a confident silent reading.
      'level' => is_null($level) ? null : intval($level),
      'alarm' => $monitor->audioAlarm(),
      'threshold' => intval($monitor->AudioThreshold()),
      'detection' => $monitor->AudioDetection() ? true : false,
    ));
    break;
  } // end switch action
} // end if canView('Monitors')

ajaxError(translate('UnrecognisedAction').' "'.validHtmlStr($_REQUEST['action'] ?? '').'" '.translate('ConjOr').' '.translate('insufficientPermissionsUser').' "'.validHtmlStr($user->Username()).'"');
?>
