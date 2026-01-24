<form name="modelsForm" method="post" action="?">
  <div id="options">
    <div class="row">
      <input type="hidden" name="view" value="<?php echo $view ?>"/>
      <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="object" value="ai_model"/>
      <div class="col">
        <div id="contentButtons">
          <button type="button" id="NewModelBtn" value="<?php echo translate('AddNewModel') ?>" disabled="disabled"><?php echo translate('AddNewModel') ?></button>
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
                <th data-sortable="true" class="colFramework"><?php echo translate('Framework') ?></th>
                <th data-sortable="true" class="colVersion"><?php echo translate('Version') ?></th>
                <th data-sortable="true" class="colDataset"><?php echo translate('Dataset') ?></th>
                <th data-sortable="true" class="colModelPath"><?php echo translate('ModelPath') ?></th>
                <th data-sortable="true" class="colEnabled"><?php echo translate('Enabled') ?></th>
                <th data-sortable="true" class="colDescription"><?php echo translate('Description') ?></th>
              </tr>
            </thead>
            <tbody>
<?php
$result = dbQuery('SELECT m.*, d.Name as DatasetName FROM AI_Models m LEFT JOIN AI_Datasets d ON m.DatasetId = d.Id ORDER BY m.Name');
if ($result) {
  while ($row = dbFetchNext($result)) {
    $model_opt = 'class="modelCol" data-mid="'.$row['Id'].'"';
?>
              <tr>
                <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $row['Id'] ?>" data-on-click-this="configureDeleteButton"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
                <td class="colId"><?php echo makeLink('#', validHtmlStr($row['Id']), $canEdit, $model_opt) ?></td>
                <td class="colName"><?php echo makeLink('#', validHtmlStr($row['Name']), $canEdit, $model_opt) ?></td>
                <td class="colFramework"><?php echo makeLink('#', validHtmlStr($row['Framework']), $canEdit, $model_opt) ?></td>
                <td class="colVersion"><?php echo makeLink('#', validHtmlStr($row['Version']), $canEdit, $model_opt) ?></td>
                <td class="colDataset"><?php echo makeLink('#', validHtmlStr($row['DatasetName']), $canEdit, $model_opt) ?></td>
                <td class="colModelPath"><?php echo makeLink('#', validHtmlStr($row['ModelPath']), $canEdit, $model_opt) ?></td>
                <td class="colEnabled"><?php echo makeLink('#', $row['Enabled'] ? translate('Yes') : translate('No'), $canEdit, $model_opt) ?></td>
                <td class="colDescription"><?php echo makeLink('#', validHtmlStr($row['Description']), $canEdit, $model_opt) ?></td>
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
