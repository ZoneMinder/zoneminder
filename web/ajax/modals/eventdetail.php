<?php
// This is the HTML representing the Event Detail modal on the Events page

$eid = isset($_REQUEST['eid']) ? $_REQUEST['eid'] : '';
$eids = isset($_REQUEST['eids']) ? $_REQUEST['eids'] : '';

$result = '';
$inputs = '';
$disabled = 'disabled="disabled"';
$null = '';
  
if ( !canEdit('Events') ) return;

if ( $eid ){ // Single Event Mode
  $eid = validInt($eid);
  $title = translate('Event').' '.$eid.PHP_EOL;
  $inputs .= '<input type="hidden" name="markEids[]" value="' .$eid. '"/>';
  $newEvent = dbFetchOne('SELECT E.* FROM Events AS E WHERE E.Id = ?', NULL, array($eid));

} elseif ( $eids ) { // Multi Event Mode

  $title = translate('Events');
  $sql = 'SELECT E.* FROM Events AS E WHERE ';
  $sqlWhere = array();
  $sqlValues = array();
  foreach ( $eids as $eid ) {
    $eid = validInt($eid);
    $inputs .= '<input type="hidden" name="markEids[]" value="' .$eid. '"/>';
    $sqlWhere[] = 'E.Id = ?';
    $sqlValues[] = $eid;
  }
  unset($eid);
  $sql .= join(' OR ', $sqlWhere);
  foreach( dbFetchAll( $sql, NULL, $sqlValues ) as $row ) {
    if ( !isset($newEvent) ) {
      $newEvent = $row;
    } else {
      if ( $newEvent['Cause'] && $newEvent['Cause'] != $row['Cause'] )
        $newEvent['Cause'] = '';
      if ( $newEvent['Notes'] && $newEvent['Notes'] != $row['Notes'] )
        $newEvent['Notes'] = '';
    }
  }

} else { // Event Mode not specified - should we really proceed if neither eid nor eids is set?
  $title = translate('Events');
}

?>
<div class="modal fade" id="eventDetailModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo $title ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form name="contentForm" id="eventDetailForm" method="post" action="?view=eventdetail&action=eventdetail">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          echo $inputs;
          ?>
          <input type="hidden" name="action" value="eventdetail"/>
          <input type="hidden" name="view" value="eventdetail"/>
          <table class="table-sm">
            <tbody>
              <tr>
                <th scope="row"><?php echo translate('Cause') ?></th>
                <td><input type="text" name="newEvent[Cause]" value="<?php echo validHtmlStr($newEvent['Cause']) ?>" size="32"/></td>
              </tr>
              <tr>
                <th scope="row" class="align-middle"><?php echo translate('Notes') ?></th>
                <td><textarea name="newEvent[Notes]" rows="6" cols="33"><?php echo validHtmlStr($newEvent['Notes']) ?></textarea></td>
              </tr>
            </tbody>
          </table>
      </div>
      <div class="modal-footer">
        <button type="submit" name="action" id="eventDetailSaveBtn" class="btn btn-primary" value="save" <?php echo !canEdit('Events') ? $disabled : $null .'>'. translate('Save') ?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
      </div>
      </form>
    </div>
  </div>
</div>

