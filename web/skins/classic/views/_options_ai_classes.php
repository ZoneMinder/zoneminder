<form name="classesForm" method="post" action="?">
  <div id="options">
    <div class="row">
      <input type="hidden" name="view" value="<?php echo $view ?>"/>
      <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="object" value="ai_class"/>
      <div class="col">
        <div id="contentButtons">
          <button type="button" id="NewClassBtn" value="<?php echo translate('AddNewClass') ?>" disabled="disabled"><?php echo translate('AddNewClass') ?></button>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </div> <!-- .col -->
    </div> <!-- .row -->
    <div class="row mb-3">
      <div class="col-md-4">
        <label for="datasetFilter"><?php echo translate('FilterByDataset') ?>:</label>
        <select id="datasetFilter" class="form-control">
          <option value=""><?php echo translate('AllDatasets') ?></option>
<?php
$datasets_result = dbQuery('SELECT Id, Name FROM AI_Datasets ORDER BY Name');
if ($datasets_result) {
  while ($dataset_row = dbFetchNext($datasets_result)) {
    echo '<option value="'.$dataset_row['Id'].'">'.validHtmlStr($dataset_row['Name']).'</option>';
  }
}
?>
        </select>
      </div>
    </div>
    <div class="wrapper-scroll-table">
      <div class="row">
        <div class="col">
          <table id="contentTable" class="table table-striped">
            <thead class="thead-highlight">
              <tr>
                <th class="colMark"><?php echo translate('Mark') ?></th>
                <th data-sortable="true" class="colId"><?php echo translate('Id') ?></th>
                <th data-sortable="true" class="colDataset"><?php echo translate('Dataset') ?></th>
                <th data-sortable="true" class="colClassName"><?php echo translate('ClassName') ?></th>
                <th data-sortable="true" class="colClassIndex"><?php echo translate('ClassIndex') ?></th>
                <th data-sortable="true" class="colDescription"><?php echo translate('Description') ?></th>
              </tr>
            </thead>
            <tbody>
<?php
$result = dbQuery('SELECT c.*, d.Name as DatasetName FROM AI_Object_Classes c LEFT JOIN AI_Datasets d ON c.DatasetId = d.Id ORDER BY d.Name, c.ClassIndex');
if ($result) {
  while ($row = dbFetchNext($result)) {
    $class_opt = 'class="classCol" data-cid="'.$row['Id'].'" data-dataset-id="'.$row['DatasetId'].'"';
?>
              <tr>
                <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $row['Id'] ?>" data-on-click-this="configureDeleteButton"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
                <td class="colId"><?php echo makeLink('#', validHtmlStr($row['Id']), $canEdit, $class_opt) ?></td>
                <td class="colDataset"><?php echo makeLink('#', validHtmlStr($row['DatasetName']), $canEdit, $class_opt) ?></td>
                <td class="colClassName"><?php echo makeLink('#', validHtmlStr($row['ClassName']), $canEdit, $class_opt) ?></td>
                <td class="colClassIndex"><?php echo makeLink('#', validHtmlStr($row['ClassIndex']), $canEdit, $class_opt) ?></td>
                <td class="colDescription"><?php echo makeLink('#', validHtmlStr($row['Description']), $canEdit, $class_opt) ?></td>
              </tr>
<?php
  }
}
?>
           </tbody>
          </table>
        </div> <!-- .col -->
      </div> <!-- .row -->
    </div> <!-- .wrapper-scroll-table -->
  </div> <!-- .options -->
</form>
