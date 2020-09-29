<?php
//
// ZoneMinder web export view file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

function getDLEventHTML($eid, $eids) {
  $result = '';

  if ( !empty($eid) ) {
    $result .= '<input type="hidden" name="id" value="' .validInt($eid). '"/>'.PHP_EOL;

    $Event = new ZM\Event($eid);
    if ( !$Event->Id() ) {
      ZM\Error('Invalid event id');
      $result .= '<div class="error">Invalid event id</div>'.PHP_EOL;
    } else {
      $result .= 'Downloading event ' . $Event->Id . '. Resulting file should be approximately ' . human_filesize( $Event->DiskSpace() ).PHP_EOL;
    }
  } else if ( !empty($eids) ) {
    $total_size = 0;
    foreach ( $eids as $eid ) {
      if ( !validInt($eid) ) {
        ZM\Warning("Invalid event id in eids[] $eid");
        continue;
      }
      $Event = new ZM\Event($eid);
      $total_size += $Event->DiskSpace();
      $result .= '<input type="hidden" name="eids[]" value="' .validInt($eid). '"/>'.PHP_EOL;
    }
    unset($eid);
    $result .= 'Downloading ' . count($eids) . ' events.  Resulting file should be approximately ' . human_filesize($total_size).PHP_EOL;
  } else {
    $result .= '<div class="warning">There are no events found.  Resulting download will be empty.</div>';
  }
  
  return $result;
}

function getGeneratedHTML($generated, $exportFormat) {
  $result = '';

  if ( $generated == '' ) {
      $result .= '<h2 id="exportProgress" class="hidden warnText">'.PHP_EOL;
        $result .= '<span id="exportProgressText">' .translate('Exporting'). '</span>'.PHP_EOL;
        $result .= '<span id="exportProgressTicker"></span>'.PHP_EOL;
      $result .= '</h2>'.PHP_EOL;
  } else {
      $result .= '<h2 id="exportProgress" class="' .($generated ? 'infoText' : 'errorText').'">'.PHP_EOL;
        $result .= '<span id="exportProgressText">' .($generated ? translate('ExportSucceeded') : translate('ExportFailed')). '</span>'.PHP_EOL;
        $result .= '<span id="exportProgressTicker"></span>'.PHP_EOL;
      $result .= '</h2>'.PHP_EOL;
  }
  if ( $generated ) {
      $result .= '<h3 id="downloadLink"><a href="?view=archive&amp;type=' .$exportFormat. '">' .translate('Download'). '</a></h3>'.PHP_EOL;
  }
  
  return $result;
}

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

$eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';
$eids = isset($_REQUEST['eids']) ? $_REQUEST['eids'] : array();
$generated = isset($_REQUEST['generated']) ? $_REQUEST['generated'] : '';

$total_size = 0;
if ( isset($_SESSION['montageReviewFilter']) and !$eids ) {
  # Handles montageReview filter
  $eventsSql = 'SELECT E.Id, E.DiskSpace FROM Events AS E WHERE 1';
  $eventsSql .= $_SESSION['montageReviewFilter']['sql'];
  $results = dbQuery($eventsSql);
  while ( $event_row = dbFetchNext( $results ) ) {
    array_push($eids, $event_row['Id']);
    $total_size += $event_row['DiskSpace'];
  }
  if ( ! count($eids) ) {
    ZM\Error("No events found for download using $eventsSql");
  } 
  #session_start();
  #unset($_SESSION['montageReviewFilter']);
  #session_write_close();
#} else {
#Logger::Debug("NO montageReviewFilter");
}

$exportFormat = '';
if ( isset($_REQUEST['exportFormat']) ) {
  if ( !in_array($_REQUEST['exportFormat'], array('zip', 'tar')) ) {
    ZM\Error('Invalid exportFormat: '.$_REQUEST['exportFormat']);
  } else {
    $exportFormat = $_REQUEST['exportFormat'];
  }
}

$focusWindow = true;
$connkey = isset($_REQUEST['connkey']) ? validInt($_REQUEST['connkey']) : generateConnKey();

xhtmlHeaders(__FILE__, translate('Download'));
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" data-on-click="closeWindow"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Download') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="?">
        <input type="hidden" name="connkey" value="<?php echo $connkey; ?>"/>
        <?php echo getDLEventHTML($eid, $eids) ?>
        <table id="contentTable" class="minor">
          <tbody>
            <tr>
              <td><input type="hidden" name="exportVideo" value="1"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ExportFormat') ?></th>
              <td>
                <input type="radio" id="exportFormatTar" name="exportFormat" value="tar"/>
                <label for="exportFormatTar"><?php echo translate('ExportFormatTar') ?></label>
                <input type="radio" id="exportFormatZip" name="exportFormat" value="zip" checked="checked"/>
                <label for="exportFormatZip"><?php echo translate('ExportFormatZip') ?></label>
              </td>
            </tr>
          </tbody>
        </table>
        <button type="button" id="exportButton" name="exportButton" value="GenerateDownload">
        <!--data-on-click-this="exportEvent">-->
        <?php echo translate('GenerateDownload') ?>
        </button>
      </form>
    </div>
    <?php echo getGeneratedHTML($generated, $exportFormat) ?>
  </div>
<?php xhtmlFooter() ?>
