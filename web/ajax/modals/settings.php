<?php
if ( !canView('Control') ) return;

$monitor = ZM\Monitor::find_one(array('Id'=>$_REQUEST['mid']));

$zmuCommand = getZmuCommand(' -m '.escapeshellarg($_REQUEST['mid']).' -B -C -H -O');
$zmuOutput = exec( $zmuCommand );
if ( $zmuOutput ) {
  list($brightness, $contrast, $hue, $colour) = explode(' ', $zmuOutput);

  $monitor->Brightness($brightness);
  $monitor->Contrast($contrast);
  $monitor->Hue($hue);
  $monitor->Colour($colour);
}

?>
<div class="modal" id="settingsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo validHtmlStr($monitor->Name()) ?> - <?php echo translate('Settings') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
      <form name="contentForm" id="settingsForm" method="post" action="?view=watch&mid=<?php echo $monitor->Id() ?>">
        <?php
        // We have to manually insert the csrf key into the form when using a modal generated via ajax call
        echo getCSRFinputHTML();
        ?>
        <input type="hidden" name="action" value="settings"/>
        <table id="contentTable" class="major">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('Brightness') ?></th>
              <td><input type="number" name="newBrightness" value="<?php echo $monitor->Brightness() ?>" <?php if ( !canView( 'Control' ) ) { ?> disabled="disabled"<?php } ?> /></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Contrast') ?></th>
              <td><input type="number" name="newContrast" value="<?php echo $monitor->Contrast() ?>" <?php  echo canView('Control') ? '' : ' disabled="disabled"' ?> /></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Hue') ?></th>
              <td><input type="number" name="newHue" value="<?php echo $monitor->Hue() ?>" <?php echo canView('Control') ? '' : ' disabled="disabled"' ?> /></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('Colour') ?></th>
              <td><input type="number" name="newColour" value="<?php echo $monitor->Colour() ?>" <?php echo canView('Control') ? '' : ' disabled="disabled"' ?> /></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="settingsSubmitModal" value="Save"<?php echo canView('Control') ? '' : ' disabled="disabled"' ?>><?php echo translate('Save') ?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </form>
    </div>
  </div>
</div>
