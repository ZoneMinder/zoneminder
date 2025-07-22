<?php
if ( !canEdit('System') ) return;
?>
<div class="modal fade" id="ImportMonitorsModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Import Monitors FROM CSV') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="importModalForm" name="contentForm" method="post" action="?view=add_monitors" class="validateFormOnSubmit">
        <div class="modal-body">
          <?php
          // We have to manually insert the csrf key into the form when using a modal generated via ajax call
          echo getCSRFinputHTML();
          ?>
          <fieldset><legend>Import CSV Spreadsheet</legend>
              Spreadsheet should have the following format:<br/>
              <table class="major">
                <tr>
                  <th>Name</th>
                  <th>URL</th>
                  <th>Group</th>
                </tr>
                <tr title="Example Data">
                  <td>Example Name Driveway</td>
                  <td>http://192.168.1.0/?action=stream</td>
                  <td>MN1</td>
                </tr>
              </table>
            </fieldset>
        </div>
        <div class="modal-footer">
          <input type="file" name="import_file" id="import_file"/>
          <button name="action" id="importSubmitBtn" type="submit" class="btn btn-primary" value="save"><?php echo translate('Save') ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
