<form name="storageForm" method="post" action="?">
  <div id="options">
    <input type="hidden" name="view" value="<?php echo $view ?>"/>
    <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
    <input type="hidden" name="action" value="delete"/>
    <input type="hidden" name="object" value="storage"/>
    <div class="row">
      <div class="col">
        <div id="contentButtons">
          <button type="button" id="NewStorageBtn" value="<?php echo translate('AddNewStorage') ?>" disabled="disabled"><?php echo translate('AddNewStorage') ?></button>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </div>
    </div> <!-- .row -->
    <div class="wrapper-scroll-table">
      <div class="row">
        <div class="col overflow-auto">
          <table id="contentTable" class="table table-striped">
            <thead class="thead-highlight">
              <tr>
                <th class="colId"><?php echo translate('Id') ?></th>
                <th class="colName"><?php echo translate('Name') ?></th>
                <th class="colPath"><?php echo translate('Path') ?></th>
                <th class="colType"><?php echo translate('Type') ?></th>
                <th class="colScheme"><?php echo translate('StorageScheme') ?></th>
                <th class="colServer"><?php echo translate('Server') ?></th>
                <th class="colDiskSpace"><?php echo translate('DiskSpace') ?></th>
                <th class="colEvents"><?php echo translate('Events') ?></th>
                <th class="colMark"><?php echo translate('Mark') ?></th>
              </tr>
            </thead>
            <tbody>
<?php
  foreach (ZM\Storage::find(null, array('order'=>'lower(Name)')) as $Storage) { 
    $filter = new ZM\Filter();
    $filter->addTerm(array('attr'=>'StorageId','op'=>'=','val'=>$Storage->Id()));
    if (count($user->unviewableMonitorIds())) {
      $filter = $filter->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'IN', 'val'=>$user->viewableMonitorIds()));
    }

    $str_opt = 'class="storageCol" data-sid="'.$Storage->Id().'"';
  ?>
              <tr>
                <td class="colId"><?php echo makeLink('#', validHtmlStr($Storage->Id()), $canEdit, $str_opt) ?></td>
                <td class="colName"><?php echo makeLink('#', validHtmlStr($Storage->Name()), $canEdit, $str_opt) ?></td>
                <td class="colPath"><?php echo makeLink('#', validHtmlStr($Storage->Path()), $canEdit, $str_opt) ?></td>
                <td class="colType"><?php echo makeLink('#', validHtmlStr($Storage->Type()), $canEdit, $str_opt) ?></td>
                <td class="colScheme"><?php echo makeLink('#', validHtmlStr($Storage->Scheme()), $canEdit, $str_opt) ?></td>
                <td class="colServer"><?php echo makeLink('#', validHtmlStr($Storage->Server()->Name()), $canEdit, $str_opt) ?></td>
                <td class="colDiskSpace"><?php
    if ($Storage->disk_total_space()) {
      echo intval(100*$Storage->disk_used_space()/$Storage->disk_total_space()).'% ';
    }
    echo human_filesize($Storage->disk_used_space()) . ' of ' . human_filesize($Storage->disk_total_space()) ?></td>
                <td class="ColEvents"><?php echo makeLink('?view=events'.$filter->querystring(), $Storage->EventCount().' using '.human_filesize($Storage->event_disk_space() ? $Storage->event_disk_space() : 0) ); ?></td>
                <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $Storage->Id() ?>" data-on-click-this="configureDeleteButton"
<?php
    echo ($Storage->EventCount() or !$canEdit) ? ' disabled="disabled"' : '';
    echo $Storage->EventCount() ? ' title="Can\'t delete as long as there are events stored here."' : '';
?>
                /></td>
              </tr>
<?php
  } #end foreach Server
?>
            </tbody>
          </table>
        </div><!-- .col -->
      </div> <!-- .row -->
    </div> <!-- .wrapper-scroll-table -->
  </div> <!-- .options -->
</form>
