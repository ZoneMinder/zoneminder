<?php

if ( !canEdit( 'Devices' ) ) return;

if ( !empty($_REQUEST['did']) ) {
    $newDevice = dbFetchOne( 'SELECT * FROM Devices WHERE Id = ?', NULL, array($_REQUEST['did']) );
} else {
    $newDevice = array(
        "Id" => "",
        "Name" => "New Device",
        "KeyString" => ""
    );
}

?>
<div class="modal fade" id="deviceModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="staticBackdropLabel"><?php echo translate('Device')." - ".validHtmlStr($newDevice['Name']) ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="deviceModalForm" name="contentForm" method="post" action="?view=device">
 
      <div class="modal-body">
        <?php
        // We have to manually insert the csrf key into the form when using a modal generated via ajax call
        echo getCSRFinputHTML();
        ?>
        <input type="hidden" name="view" value="device"/>
        <input type="hidden" name="action" value="device"/>
        <input type="hidden" name="did" value="<?php echo $newDevice['Id'] ?>"/>
        <table class="table-sm table-borderless" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row" class="text-right pr-2"><?php echo translate('Name') ?></th>
              <td><input type="text" name="newDevice[Name]" value="<?php echo validHtmlStr($newDevice['Name']) ?>"/></td>
            </tr>
            <tr>
              <th scope="row" class="text-right pr-2"><?php echo translate('KeyString') ?></th>
              <td><input type="text" name="newDevice[KeyString]" value="<?php echo validHtmlStr($newDevice['KeyString']) ?>"/></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="submit" id="deviceSaveBtn" class="btn btn-primary"><?php echo translate('Save') ?></button>        
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </form>
    </div>
  </div>
</div>
