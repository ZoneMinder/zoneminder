<?php
// This is the HTML representing the Download event modal on the Events page and other pages

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
#Debug("NO montageReviewFilter");
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

?>
<div id="downloadModal" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Download') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      <form name="contentForm" id="downloadForm" method="post" action="?">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          ?>
        <input type="hidden" name="connkey" value="<?php echo $connkey; ?>"/>
        <input type="hidden" name="exportVideo" value="1"/>
        <div>
          <?php echo getDLEventHTML($eid, $eids) ?>
        </div>
        <div class="font-weight-bold py-2">
          <span class="pr-3"><?php echo translate('ExportFormat') ?></span>
          <input type="radio" id="exportFormatTar" name="exportFormat" value="tar"/>
          <label for="exportFormatTar"><?php echo translate('ExportFormatTar') ?></label>
          <input type="radio" id="exportFormatZip" name="exportFormat" value="zip" checked="checked"/>
          <label for="exportFormatZip"><?php echo translate('ExportFormatZip') ?></label>
        </div>
        <button type="button" id="exportButton" name="exportButton" value="GenerateDownload"><?php echo translate('GenerateDownload') ?></button>
      </form>
      </div>
      <h2 id="exportProgress" class="text-warning invisible"> 
        <span class="spinner-grow" role="status" aria-hidden="true"></span> 
        Exporting...
      </h2>
      <h3><a id="downloadLink" href="#"></a></h3>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
