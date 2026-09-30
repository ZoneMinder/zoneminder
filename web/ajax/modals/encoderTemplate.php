<?php
// Modal for the EncoderTemplates editor.
// Called via /index.php?view=request&request=modal&modal=encoderTemplate&id=<id>
//   or with id=0 / no id for create mode.

if (!canEdit('System')) {
  ajaxError('Insufficient privileges');
  return;
}

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$tmpl = null;
if ($id) {
  $row = dbFetchOne('SELECT * FROM EncoderTemplates WHERE Id = ?', null, array($id));
  if (!$row) {
    ajaxError('Invalid template id');
    return;
  }
  $tmpl = $row;
}

$encoders = array(
  'libx264' => 'libx264',
  'libx265' => 'libx265',
  'h264_nvenc' => 'h264_nvenc',
  'hevc_nvenc' => 'hevc_nvenc',
  'h264_vaapi' => 'h264_vaapi',
  'hevc_vaapi' => 'hevc_vaapi',
);
?>
<div class="modal fade" id="EncoderTemplateModal" tabindex="-1" aria-labelledby="encoderTemplateModalTitle" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="encoderTemplateModalTitle">
          <?php echo $tmpl ? translate('EditEncoderTemplate') : translate('NewEncoderTemplate') ?>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="encoderTemplateError" class="alert alert-danger" style="display:none"></div>
        <input type="hidden" id="EtId" value="<?php echo $tmpl ? $tmpl['Id'] : '' ?>"/>
        <div class="form-group">
          <label for="EtEncoder"><?php echo translate('Encoder') ?></label>
          <select id="EtEncoder" name="EtEncoder"<?php if ($tmpl) echo ' disabled' ?>>
<?php foreach ($encoders as $val => $label) {
  $selected = ($tmpl && $tmpl['Encoder'] == $val) ? ' selected="selected"' : '';
?>            <option value="<?php echo $val ?>"<?php echo $selected ?>><?php echo $label ?></option>
<?php } ?>
          </select>
        </div>
        <div class="form-group">
          <label for="EtName"><?php echo translate('Name') ?></label>
          <input type="text" id="EtName" maxlength="64" value="<?php echo $tmpl ? validHtmlStr($tmpl['Name']) : '' ?>"/>
        </div>
        <div class="form-group">
          <label for="EtDescription"><?php echo translate('Description') ?></label>
          <textarea id="EtDescription" rows="2"><?php echo $tmpl ? validHtmlStr($tmpl['Description'] ?? '') : '' ?></textarea>
        </div>
        <div class="form-group">
          <label for="EtParams"><?php echo translate('Params') ?></label>
          <textarea id="EtParams" rows="6"><?php echo $tmpl ? validHtmlStr($tmpl['Params']) : '' ?></textarea>
          <div id="EtParamsDiagnostics" class="encoderParameterDiagnostics"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        <button type="button" class="btn btn-primary" id="EtSave"><?php echo translate('Save') ?></button>
      </div>
    </div>
  </div>
</div>
