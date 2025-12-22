<form name="datasetsForm" method="post" action="?">
  <div id="options">
    <div class="row">
      <input type="hidden" name="view" value="<?php echo $view ?>"/>
      <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="object" value="ai_dataset"/>
      <div class="col">
        <div id="contentButtons">
          <button type="button" id="NewDatasetBtn" value="<?php echo translate('AddNewDataset') ?>" disabled="disabled"><?php echo translate('AddNewDataset') ?></button>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </div> <!-- .col -->
    </div> <!-- .row -->
    <div class="wrapper-scroll-table">
      <div class="row">
        <div class="col">
          <table id="contentTable" class="table table-striped">
            <thead class="thead-highlight">
              <tr>
                <th class="colMark"><?php echo translate('Mark') ?></th>
                <th data-sortable="true" class="colId"><?php echo translate('Id') ?></th>
                <th data-sortable="true" class="colName"><?php echo translate('Name') ?></th>
                <th data-sortable="true" class="colVersion"><?php echo translate('Version') ?></th>
                <th data-sortable="true" class="colNumClasses"><?php echo translate('NumClasses') ?></th>
                <th data-sortable="true" class="colDescription"><?php echo translate('Description') ?></th>
              </tr>
            </thead>
            <tbody>
<?php
$result = dbQuery('SELECT * FROM AI_Datasets ORDER BY Name');
if ($result) {
  while ($row = dbFetchNext($result)) {
    $dataset_opt = 'class="datasetCol" data-did="'.$row['Id'].'"';
?>
              <tr>
                <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $row['Id'] ?>" data-on-click-this="configureDeleteButton"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
                <td class="colId"><?php echo makeLink('#', validHtmlStr($row['Id']), $canEdit, $dataset_opt) ?></td>
                <td class="colName"><?php echo makeLink('#', validHtmlStr($row['Name']), $canEdit, $dataset_opt) ?></td>
                <td class="colVersion"><?php echo makeLink('#', validHtmlStr($row['Version']), $canEdit, $dataset_opt) ?></td>
                <td class="colNumClasses"><?php echo makeLink('#', validHtmlStr($row['NumClasses']), $canEdit, $dataset_opt) ?></td>
                <td class="colDescription"><?php echo makeLink('#', validHtmlStr($row['Description']), $canEdit, $dataset_opt) ?></td>
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
