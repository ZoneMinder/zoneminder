<?php
if (!canView('Control')) return;

$mid = validCardinal($_REQUEST['mid']);
if (!$mid) return;

$monitor = ZM\Monitor::find_one(array('Id'=>$mid));
if (!$monitor) return;

$zmuCommand = getZmuCommand(' -m '.escapeshellarg($mid).' -B -C -H -O');
$zmuOutput = exec( $zmuCommand );
if ($zmuOutput) {
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
      <form name="contentForm" id="settingsForm" method="post" action="?">
        <?php
        // We have to manually insert the csrf key into the form when using a modal generated via ajax call
        echo getCSRFinputHTML();
        ?>
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="settings"/>
        <input type="hidden" name="mid" value="<?php echo $mid; ?>"/>
        <table id="contentTable" class="major">
          <tbody>
<?php
$ctls = shell_exec('v4l2-ctl -d '.$monitor->Device().' --list-ctrls');

if (!$ctls) {
ZM\Warning('Guessing v4l ctrls.  We need v4l2-ctl please install it');
$ctls = '
                     brightness 0x00980900 (int)    : min=-10 max=10 step=1 default=0 value=8
                       contrast 0x00980901 (int)    : min=0 max=20 step=1 default=10 value=12
                     saturation 0x00980902 (int)    : min=0 max=10 step=1 default=7 value=6
                            hue 0x00980903 (int)    : min=-5000 max=5000 step=1000 default=0 value=2000
';
}
$ctls = trim($ctls);
$ctls = explode("\n", $ctls);

foreach ($ctls as $line) {
  $ctl = explode(':', $line);
  $type_info = explode(' ', trim($ctl[0]));

  $setting = trim($type_info[0]);
  if ($setting == 'saturation')
    $setting = 'colour';
  $setting_uc = ucwords($setting);
  $type = $type[2];

  $min = '';
  $max = '';
  $step = '';
  $value = '';
  $default = '';

  # The purpose is security
  foreach (explode(' ', trim($ctl[1])) as $index=>$prop) {
    list($key,$val) = explode('=', $prop);

    // get current value
    if ($key == 'value') {
      $value = validInt($val);
    } else if ($key == 'default') {
      $default = validInt($val);
    } else if ($key == 'min') {
      $min = validInt($val);
    } else if ($key == 'max') {
      $max = validInt($val);
    } else if ($key == 'step') {
      $step = validInt($val);
    }
  }

  $label = translate($setting_uc);
  if ($label == $setting_uc) {
    $label = ucwords(str_replace('_', ' ', $label));
  }

  if ($setting == 'brightness' or $setting == 'colour' or $setting == 'contrast' or $setting == 'hue') {
    echo '
            <tr>
              <th scope="row">'.$label.'</th>
              <td>'.$min.'</td><td><input type="range" title="'.$value.'" min="'.$min.'" max="'.$max.'" step="'.$step.'" default="'.$default.'" value="'.$value.'" id="new'.$setting_uc.'" name="new'.$setting_uc.'" '.(canEdit('Control') ? '' : 'disabled="disabled"') .'/></td><td>'.$max.'</td>
            </tr>
';
  } else {
    if ($type == '(bool)') {
    echo '
            <tr>
              <th scope="row">'.$label.'</th>
              <td></td><td>'.html_radio('new'.$setting_uc, array('0'=>translate('True'), '1', translate('False')), $value, array('disabled'=>'disabled')).'
              </td><td></td>
            </tr>
';
    } else if ($type == '(int)') {
    echo '
            <tr>
              <th scope="row">'.$label.'</th>
              <td></td><td><input type="range" '.$ctl[1].' disabled="disabled"/></td><td></td>
            </tr>
';
    } else {
    echo '
            <tr>
              <th scope="row">'.$label.'</th>
              <td></td><td>'.$value.'</td><td></td>
            </tr>
';
    }
  }
} # end foreach ctrl
?>
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
