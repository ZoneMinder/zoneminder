<form name="userForm" method="post" action="?">
  <input type="hidden" name="view" value="<?php echo $view ?>"/>
  <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
  <input type="hidden" name="action" value="delete"/>
  <table id="contentTable" class="table table-striped">
    <thead class="thead-highlight">
      <tr>
        <th class="colUsername"><?php echo translate('Username') ?></th>
        <th class="colLanguage"><?php echo translate('Language') ?></th>
        <th class="colEnabled"><?php echo translate('Enabled') ?></th>
        <th class="colStream"><?php echo translate('Stream') ?></th>
        <th class="colEvents"><?php echo translate('Events') ?></th>
        <th class="colControl"><?php echo translate('Control') ?></th>
        <th class="colMonitors"><?php echo translate('Monitors') ?></th>
        <th class="colGroups"><?php echo translate('Groups') ?></th>
        <th class="colSnapshots"><?php echo translate('Snapshots') ?></th>
        <th class="colSystem"><?php echo translate('System') ?></th>
        <th class="colDevices"><?php echo translate('Devices') ?></th>
        <th class="colBandwidth"><?php echo translate('Bandwidth') ?></th>
        <th class="colMonitor"><?php echo translate('Monitor') ?></th>
        <?php if ( ZM_OPT_USE_API ) { ?><th class="colAPIEnabled"><?php echo translate('APIEnabled') ?></th><?php } ?>
        <th class="colMark"><?php echo translate('Mark') ?></th>
      </tr>
    </thead>
    <tbody>
<?php
  $sql = 'SELECT * FROM Monitors ORDER BY Sequence ASC';
  $monitors = array();
  foreach (dbFetchAll($sql) as $monitor) {
    $monitors[$monitor['Id']] = $monitor;
  }

  $sql = 'SELECT * FROM Users ORDER BY Username';
  foreach (dbFetchAll($sql) as $user_row) {
    $userMonitors = array();
    if (!empty($user_row['MonitorIds'])) {
      foreach ( explode(',', $user_row['MonitorIds']) as $monitorId ) {
        // A deleted monitor will cause an error since we don't update 
        // the user monitors list on monitor delete
        if (!isset($monitors[$monitorId])) continue;
        $userMonitors[] = $monitors[$monitorId]['Name'];
      }
    }
    ?>
      <tr>
        <td class="colUsername"><?php echo makeLink('?view=user&amp;uid='.$user_row['Id'], validHtmlStr($user_row['Username']).($user->Username()==$user_row['Username']?'*':''), $canEdit) ?></td>
        <td class="colLanguage"><?php echo $user_row['Language']?validHtmlStr($user_row['Language']):'default' ?></td>
        <td class="colEnabled"><?php echo translate($user_row['Enabled']?'Yes':'No') ?></td>
        <td class="colStream"><?php echo validHtmlStr($user_row['Stream']) ?></td>
        <td class="colEvents"><?php echo validHtmlStr($user_row['Events']) ?></td>
        <td class="colControl"><?php echo validHtmlStr($user_row['Control']) ?></td>
        <td class="colMonitors"><?php echo validHtmlStr($user_row['Monitors']) ?></td>
        <td class="colGroups"><?php echo validHtmlStr($user_row['Groups']) ?></td>
        <td class="colSnapshots"><?php echo validHtmlStr($user_row['Snapshots']) ?></td>
        <td class="colSystem"><?php echo validHtmlStr($user_row['System']) ?></td>
        <td class="colDevices"><?php echo validHtmlStr($user_row['Devices']) ?></td>
        <td class="colBandwidth"><?php echo $user_row['MaxBandwidth']?$bandwidth_options[$user_row['MaxBandwidth']]:'&nbsp;' ?></td>
        <td class="colMonitor"><?php echo count($userMonitors)?(join(', ', $userMonitors)):'&nbsp;' ?></td>
        <?php if ( ZM_OPT_USE_API ) { ?><td class="colAPIEnabled"><?php echo translate($user_row['APIEnabled']?'Yes':'No') ?></td><?php } ?>
        <td class="colMark"><input type="checkbox" name="markUids[]" value="<?php echo $user_row['Id'] ?>" data-on-click-this="configureDeleteButton"<?php echo (!$canEdit) ? ' disabled="disabled"' : '' ?>/></td>
      </tr>
<?php
}
?>
    </tbody>
  </table>
  <div id="contentButtons">
    <?php echo makeButton('?view=user&uid=0', 'AddNewUser', $canEdit); ?>
    <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
  </div>
</form>
