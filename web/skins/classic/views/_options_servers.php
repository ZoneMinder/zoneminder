<form name="serversForm" method="post" action="?">
  <div id="options">
    <div class="row">
      <input type="hidden" name="view" value="<?php echo $view ?>"/>
      <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
      <input type="hidden" name="action" value="delete"/>
      <input type="hidden" name="object" value="server"/>
      <div class="col">
        <div id="contentButtons">
          <button type="button" id="NewServerBtn" value="<?php echo translate('AddNewServer') ?>" disabled="disabled"><?php echo translate('AddNewServer') ?></button>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </div> <!-- .col -->
    </div> <!-- .row -->
    <div class="wrapper-scroll-table">
      <div class="row">
        <div class="col">
          <table id="contentTable" class="table table-striped"
              data-click-to-select="true"
              data-check-on-init="true"
              data-mobile-responsive="true"
              data-min-width="562"
              data-show-export="true"
              data-show-columns="true"
              data-uncheckAll="true"
              data-cookie="true"
              data-cookie-same-site="Strict"
              data-cookie-id-table="zmServersTable"
              data-cookie-expire="2y"
              data-remember-order="false"
          >
            <thead class="thead-highlight">
              <tr>
                <th class="colMark"><?php echo translate('Mark') ?></th>
                <th data-sortable="true" class="colId"><?php echo translate('Id') ?></th>
                <th data-sortable="true" class="colName"><?php echo translate('Name') ?></th>
                <th data-sortable="true" class="colUrl"><?php echo translate('Url') ?></th>
                <th data-sortable="true" class="colPathToIndex"><?php echo translate('PathToIndex') ?></th>
                <th data-sortable="true" class="colPathToZMS"><?php echo translate('PathToZMS') ?></th>
                <th data-sortable="true" class="colPathToAPI"><?php echo translate('PathToApi') ?></th>
                <th data-sortable="true" class="colStatus"><?php echo translate('Status') ?></th>
                <th data-sortable="true" class="colMonitorCount"><?php echo translate('Monitors') ?></th>
                <th data-sortable="true" class="colCpuLoad"><?php echo translate('CpuLoad') ?></th>
                <th data-sortable="true" class="colMemory"><?php echo translate('Free').'/'.translate('Total') . ' ' . translate('Memory') ?></th>
                <th data-sortable="true" class="colSwap"><?php echo translate('Free').'/'.translate('Total') . ' ' . translate('Swap') ?></th>
                <th data-sortable="true" class="colStats"><?php echo translate('RunStats') ?></th>
                <th data-sortable="true" class="colAudit"><?php echo translate('RunAudit') ?></th>
                <th data-sortable="true" class="colTrigger"><?php echo translate('RunTrigger') ?></th>
               <th data-sortable="true" class="colEventNotification"><?php echo translate('RunEventNotification') ?></th>
              </tr>
            </thead>
            <tbody>
<?php
$monitor_counts = dbFetchAssoc('SELECT Id,(SELECT COUNT(Id) FROM Monitors WHERE Deleted!=1 AND ServerId=Servers.Id) AS MonitorCount FROM Servers', 'Id', 'MonitorCount');
foreach (ZM\Server::find() as $Server) {
  $svr_opt = 'class="serverCol" data-sid="'.$Server->Id().'"';
  $Server->ReadStats();
  ?>
              <tr>
                <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $Server->Id() ?>" data-on-click-this="configureDeleteButton"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
                <td class="colId"><?php echo makeLink('#', $Server->Id(), $canEdit, $svr_opt ) ?></td>
                <td class="colName"><?php echo makeLink('#', validHtmlStr($Server->Name()), $canEdit, $svr_opt ) ?></td>
                <td class="colUrl"><?php echo makeLink('#', validHtmlStr($Server->Url()), $canEdit, $svr_opt ) ?></td>
                <td class="colPathToIndex"><?php echo makeLink('#', validHtmlStr($Server->PathToIndex()), $canEdit, $svr_opt ) ?></td>
                <td class="colPathToZMS"><?php echo makeLink('#', validHtmlStr($Server->PathToZMS()), $canEdit, $svr_opt ) ?></td>
                <td class="colPathToAPI"><?php echo makeLink('#', validHtmlStr($Server->PathToAPI()), $canEdit, $svr_opt ) ?></td>
                <td class="colStatus <?php if ( $Server->Status() == 'NotRunning' ) { echo 'danger'; } ?>">
                    <?php echo makeLink('#', validHtmlStr($Server->Status()), $canEdit, $svr_opt) ?></td>
                <td class="colMonitorCount"><?php echo makeLink('#', validHtmlStr($monitor_counts[$Server->Id()]), $canEdit, $svr_opt) ?></td>
                <td class="colCpuLoad <?php if ( $Server->CpuLoad() > 5 ) { echo 'danger'; } ?>"><?php echo makeLink('#', $Server->CpuLoad(), $canEdit, $svr_opt) ?></td>
                <td class="colMemory <?php if ( (!$Server->TotalMem()) or ($Server->FreeMem()/$Server->TotalMem() < .1) ) { echo 'danger'; } ?>">
                    <?php echo makeLink('#', human_filesize($Server->FreeMem()) . ' / ' . human_filesize($Server->TotalMem()), $canEdit, $svr_opt) ?></td>
                <td class="colSwap <?php if ( (!$Server->TotalSwap()) or ($Server->FreeSwap()/$Server->TotalSwap() < .1) ) { echo 'danger'; } ?>">
                    <?php echo makeLink('#', human_filesize($Server->FreeSwap()) . ' / ' . human_filesize($Server->TotalSwap()) , $canEdit, $svr_opt) ?></td>
                <td class="colStats"><?php echo makeLink('#', $Server->zmstats() ? 'yes' : 'no', $canEdit, $svr_opt) ?></td>
                <td class="colAudit"><?php echo makeLink('#', $Server->zmaudit() ? 'yes' : 'no', $canEdit, $svr_opt) ?></td>
                <td class="colTrigger"><?php echo makeLink('#', $Server->zmtrigger() ? 'yes' : 'no', $canEdit, $svr_opt) ?></td>
                <td class="colEventNotification"><?php echo makeLink('#', $Server->zmeventnotification() ? 'yes' : 'no', $canEdit, $svr_opt) ?></td>
              </tr>
<?php } #end foreach Server ?>
           </tbody>
          </table>
        </div> <!-- .col -->
      </div> <!-- .row -->
    </div> <!-- .wrapper-scroll-table -->
  </div> <!-- .options -->
</form>
<script nonce="<?php echo $cspNonce ?>">
window.addEventListener("DOMContentLoaded",
 function() {
   $j('#contentTable').bootstrapTable({icons: icons}).show();
});
</script>
