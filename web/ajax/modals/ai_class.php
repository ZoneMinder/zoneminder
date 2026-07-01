<?php
// This is the HTML representing the AI Object Class modal from Options -> AI Classes
if ( !isset($_REQUEST['id']) ) {
  ajaxError('Class Id Not Provided');
  return;
}

$result = '';
$checked = ' checked="checked"';
$null = '';
$cid = validCardinal($_REQUEST['id']);

if ( !canEdit('System') ) return;

if ( $cid ) {
  $class = dbFetchOne('SELECT * FROM AI_Object_Classes WHERE Id=?', NULL, [$cid]);
  if ( !$class ) return;
} else {
  $class = array(
    'Id' => 0,
    'DatasetId' => null,
    'ClassName' => '',
    'ClassIndex' => 0,
    'Description' => ''
  );
}

// Get datasets for dropdown
$datasets = array();
$result = dbQuery('SELECT Id, Name FROM AI_Datasets ORDER BY Name');
if ($result) {
  while ($row = dbFetchNext($result)) {
    $datasets[$row['Id']] = $row['Name'];
  }
}

?>
<div class="modal fade" id="ClassModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('AIObjectClass') .' - '. validHtmlStr($class['ClassName']) ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="classModalForm" name="contentForm" method="post" action="?view=ai_class" class="validateFormOnSubmit">
        <div class="modal-body">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          ?>
          <input type="hidden" name="object" value="ai_class"/>
          <input type="hidden" name="id" value="<?php echo $cid ?>"/>
          <table class="table-sm">
            <tbody>
              <tr class="Dataset">
                <th scope="row"><?php echo translate('Dataset') ?></th>
                <td><?php echo htmlSelect('newClass[DatasetId]', $datasets, $class['DatasetId']) ?></td>
              </tr>
              <tr class="ClassName">
                <th scope="row"><?php echo translate('ClassName') ?></th>
                <td><input type="text" name="newClass[ClassName]" value="<?php echo validHtmlStr($class['ClassName']) ?>" required/></td>
              </tr>
              <tr class="ClassIndex">
                <th scope="row"><?php echo translate('ClassIndex') ?></th>
                <td><input type="number" name="newClass[ClassIndex]" value="<?php echo validCardinal($class['ClassIndex']) ?>" step="1" min="0" required/></td>
              </tr>
              <tr class="Description">
                <th scope="row"><?php echo translate('Description') ?></th>
                <td><textarea name="newClass[Description]" rows="3"><?php echo validHtmlStr($class['Description']) ?></textarea></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button name="action" id="classSubmitBtn" type="submit" class="btn btn-primary" value="save"><?php echo translate('Save') ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
