<?php
// This is the HTML representing the AI Model modal from Options -> AI Models
if ( !isset($_REQUEST['id']) ) {
  ajaxError('Model Id Not Provided');
  return;
}

$result = '';
$checked = ' checked="checked"';
$null = '';
$mid = validCardinal($_REQUEST['id']);

if ( !canEdit('System') ) return;

if ( $mid ) {
  $model = dbFetchOne('SELECT * FROM AI_Models WHERE Id=?', NULL, [$mid]);
  if ( !$model ) return;
} else {
  $model = array(
    'Id' => 0,
    'Name' => '',
    'Description' => '',
    'ModelPath' => '',
    'Framework' => 'ONNX',
    'Version' => '',
    'DatasetId' => null,
    'Enabled' => 0
  );
}

// Get datasets for dropdown
$datasets = array('' => translate('None'));
$result = dbQuery('SELECT Id, Name FROM AI_Datasets ORDER BY Name');
if ($result) {
  while ($row = dbFetchNext($result)) {
    $datasets[$row['Id']] = $row['Name'];
  }
}

$framework_options = array(
  'TensorFlow' => 'TensorFlow',
  'PyTorch' => 'PyTorch',
  'ONNX' => 'ONNX',
  'OpenVINO' => 'OpenVINO',
  'TensorRT' => 'TensorRT',
  'Other' => 'Other'
);

?>
<div class="modal fade" id="ModelModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('AIModel') .' - '. validHtmlStr($model['Name']) ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="modelModalForm" name="contentForm" method="post" action="?view=ai_model" class="validateFormOnSubmit">
        <div class="modal-body">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          ?>
          <input type="hidden" name="object" value="ai_model"/>
          <input type="hidden" name="id" value="<?php echo $mid ?>"/>
          <table class="table-sm">
            <tbody>
              <tr class="Name">
                <th scope="row"><?php echo translate('Name') ?></th>
                <td><input type="text" name="newModel[Name]" value="<?php echo validHtmlStr($model['Name']) ?>" required/></td>
              </tr>
              <tr class="Framework">
                <th scope="row"><?php echo translate('Framework') ?></th>
                <td><?php echo htmlSelect('newModel[Framework]', $framework_options, $model['Framework']) ?></td>
              </tr>
              <tr class="Version">
                <th scope="row"><?php echo translate('Version') ?></th>
                <td><input type="text" name="newModel[Version]" value="<?php echo validHtmlStr($model['Version']) ?>"/></td>
              </tr>
              <tr class="Dataset">
                <th scope="row"><?php echo translate('Dataset') ?></th>
                <td><?php echo htmlSelect('newModel[DatasetId]', $datasets, $model['DatasetId']) ?></td>
              </tr>
              <tr class="ModelPath">
                <th scope="row"><?php echo translate('ModelPath') ?></th>
                <td><input type="text" name="newModel[ModelPath]" value="<?php echo validHtmlStr($model['ModelPath']) ?>"/></td>
              </tr>
              <tr class="Enabled">
                <th scope="row"><?php echo translate('Enabled') ?></th>
                <td>
                  <input type="radio" name="newModel[Enabled]" value="1" <?php echo $model['Enabled'] ? $checked : $null ?>/> <?php echo translate('Yes') ?>
                  <input type="radio" name="newModel[Enabled]" value="0" <?php echo $model['Enabled'] ? $null : $checked ?>/> <?php echo translate('No') ?>
                </td>
              </tr>
              <tr class="Description">
                <th scope="row"><?php echo translate('Description') ?></th>
                <td><textarea name="newModel[Description]" rows="3"><?php echo validHtmlStr($model['Description']) ?></textarea></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button name="action" id="modelSubmitBtn" type="submit" class="btn btn-primary" value="save"><?php echo translate('Save') ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
