<?php
// This is the HTML representing the AI Dataset modal from Options -> AI Datasets
if ( !isset($_REQUEST['id']) ) {
  ajaxError('Dataset Id Not Provided');
  return;
}

$result = '';
$checked = ' checked="checked"';
$null = '';
$did = validCardinal($_REQUEST['id']);

if ( !canEdit('System') ) return;

if ( $did ) {
  $dataset = dbFetchOne('SELECT * FROM AI_Datasets WHERE Id=?', NULL, [$did]);
  if ( !$dataset ) return;
} else {
  $dataset = array(
    'Id' => 0,
    'Name' => '',
    'Description' => '',
    'Version' => '',
    'NumClasses' => 0
  );
}

?>
<div class="modal fade" id="DatasetModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('AIDataset') .' - '. validHtmlStr($dataset['Name']) ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="datasetModalForm" name="contentForm" method="post" action="?view=ai_dataset" class="validateFormOnSubmit">
        <div class="modal-body">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          ?>
          <input type="hidden" name="object" value="ai_dataset"/>
          <input type="hidden" name="id" value="<?php echo $did ?>"/>
          <table class="table-sm">
            <tbody>
              <tr class="Name">
                <th scope="row"><?php echo translate('Name') ?></th>
                <td><input type="text" name="newDataset[Name]" value="<?php echo validHtmlStr($dataset['Name']) ?>" required/></td>
              </tr>
              <tr class="Version">
                <th scope="row"><?php echo translate('Version') ?></th>
                <td><input type="text" name="newDataset[Version]" value="<?php echo validHtmlStr($dataset['Version']) ?>"/></td>
              </tr>
              <tr class="NumClasses">
                <th scope="row"><?php echo translate('NumClasses') ?></th>
                <td><input type="number" name="newDataset[NumClasses]" value="<?php echo validCardinal($dataset['NumClasses']) ?>" step="1" min="0" required/></td>
              </tr>
              <tr class="Description">
                <th scope="row"><?php echo translate('Description') ?></th>
                <td><textarea name="newDataset[Description]" rows="3"><?php echo validHtmlStr($dataset['Description']) ?></textarea></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button name="action" id="datasetSubmitBtn" type="submit" class="btn btn-primary" value="save"><?php echo translate('Save') ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
