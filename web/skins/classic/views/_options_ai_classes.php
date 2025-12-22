<form name="aiClassesForm" method="post" action="?">
  <div id="options">
    <div class="row">
      <input type="hidden" name="view" value="<?php echo $view ?>"/>
      <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="object" value="ai_class"/>
      <div class="col">
        <div id="contentButtons">
          <button type="button" id="NewAIClassBtn" value="<?php echo translate('AddNewAIClass') ?>" disabled="disabled"><?php echo translate('AddNewAIClass') ?></button>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </div> <!-- .col -->
    </div> <!-- .row -->
    <div class="wrapper-scroll-table">
      <div class="row">
        <div class="col overflow-auto">
          <table id="contentTable" class="table table-striped">
            <thead class="thead-highlight">
              <tr>
                <th class="colId"><?php echo translate('Id') ?></th>
                <th class="colModel"><?php echo translate('Model') ?></th>
                <th class="colClassName"><?php echo translate('ClassName') ?></th>
                <th class="colClassIndex"><?php echo translate('ClassIndex') ?></th>
                <th class="colDescription"><?php echo translate('Description') ?></th>
                <th class="colMark"><?php echo translate('Mark') ?></th>
              </tr>
            </thead>
            <tbody>
<?php
  // Fetch AI Object Classes with their model names
  $sql = 'SELECT oc.*, m.Name AS ModelName FROM AI_Object_Classes oc 
          LEFT JOIN AI_Models m ON oc.ModelId = m.Id 
          ORDER BY m.Name, oc.ClassName';
  $result = dbQuery($sql);
  if ($result) {
    while ($row = dbFetchNext($result)) {
      $class_opt = 'class="aiClassCol" data-cid="'.$row['Id'].'"';
?>
              <tr>
                <td class="colId"><?php echo makeLink('#', validHtmlStr($row['Id']), $canEdit, $class_opt) ?></td>
                <td class="colModel"><?php echo makeLink('#', validHtmlStr($row['ModelName']), $canEdit, $class_opt) ?></td>
                <td class="colClassName"><?php echo makeLink('#', validHtmlStr($row['ClassName']), $canEdit, $class_opt) ?></td>
                <td class="colClassIndex"><?php echo makeLink('#', validInt($row['ClassIndex']), $canEdit, $class_opt) ?></td>
                <td class="colDescription"><?php echo makeLink('#', validHtmlStr($row['Description']), $canEdit, $class_opt) ?></td>
                <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $row['Id'] ?>" data-on-click-this="configureDeleteButton"<?php if (!$canEdit) { ?> disabled="disabled"<?php } ?>/></td>
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
