<?php
require_once('includes/MenuItem.php');
$menuItems = ZM\MenuItem::find([], ['order' => 'SortOrder ASC']);
$canEdit = canEdit('System');
?>
<form name="menuItemsForm" method="post" action="?">
  <input type="hidden" name="view" value="options"/>
  <input type="hidden" name="tab" value="menu"/>
  <input type="hidden" name="action" value="menuitems"/>
  <div id="options">
    <div class="row pb-2">
      <div class="col">
        <div id="contentButtons">
<?php if ($canEdit) { ?>
          <button type="submit" class="btn btn-primary"><?php echo translate('Save') ?></button>
          <button type="button" id="sortMenuBtn" data-on-click-this="sortMenuItems">
            <i class="material-icons" title="<?php echo translate('Click and drag rows to change order') ?>">swap_vert</i>
            <span class="text"><?php echo translate('Sort') ?></span>
          </button>
          <button type="submit" name="action" value="resetmenu" class="btn btn-warning"
            onclick="return confirm('<?php echo addslashes(translate('Reset menu items to defaults?')) ?>');"
          ><?php echo translate('Reset') ?></button>
<?php } ?>
        </div>
      </div>
    </div>
    <div class="wrapper-scroll-table">
      <div class="row">
        <div class="col">
          <table class="table table-striped" id="menuItemsTable">
            <thead>
              <tr>
                <th class="text-left"><?php echo translate('Enabled') ?></th>
                <th class="text-left"><?php echo translate('Name') ?></th>
                <th class="text-left"><?php echo translate('Custom Label') ?></th>
              </tr>
            </thead>
            <tbody id="menuItemsBody">
<?php foreach ($menuItems as $item) { ?>
              <tr id="menuItem-<?php echo $item->Id() ?>">
                <td>
                  <input type="hidden" name="items[<?php echo $item->Id() ?>][Id]" value="<?php echo $item->Id() ?>"/>
                  <input type="hidden" name="items[<?php echo $item->Id() ?>][SortOrder]" class="sortOrderInput" value="<?php echo $item->SortOrder() ?>"/>
                  <input type="checkbox" name="items[<?php echo $item->Id() ?>][Enabled]" value="1"
                    <?php echo $item->Enabled() ? 'checked' : '' ?>
                    <?php echo !$canEdit ? 'disabled' : '' ?>
                  />
                </td>
                <td class="text-left"><?php echo htmlspecialchars(translate($item->MenuKey())) ?></td>
                <td>
                  <input type="text" name="items[<?php echo $item->Id() ?>][Label]"
                    value="<?php echo htmlspecialchars($item->Label() ?? '') ?>"
                    placeholder="<?php echo htmlspecialchars(translate($item->MenuKey())) ?>"
                    <?php echo !$canEdit ? 'disabled' : '' ?>
                  />
                </td>
              </tr>
<?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</form>
