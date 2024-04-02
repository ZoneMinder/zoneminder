<?php
// This is the HTML representing the Server modal from Options -> Server
if ( !isset($_REQUEST['id']) ) {
  ajaxError('Server Id Not Provided');
  return;
}

$result = '';
$checked = ' checked="checked"';
$null = '';
$sid = validCardinal($_REQUEST['id']);

if ( !canEdit('System') ) return;

$Server = new ZM\Server($sid);
if ( $sid and ! $Server->Id() ) return;

?>
<div class="modal fade" id="ServerModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Server') .' - '. $Server->Name() ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="serverModalForm" name="contentForm" method="post" action="?view=server" class="validateFormOnSubmit">
        <div class="modal-body">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          ?>
          <input type="hidden" name="object" value="server"/>
          <input type="hidden" name="id" value="<?php echo $sid ?>"/>
          <table class="table-sm">
            <tbody>
              <tr class="Name">
                <th scope="row"><?php echo translate('Name') ?></th>
                <td><input type="text" name="newServer[Name]" value="<?php echo validHtmlStr($Server->Name()) ?>"/></td>
              </tr>
              <tr class="Protocol">
                <th scope="row"><?php echo translate('Protocol') ?></th>
                <td><input type="text" name="newServer[Protocol]" value="<?php echo validHtmlStr($Server->Protocol()) ?>"/></td>
              </tr>
              <tr class="Hostname">
                <th scope="row"><?php echo translate('Hostname') ?></th>
                <td><input type="text" name="newServer[Hostname]" value="<?php echo validHtmlStr($Server->Hostname()) ?>"/></td>
              </tr>
              <tr class="Port">
                <th scope="row"><?php echo translate('Port') ?></th>
                <td><input type="number" name="newServer[Port]" value="<?php echo validCardinal($Server->Port()) ?>" step="1" min="1" max="65536"/></td>
              </tr>
              <tr class="PathToIndex">
                <th scope="row"><?php echo translate('PathToIndex') ?></th>
                <td><input type="text" name="newServer[PathToIndex]" value="<?php echo validHtmlStr($Server->PathToIndex()) ?>"/></td>
              </tr>
              <tr class="PathToZMS">
                <th scope="row"><?php echo translate('PathToZMS') ?></th>
                <td><input type="text" name="newServer[PathToZMS]" value="<?php echo validHtmlStr($Server->PathToZMS()) ?>"/></td>
              </tr>
              <tr class="PathToApi">
                <th scope="row"><?php echo translate('PathToApi') ?></th>
                <td><input type="text" name="newServer[PathToApi]" value="<?php echo validHtmlStr($Server->PathToApi()) ?>"/></td>
              </tr>
              <tr class="RunStats">
                <th scope="row"><?php echo translate('RunStats') ?></th>
                <td>
                  <input type="radio" name="newServer[zmstats]" value="1" <?php echo $Server->zmstats() ? $checked : $null ?>/> Yes
                  <input type="radio" name="newServer[zmstats]" value="0" <?php echo $Server->zmstats() ? $null : $checked ?>/> No
                </td>
              </tr>
              <tr class="RunAudit">
                <th scope="row"><?php echo translate('RunAudit') ?></th>
                <td>
                  <input type="radio" name="newServer[zmaudit]" value="1"<?php echo $Server->zmaudit() ? $checked : $null ?>/> Yes
                  <input type="radio" name="newServer[zmaudit]" value="0"<?php echo $Server->zmaudit() ? $null : $checked ?>/> No
                </td>
              </tr>
              <tr class="RunTrigger">
                <th scope="row"><?php echo translate('RunTrigger') ?></th>
                <td>
                  <input type="radio" name="newServer[zmtrigger]" value="1" <?php echo $Server->zmtrigger() ? $checked : $null ?>/> Yes
                  <input type="radio" name="newServer[zmtrigger]" value="0" <?php echo $Server->zmtrigger() ? $null : $checked ?>/> No
                </td>
              </tr>
              <tr class="RunEventNotification">
                <th scope="row"><?php echo translate('RunEventNotification') ?></th>
                <td>
                  <input type="radio" name="newServer[zmeventnotification]" value="1" <?php echo $Server->zmeventnotification() ? $checked : $null ?>/> Yes
                  <input type="radio" name="newServer[zmeventnotification]" value="0" <?php echo $Server->zmeventnotification() ? $null : $checked ?>/> No
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button name="action" id="serverSubmitBtn" type="submit" class="btn btn-primary" value="save"><?php echo translate('Save') ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

