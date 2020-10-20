<?php

if ( empty($_REQUEST['eid']) ) ajaxError('Event Id Not Provided');
if ( empty($_REQUEST['fid']) ) ajaxError('Frame Id Not Provided');

$eid = $_REQUEST['eid'];
$fid = $_REQUEST['fid'];
$row = ( isset($_REQUEST['row']) ) ? $_REQUEST['row'] : '';
$data = array();

// Not sure if this is required
if ( ZM_OPT_USE_AUTH && (ZM_AUTH_RELAY == 'hashed') ) {
  $auth_hash = generateAuthHash(ZM_AUTH_HASH_IPS);
  if ( isset($_REQUEST['auth']) and ($_REQUEST['auth'] != $auth_hash) ) {
    $data['auth'] = $auth_hash;
  }
}

$data['html'] = getStatsTableHTML($eid, $fid, $row);
$data['id'] = '#contentStatsTable' .$row;

ajaxResponse($data);
return;
?>
