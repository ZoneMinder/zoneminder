<?php
require_once('includes/User_Role.php');
?>
<form name="roleForm" method="post" action="?">
  <input type="hidden" name="view" value="<?php echo $view ?>"/>
  <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
  <input type="hidden" name="action" value="delete"/>
  <input type="hidden" name="object" value="role"/>
  <div id="options">
    <div class="row">
      <div class="col">
        <div id="contentButtons">
          <?php echo makeButton('?view=role&rid=0', 'AddNewRole', $canEdit); ?>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </div><!-- .col -->
    </div> <!-- .row -->
    <div class="wrapper-scroll-table">
      <div class="row">
        <div class="col">
          <table id="contentTable"
             class="table-sm table-striped"
             style="display:none;"
             data-click-to-select="true"
             data-check-on-init="true"
             data-mobile-responsive="true"
             data-min-width="562"
             data-show-export="true"
          >
            <thead class="thead-highlight">
              <tr>
                <th class="colMark"><?php echo translate('Mark') ?></th>
                <th data-sortable="true" class="colName"><?php echo translate('Name') ?></th>
                <th data-sortable="true" class="colDescription"><?php echo translate('Description') ?></th>
                <th data-sortable="true" class="colStream"><?php echo translate('Stream') ?></th>
                <th data-sortable="true" class="colEvents"><?php echo translate('Events') ?></th>
                <th data-sortable="true" class="colControl"><?php echo translate('Control') ?></th>
                <th data-sortable="true" class="colMonitors"><?php echo translate('Monitors') ?></th>
                <th data-sortable="true" class="colGroups"><?php echo translate('Groups') ?></th>
                <th data-sortable="true" class="colSnapshots"><?php echo translate('Snapshots') ?></th>
                <th data-sortable="true" class="colSystem"><?php echo translate('System') ?></th>
                <th data-sortable="true" class="colDevices"><?php echo translate('Devices') ?></th>
                <th data-sortable="true" class="colUsers"><?php echo translate('Users') ?></th>
              </tr>
            </thead>
            <tbody>
<?php
  foreach (ZM\User_Role::find([], ['order'=>'Name']) as $role) {
    $users = $role->Users();
    $user_count = count($users);
    ?>
              <tr>
                <td class="colMark"><input type="checkbox" name="markRids[]" value="<?php echo $role->Id() ?>" data-on-click-this="configureDeleteButton"<?php echo (!$canEdit) ? ' disabled="disabled"' : '' ?>/></td>
                <td class="colName"><?php echo makeLink('?view=role&amp;rid='.$role->Id(), validHtmlStr($role->Name()), $canEdit) ?></td>
                <td class="colDescription"><?php echo validHtmlStr($role->Description()) ?></td>
                <td class="colStream"><?php echo validHtmlStr($role->Stream()) ?></td>
                <td class="colEvents"><?php echo validHtmlStr($role->Events()) ?></td>
                <td class="colControl"><?php echo validHtmlStr($role->Control()) ?></td>
                <td class="colMonitors"><?php echo validHtmlStr($role->Monitors()) ?></td>
                <td class="colGroups"><?php echo validHtmlStr($role->Groups()) ?></td>
                <td class="colSnapshots"><?php echo validHtmlStr($role->Snapshots()) ?></td>
                <td class="colSystem"><?php echo validHtmlStr($role->System()) ?></td>
                <td class="colDevices"><?php echo validHtmlStr($role->Devices()) ?></td>
                <td class="colUsers"><?php echo $user_count ?></td>
              </tr>
<?php
  }
?>
            </tbody>
          </table>
        </div><!-- .col -->
      </div><!-- .row -->
    </div><!-- .wrapper-scroll-table -->
  </div> <!-- #options -->
<script nonce="<?php echo $cspNonce ?>">
window.addEventListener("DOMContentLoaded",
 function() {
   $j('#contentTable').bootstrapTable({icons: icons}).show();
});
</script></form>
