<?php
//
// ZoneMinder Options - Encoder Templates tab
// Editor for the EncoderTemplates DB table.
//
require_once('includes/EncoderTemplates.php');

$encoderFilter = isset($_REQUEST['encoderFilter']) ? validHtmlStr($_REQUEST['encoderFilter']) : '';
$canEdit = canEdit('System');
$dict = ZM\EncoderTemplates::all();

// Hardcoded encoder list — same as monitor.php
$encoders = array(
  '' => translate('AllEncoders'),
  'libx264' => 'libx264',
  'libx265' => 'libx265',
  'h264_nvenc' => 'h264_nvenc',
  'hevc_nvenc' => 'hevc_nvenc',
  'h264_vaapi' => 'h264_vaapi',
  'hevc_vaapi' => 'hevc_vaapi',
);
?>
<div id="options">
  <h2><?php echo translate('EncoderTemplates') ?></h2>
  <p><?php echo translate('EncoderTemplatesDescription') ?></p>

  <div class="col">
    <label for="encoderFilter"><?php echo translate('FilterByEncoder') ?></label>
    <?php echo htmlSelect('encoderFilter', $encoders, $encoderFilter, array('id'=>'encoderFilter')) ?>
  </div>

  <div class="col button-block">
    <div id="contentButtons">
      <button type="button" id="NewEncoderTemplateBtn"<?php if (!$canEdit) echo ' disabled="disabled"' ?>><?php echo translate('AddNewEncoderTemplate') ?></button>
    </div>
  </div>

  <div class="wrapper-scroll-table">
    <div class="col">
      <table id="contentTable" class="table table-striped"
          data-show-export="true"
          data-show-columns="true"
          data-cookie="true"
          data-cookie-id-table="zmEncoderTemplatesTable"
          data-cookie-expire="2y"
      >
        <thead class="thead-highlight">
          <tr>
            <th data-sortable="true" class="colId"><?php echo translate('Id') ?></th>
            <th data-sortable="true" class="colEncoder"><?php echo translate('Encoder') ?></th>
            <th data-sortable="true" class="colName"><?php echo translate('Name') ?></th>
            <th data-sortable="true" class="colParams"><?php echo translate('Params') ?></th>
            <th class="colActions"><?php echo translate('Actions') ?></th>
          </tr>
        </thead>
        <tbody>
<?php
foreach ($dict as $enc => $entry) {
  foreach ($entry['templates'] as $tmpl) {
    $paramsLines = [];
    foreach ($tmpl['params'] as $k => $v) {
      $paramsLines[] = $k.'='.$v;
    }
    $paramsFull = implode("\n", $paramsLines);
    $paramsShort = mb_strimwidth(implode(' ', $paramsLines), 0, 60, '…');
?>
          <tr data-tid="<?php echo $tmpl['id'] ?>" data-encoder="<?php echo validHtmlStr($enc) ?>" data-name="<?php echo validHtmlStr($tmpl['name']) ?>">
            <td class="colId"><?php echo $tmpl['id'] ?></td>
            <td class="colEncoder"><?php echo validHtmlStr($enc) ?></td>
            <td class="colName"><?php echo validHtmlStr($tmpl['name']) ?></td>
            <td class="colParams" title="<?php echo validHtmlStr($paramsFull) ?>"><?php echo validHtmlStr($paramsShort) ?></td>
            <td class="colActions">
              <button type="button" class="btn-edit" data-tid="<?php echo $tmpl['id'] ?>"<?php if (!$canEdit) echo ' disabled="disabled"' ?>><?php echo translate('Edit') ?></button>
              <button type="button" class="btn-copy" data-tid="<?php echo $tmpl['id'] ?>"<?php if (!$canEdit) echo ' disabled="disabled"' ?>><?php echo translate('Copy') ?></button>
              <button type="button" class="btn-delete" data-tid="<?php echo $tmpl['id'] ?>"<?php if (!$canEdit) echo ' disabled="disabled"' ?>><?php echo translate('Delete') ?></button>
            </td>
          </tr>
<?php
  }
}
?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit modal placeholder; populated client-side by options-encoder-templates.js -->
<div id="encoderTemplateModalContainer"></div>

<script nonce="<?php echo $cspNonce ?>">
window.ZM_ENCODER_TEMPLATES = <?php echo json_encode($dict, JSON_UNESCAPED_SLASHES); ?>;
window.addEventListener('DOMContentLoaded', function() {
  $j('#contentTable').bootstrapTable({icons: icons}).show();
});
</script>
<script src="<?php echo cache_bust('skins/classic/views/js/monitor-encoder-templates.js') ?>"></script>
<script src="<?php echo cache_bust('skins/classic/views/js/options-encoder-templates.js') ?>"></script>
