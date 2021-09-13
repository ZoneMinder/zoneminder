<?php
if (!canEdit('Monitors')) return ' ';

$monitor = dbFetchOne('SELECT C.*,M.* FROM Monitors AS M INNER JOIN Controls AS C ON (M.ControlId = C.Id ) WHERE M.Id = ?', NULL, array($_REQUEST['mid']));

$labels = array();
foreach (dbFetchAll('SELECT * FROM ControlPresets WHERE MonitorId = ?', NULL, array($monitor['Id'])) as $row) {
  $labels[$row['Preset']] = $row['Label'];
}

$presets = array();
for ($i = 1; $i <= $monitor['NumPresets']; $i++) {
  $presets[$i] = translate('Preset').' '.$i;
  if (!empty($labels[$i])) {
    $presets[$i] .= ' ('.validHtmlStr($labels[$i]).')';
  }
}
?>
<div class="modal" id="ctrlPresetModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('SetPreset') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form name="contentForm" id="ctrlPresetForm" method="post" action="?">
        <?php
        // We have to manually insert the csrf key into the form when using a modal generated via ajax call
        echo getCSRFinputHTML();
        ?>
        <input type="hidden" name="view" value="control"/>
        <input type="hidden" name="mid" value="<?php echo $monitor['Id'] ?>"/>
        <input type="hidden" name="action" value="control"/>
        <input type="hidden" name="control" value="presetSet"/>
        <input type="hidden" name="showControls" value="1"/>
        <p><?php echo buildSelect('preset', $presets) ?></p>
        <p>
          <label for="newLabel"><?php echo translate('NewLabel') ?></label>
          <input type="text" name="newLabel" id="newLabel" value=""/>
        </p>

      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="cPresetSubmitModal" value="Save"><?php echo translate('Save') ?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
      </div>
    </form>
    </div>
  </div>
</div>
