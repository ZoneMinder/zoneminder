<?php
//
// ZoneMinder frame web action
// Copyright (C) 2019 ZoneMinder LLC
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

// If there is an action on an event, then we must have an id.
if ( empty($_REQUEST['eid']) ) {
  ZM\Warning('No eid in action on frame view');
  return;
}
$eid = validInt($_REQUEST['eid']);

if ( empty($_REQUEST['fid']) ) {
  ZM\Warning('No fid in action on frame view');
  return;
}
# Fid maybe is alarm/objdetect/etc.
$fid = validInt($_REQUEST['fid']);

if ( !canEdit('Events') ) {
  $view = 'error';
  return;
}

require_once('includes/Frame.php');

$Frame = ZM\Frame::find_one( array('EventId'=>$_REQUEST['eid'],'FrameId'=>$fid) );
if ( !$Frame ) {
  ZM\Error("Frame not found for event $eid frame $fid");
  return;
}
if ( $action == 'delete' ) {
  $Frame->delete();
  $view = 'none';
  $refreshParent = true;
} else if ( $action == 'do_alpr' ) {
  $path = $Frame->Path();#defaults to capture image
  ZM\Logger::Debug("Using frame path $path");
  $i = file_get_contents($path);
  if ( !$i ) {
    ZM\Error("No image from $path");
    return;
  }

  $postdata = array(
      'upload' => $i,
      #'regions' =>  ZM_PLATERECOGNIZER_REGION,
    );

  $eol = "\r\n";
  $data = '';
  $boundary = '---------------------' . substr(md5(rand(0, 32000)), 0, 10);
  if ( is_array($postdata) ) {
    foreach ( $postdata as $key => $val ) {
      $data .= '--' . $boundary . $eol;
      $data .= 'Content-Disposition: form-data; name='.$key.'; filename=frame.jpg;'.$eol.$eol.$val.$eol;
    }
  }
  $data .= '--'.$boundary.$eol;

  $response = do_post_request(
    ZM_PLATERECOGNIZER_URL,
    $data,
    implode($eol, array(
      #'Accept: */*',
      'Authorization: Token '. ZM_PLATERECOGNIZER_API_TOKEN,
      #'Connection: close',
      'Content-type: multipart/form-data; boundary='.$boundary
    )
    )
      #'Content-type: application/x-www-form-urlencoded; charset=UTF-8'))
  );
  ZM\Logger::Debug('Response: ' . print_r($response,true));
  $previous_data = $Frame->Data();
  ZM\Logger::Debug('Old data: ' . print_r($previous_data,true));
  $previous_data[] = array('type'=>'PLATE_RECOGNIZER','time'=>time(),'data'=>json_decode($response));
  // Frame->json_data is an array of responses, so append this one
  $Frame->save( array('Data'=>$previous_data) );
}
?>
