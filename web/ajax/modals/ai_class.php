<?php
// This is the HTML representing the AI Class modal from Options -> AI
if (!isset($_REQUEST['id'])) {
  ajaxError('AI Class Id Not Provided');
  return;
}

$result = '';
$checked = ' checked="checked"';
$null = '';
$cid = validCardinal($_REQUEST['id']);

if (!canEdit('System')) return;

// Fetch AI Object Class data
$aiClass = array();
if ($cid) {
  $aiClass = dbFetchOne('SELECT * FROM AI_Object_Classes WHERE Id=?', NULL, array($cid));
  if (!$aiClass) {
    ajaxError('AI Class not found');
    return;
  }
}

// Fetch available AI Models
$models = array();
$modelResult = dbQuery('SELECT Id, Name FROM AI_Models ORDER BY Name');
if ($modelResult) {
  while ($row = dbFetchNext($modelResult)) {
    $models[] = $row;
  }
}

?>
<div class="modal fade" id="AIClassModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('AIClass') . ($cid ? ' - ' . validHtmlStr($aiClass['ClassName']) : ' - ' . translate('New')) ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="aiClassModalForm" name="contentForm" method="post" action="?view=ai_class" class="validateFormOnSubmit">
        <div class="modal-body">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          ?>
          <input type="hidden" name="object" value="ai_class"/>
          <input type="hidden" name="id" value="<?php echo $cid ?>"/>
          <table class="table-sm">
            <tbody>
              <tr class="Model">
                <th scope="row"><?php echo translate('Model') ?></th>
                <td>
                  <select name="newAIClass[ModelId]" required>
                    <option value="">-- <?php echo translate('Select') ?> --</option>
<?php
foreach ($models as $model) {
  $selected = ($cid && $aiClass['ModelId'] == $model['Id']) ? ' selected="selected"' : '';
  echo '<option value="' . $model['Id'] . '"' . $selected . '>' . validHtmlStr($model['Name']) . '</option>'.PHP_EOL;
}
?>
                  </select>
                </td>
              </tr>
              <tr class="ClassName">
                <th scope="row"><?php echo translate('ClassName') ?></th>
                <td><input type="text" name="newAIClass[ClassName]" value="<?php echo $cid ? validHtmlStr($aiClass['ClassName']) : '' ?>" required/></td>
              </tr>
              <tr class="ClassIndex">
                <th scope="row"><?php echo translate('ClassIndex') ?></th>
                <td><input type="number" name="newAIClass[ClassIndex]" value="<?php echo $cid ? validInt($aiClass['ClassIndex']) : '' ?>" step="1" min="0" required/></td>
              </tr>
              <tr class="Description">
                <th scope="row"><?php echo translate('Description') ?></th>
                <td><textarea name="newAIClass[Description]" rows="3"><?php echo $cid ? validHtmlStr($aiClass['Description']) : '' ?></textarea></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button name="action" id="aiClassSubmitBtn" type="submit" class="btn btn-primary" value="save"><?php echo translate('Save') ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
