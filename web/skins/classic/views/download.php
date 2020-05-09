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

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

$total_size = 0;
if ( isset($_SESSION['montageReviewFilter']) and !isset($_REQUEST['eids']) ) {
  # Handles montageReview filter
  $eventsSql = 'SELECT E.Id, E.DiskSpace FROM Events AS E WHERE 1';
  $eventsSql .= $_SESSION['montageReviewFilter']['sql'];
  $results = dbQuery($eventsSql);
  $eids = [];
  while ( $event_row = dbFetchNext( $results ) ) {
    array_push($eids, $event_row['Id']);
    $total_size += $event_row['DiskSpace'];
  }
  $_REQUEST['eids'] = $eids;
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

if ( !empty($_REQUEST['eid']) ) {
  $Event = new ZM\Event($_REQUEST['eid']);
  if ( !$Event->Id ) {
    Error('Invalid event id');
  }
}

$focusWindow = true;
$connkey = isset($_REQUEST['connkey']) ? $_REQUEST['connkey'] : generateConnKey();

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
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="connkey" value="<?php echo $connkey; ?>"/>
<?php
if ( !empty($_REQUEST['eid']) ) {
?>
        <input type="hidden" name="id" value="<?php echo validInt($_REQUEST['eid']) ?>"/>
    <?php
    $Event = new ZM\Event($_REQUEST['eid']);
    echo 'Downloading event ' . $Event->Id . '. Resulting file should be approximately ' . human_filesize( $Event->DiskSpace() );
} else if ( !empty($_REQUEST['eids']) ) {
    $total_size = 0;
    foreach ( $_REQUEST['eids'] as $eid ) {
      if ( ! validInt($eid) ) {
        ZM\Warning("Invalid event id in eids[] $eid");
        continue;
      }
      $Event = new ZM\Event($eid);
      $total_size += $Event->DiskSpace();
?>
        <input type="hidden" name="eids[]" value="<?php echo validInt($eid) ?>"/>
<?php
    }
    unset($eid);
    echo "Downloading " . count($_REQUEST['eids']) . ' events.  Resulting file should be approximately ' . human_filesize($total_size);
} else {
    echo '<div class="warning">There are no events found.  Resulting download will be empty.</div>';
}
?>
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
<?php
    if ( isset($_REQUEST['generated']) ) {
?>
      <h2 id="exportProgress" class="<?php echo $_REQUEST['generated']?'infoText':'errorText' ?>">
        <span id="exportProgressText"><?php echo $_REQUEST['generated']?translate('ExportSucceeded'):translate('ExportFailed') ?></span>
        <span id="exportProgressTicker"></span>
      </h2>
<?php
    } else {
?>
      <h2 id="exportProgress" class="hidden warnText">
        <span id="exportProgressText"><?php echo translate('Exporting') ?></span>
        <span id="exportProgressTicker"></span>
      </h2>
<?php
    }
    if ( !empty($_REQUEST['generated']) ) {
?>
      <h3 id="downloadLink"><a href="?view=archive&amp;type=<?php echo $exportFormat; ?>"><?php echo translate('Download') ?></a></h3>
<?php
    }
?>
  </div>
</body>
</html>
