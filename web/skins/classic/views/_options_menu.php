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
    <div class="col button-block">
      <div id="contentButtons">
<?php if ($canEdit) { ?>
        <button type="submit" class="btn btn-primary">
          <i class="material-icons">save</i>
          <span class="text"><?php echo translate('Save') ?></span>
        </button>
        <button type="button" id="sortMenuBtn" class="btn btn-secondary" data-on-click-this="sortMenuItems">
          <i class="material-icons" title="<?php echo translate('Click and drag rows to change order') ?>">swap_vert</i>
          <span class="text"><?php echo translate('Sort') ?></span>
        </button>
        <button type="submit" name="action" value="resetmenu" class="btn btn-warning"
          onclick="return confirm('<?php echo addslashes(translate('Reset menu items to defaults?')) ?>');"
        ><i class="material-icons">restart_alt</i>
          <span class="text"><?php echo translate('Reset') ?></span>
        </button>
<?php } ?>
      </div>
    </div>
    <div class="wrapper-scroll-table">
        <div class="col">
          <table class="table table-striped" id="menuItemsTable">
            <thead>
              <tr>
                <th class="text-left"><?php echo translate('Enabled') ?></th>
                <th class="text-left"><?php echo translate('Name') ?></th>
                <th class="text-left"><?php echo translate('Custom Label') ?></th>
                <th class="text-left"><?php echo translate('Icon') ?></th>
              </tr>
            </thead>
            <tbody id="menuItemsBody">
<?php foreach ($menuItems as $item) {
  $id = $item->Id();
  $effIcon = $item->effectiveIcon();
  $effIconType = $item->effectiveIconType();
  $hasCustomIcon = ($item->Icon() !== null && $item->Icon() !== '');
?>
              <tr id="menuItem-<?php echo $id ?>">
                <td>
                  <input type="hidden" name="items[<?php echo $id ?>][Id]" value="<?php echo $id ?>"/>
                  <input type="hidden" name="items[<?php echo $id ?>][SortOrder]" class="sortOrderInput" value="<?php echo $item->SortOrder() ?>"/>
                  <input type="checkbox" name="items[<?php echo $id ?>][Enabled]" value="1"
                    <?php echo $item->Enabled() ? 'checked' : '' ?>
                    <?php echo !$canEdit ? 'disabled' : '' ?>
                  />
                </td>
                <td class="text-left"><?php echo htmlspecialchars(translate($item->MenuKey())) ?></td>
                <td>
                  <input type="text" name="items[<?php echo $id ?>][Label]"
                    value="<?php echo htmlspecialchars($item->Label() ?? '') ?>"
                    placeholder="<?php echo htmlspecialchars(translate($item->MenuKey())) ?>"
                    <?php echo !$canEdit ? 'disabled' : '' ?>
                  />
                </td>
                <td class="text-left">
                  <div class="d-flex align-items-center" style="gap:6px;">
                    <span class="menuIconPreview" id="iconPreview-<?php echo $id ?>">
<?php if ($effIconType == 'fontawesome') { ?>
                      <i class="fa <?php echo htmlspecialchars($effIcon) ?>"></i>
<?php } else if ($effIconType == 'image') { ?>
                      <img src="<?php echo htmlspecialchars($effIcon) ?>" style="width:24px;height:24px;object-fit:contain;" alt=""/>
<?php } else { ?>
                      <i class="material-icons"><?php echo htmlspecialchars($effIcon) ?></i>
<?php } ?>
                    </span>
                    <select name="items[<?php echo $id ?>][IconType]" class="form-control form-control-sm iconTypeSelect"
                      data-item-id="<?php echo $id ?>"
                      style="width:auto;display:inline-block;"
                      <?php echo !$canEdit ? 'disabled' : '' ?>
                    >
                      <option value="material" <?php echo $effIconType == 'material' ? 'selected' : '' ?>>Material</option>
                      <option value="fontawesome" <?php echo $effIconType == 'fontawesome' ? 'selected' : '' ?>>Font Awesome</option>
                      <option value="image" <?php echo $effIconType == 'image' ? 'selected' : '' ?>>Image</option>
                      <option value="none" <?php echo $effIconType == 'none' ? 'selected' : '' ?>>None</option>
                    </select>
                    <input type="text" name="items[<?php echo $id ?>][Icon]" class="form-control form-control-sm iconNameInput"
                      id="iconName-<?php echo $id ?>"
                      value="<?php echo htmlspecialchars($hasCustomIcon ? $item->Icon() : '') ?>"
                      placeholder="<?php echo $effIconType == 'image' ? 'graphics/menu/icon.png' : htmlspecialchars($effIcon) ?>"
                      style="width:<?php echo $effIconType == 'image' ? '200' : '140' ?>px;<?php echo $effIconType == 'none' ? 'display:none;' : '' ?>"
                      <?php echo !$canEdit ? 'disabled' : '' ?>
                    />
                  </div>
                </td>
              </tr>
<?php } ?>
            </tbody>
          </table>
        </div>
    </div>
  </div>
</form>
