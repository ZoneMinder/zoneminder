<?php
require_once('includes/Filter.php');
// This is the HTML representing the Download event modal on the Events page and other pages

if (!canView('Events')) {
  $view = 'error';
  return;
}

$eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';
$eids = isset($_REQUEST['eids']) ? $_REQUEST['eids'] : array();
$generated = isset($_REQUEST['generated']) ? $_REQUEST['generated'] : '';
$exportFileName = 'zmDownload';

$filter = null;
if (isset($_REQUEST['filter'])) {
  $filter = ZM\Filter::parse($_REQUEST['filter']);
#} else if (isset($_SESSION['montageReviewFilter'])) {
  #$filter = $_SESSION['montageReviewFilter'];
}

if ($filter) {
  if (isset($_REQUEST['MonitorId'])) {
    $filter->addTerm(array('attr' => 'Monitor', 'op' => 'IN', 'val' => implode(',', $_REQUEST['MonitorId']), 'cnj' => 'and'));
  }
  if (isset($_REQUEST['GroupId'])) {
    $monitor_ids = [];
    foreach ($_REQUEST['GroupId'] as $group_id) {
      $group = ZM\Group::find_one(['Id'=>$group_id]);
      if ($group) {
        $monitor_ids += $group->MonitorIds();
        $exportFileName .= ' '.$group->Name();
      }
    }
    $filter->addTerm(array('attr' => 'Monitor', 'op' => 'IN', 'val' => implode(',', $monitor_ids), 'cnj' => 'and'));
  }
  if (isset($_REQUEST['minTimeSecs'])) {
  }
  if (isset($_REQUEST['maxTimeSecs'])) {
  }
  if (isset($_REQUEST['minTime']) and !$filter->has_term('DateTime', '>=')) {
    $filter->addTerm(array('attr' => 'StartDateTime', 'op' => '>=', 'val' => $_REQUEST['minTime'], 'cnj' => 'and'));
    $exportFileName .= ' '.$_REQUEST['minTime']; 
  }
  if (isset($_REQUEST['maxTime']) and !$filter->has_term('DateTime', '<=')) {
    $filter->addTerm(array('attr' => 'StartDateTime', 'op' => '<=', 'val' => $_REQUEST['maxTime'], 'cnj' => 'and'));
    $exportFileName .= ' '.$_REQUEST['maxTime']; 
  }
}
$total_size = 0;
if ($filter and !$eids) {
  # Handles montageReview filter
  $eventsSql = 'SELECT E.Id, E.DiskSpace FROM Events AS E WHERE ';
  $eventsSql .= $filter->sql();
  $results = dbQuery($eventsSql);
  while ($event_row = dbFetchNext($results)) {
    array_push($eids, $event_row['Id']);
    $total_size += $event_row['DiskSpace'];
  }
  if (!count($eids)) {
    ZM\Error("No events found for download using $eventsSql");
  } 
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
if ($exportFileName == 'zmDownload') $exportFileName .= '-'.$connkey;

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
          <input type="hidden" name="mergeevents" value="1"/>
<?php echo $filter ? $filter->hidden_fields() : '' ?>
          <div>
<?php
  $result = '';

  if (!empty($eid)) {
    $result .= '<input type="hidden" name="id" value="' .validInt($eid). '"/>'.PHP_EOL;

    $Event = new ZM\Event($eid);
    if (!$Event->Id()) {
      ZM\Error('Invalid event id');
      $result .= '<div class="error">Invalid event id</div>'.PHP_EOL;
    } else {
      $result .= 'Downloading event ' . $Event->Id . '. Resulting file should be approximately ' . human_filesize( $Event->DiskSpace() ).PHP_EOL;
    }
  } else if (!empty($eids)) {
    $total_size = 0;
    foreach ($eids as $eid) {
      if (!validInt($eid)) {
        ZM\Warning("Invalid event id in eids[] $eid");
        continue;
      }
      $Event = new ZM\Event($eid);
      $total_size += $Event->DiskSpace();
      if (!$filter)
        $result .= '<input type="hidden" name="eids[]" value="' .validInt($eid). '"/>'.PHP_EOL;
    }
    unset($eid);
    $result .= 'Downloading ' . count($eids) . ' events.  Resulting file should be approximately ' . human_filesize($total_size).PHP_EOL;
    $result .= '<br>Free space on disk \''.ZM_DIR_EXPORTS.'\': ' . human_filesize(disk_free_space(ZM_DIR_EXPORTS)).PHP_EOL;
    $result .= '<br>Will remain free: ' . human_filesize(disk_free_space(ZM_DIR_EXPORTS) - $total_size).PHP_EOL;
    if (count($eids) > 1000 and !$filter) {
      $results .= '<span class="warning">Warning: Too many recordings specified.  Download may fail.  Please select fewer recordings</span>';
    }
  } else {
    $result .= '<div class="warning">There are no events found.  Resulting download will be empty.</div>';
  }

  echo $result;
?>
          </div>
          <div class="exportFileName">
            <label><?php echo translate('Download File Name') ?></label>
            <input type="text" name="exportFileName" value="<?php echo validHtmlStr($exportFileName) ?>" pattern="[A-Za-z0-9 \(\)\.\:\-]+"/>
          </div>
          <div class="exportFormat">
            <label><?php echo translate('ExportFormat') ?></label>
            <input type="radio" id="exportFormatNoArchive" name="exportFormat" value="noArchive" checked="checked"/>
            <label for="exportFormatNoArchive"><?php echo translate('mp4') ?></label>
            <input type="radio" id="exportFormatTar" name="exportFormat" value="tar"/>
            <label for="exportFormatTar"><?php echo translate('ExportFormatTar') ?></label>
            <input type="radio" id="exportFormatZip" name="exportFormat" value="zip" checked="checked"/>
            <label for="exportFormatZip"><?php echo translate('ExportFormatZip') ?></label>
          </div>
          <button type="button" id="exportButton" name="exportButton" value="GenerateDownload"><?php echo translate('GenerateDownload') ?></button>
        </form>
        <h2 id="exportProgress" class="text-warning invisible"> 
          <span class="spinner-grow" role="status" aria-hidden="true"></span> 
          Exporting...
        </h2>
        <div class="downloadLinks"><h3><a id="downloadLink" href="#"></a></h3></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>

      </div> <!-- end ".modal-body" -->
    </div>
  </div>
</div>
