<form name="userForm" method="post" action="?">
  <input type="hidden" name="view" value="<?php echo $view ?>"/>
  <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
  <input type="hidden" name="action" value="delete"/>
  <div id="options">
    <div class="row">
      <div class="col">
        <div id="contentButtons">
          <?php echo makeButton('?view=user&uid=0', 'AddNewUser', $canEdit); ?>
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
                <th data-sortable="true" class="colUsername"><?php echo translate('Username') ?></th>
                <th data-sortable="true" class="colEmail"><?php echo translate('Email') ?></th>
                <th data-sortable="true" class="colLanguage"><?php echo translate('Language') ?></th>
                <th data-sortable="true" class="colEnabled"><?php echo translate('Enabled') ?></th>
                <th data-sortable="true" class="colStream"><?php echo translate('Stream') ?></th>
                <th data-sortable="true" class="colEvents"><?php echo translate('Events') ?></th>
                <th data-sortable="true" class="colControl"><?php echo translate('Control') ?></th>
                <th data-sortable="true" class="colMonitors"><?php echo translate('Monitors') ?></th>
                <th data-sortable="true" class="colGroups"><?php echo translate('Groups') ?></th>
                <th data-sortable="true" class="colSnapshots"><?php echo translate('Snapshots') ?></th>
                <th data-sortable="true" class="colSystem"><?php echo translate('System') ?></th>
                <th data-sortable="true" class="colDevices"><?php echo translate('Devices') ?></th>
                <th data-sortable="true" class="colBandwidth"><?php echo translate('Bandwidth') ?></th>
                <?php if ( ZM_OPT_USE_API ) { ?><th class="colAPIEnabled"><?php echo translate('APIEnabled') ?></th><?php } ?>
              </tr>
            </thead>
            <tbody>
  <?php
    foreach (ZM\User::find([], ['order'=>'Username']) as $user_row) {
      ?>
              <tr>
                <td class="colMark"><input type="checkbox" name="markUids[]" value="<?php echo $user_row->Id() ?>" data-on-click-this="configureDeleteButton"<?php echo (!$canEdit) ? ' disabled="disabled"' : '' ?>/></td>
                <td class="colUsername"><?php echo makeLink('?view=user&amp;uid='.$user_row->Id(), validHtmlStr($user_row->Username()).($user->Username()==$user_row->Username()?'*':''), $canEdit) ?></td>
                <td class="colEmail"><?php echo $user_row->Email()?validHtmlStr($user_row->Email()):'' ?></td>
                <td class="colLanguage"><?php echo $user_row->Language()?validHtmlStr($user_row->Language()):'default' ?></td>
                <td class="colEnabled"><?php echo translate($user_row->Enabled()?'Yes':'No') ?></td>
                <td class="colStream"><?php echo validHtmlStr($user_row->Stream()) ?></td>
                <td class="colEvents"><?php echo validHtmlStr($user_row->Events()) ?></td>
                <td class="colControl"><?php echo validHtmlStr($user_row->Control()) ?></td>
                <td class="colMonitors"><?php echo validHtmlStr($user_row->Monitors()) ?></td>
                <td class="colGroups"><?php echo validHtmlStr($user_row->Groups()) ?></td>
                <td class="colSnapshots"><?php echo validHtmlStr($user_row->Snapshots()) ?></td>
                <td class="colSystem"><?php echo validHtmlStr($user_row->System()) ?></td>
                <td class="colDevices"><?php echo validHtmlStr($user_row->Devices()) ?></td>
                <td class="colBandwidth"><?php echo $user_row->MaxBandwidth()?$bandwidth_options[$user_row->MaxBandwidth()]:'&nbsp;' ?></td>
                <?php if ( ZM_OPT_USE_API ) { ?><td class="colAPIEnabled"><?php echo translate($user_row->APIEnabled()?'Yes':'No') ?></td><?php } ?>
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
