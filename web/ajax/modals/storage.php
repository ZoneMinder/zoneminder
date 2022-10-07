<?php
// This is the HTML representing the Storage modal from Options -> Storage
  if ( !isset($_REQUEST['id']) ) {
    ajaxError('Storage Id Not Provided');
    return;
  }

  $null = '';
  $checked = 'checked="checked"';
  $sid = validInt($_REQUEST['id']);
  
  if (!canEdit('System')) return;

  require_once('includes/Server.php');
  require_once('includes/Storage.php');

  if ($_REQUEST['id']) {
    if (!($newStorage = ZM\Storage::find_one(array('Id'=>$sid)))) {
      // Perhaps do something different here, rather than return nothing
      return;
    }
  } else {
    $newStorage = new ZM\Storage();
    $newStorage->Name(translate('NewStorage'));
  }

  $type_options = array( 'local' => translate('Local'), 's3fs' => translate('s3fs') );
  $scheme_options = array(
    'Deep' => translate('Deep'),
    'Medium' => translate('Medium'),
    'Shallow' => translate('Shallow'),
  );

  global $Servers;
  $ServersById = array();
  foreach ( $Servers as $S ) {
    $ServersById[$S->Id()] = $S;
  }
?>
<div class="modal fade" id="storageModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Storage') .' - '. $newStorage->Name() ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="storageModalForm" name="contentForm" method="post" action="?view=storage&action=save" class="validateFormOnSubmit">
        <?php
        // We have to manually insert the csrf key into the form when using a modal generated via ajax call
        echo getCSRFinputHTML();
        ?>
        <input type="hidden" name="object" value="storage"/>
        <input type="hidden" name="id" value="<?php echo validHtmlStr($sid) ?>"/>
        <div class="modal-body">
<div class="table-responsive">
        <table class="major table table-sm">
          <tbody>
            <tr>
              <th class="text-right " scope="row"><?php echo translate('Name') ?></th>
              <td><input type="text" name="newStorage[Name]" value="<?php echo $newStorage->Name() ?>"/></td>
            </tr>
            <tr>
              <th class="text-right " scope="row"><?php echo translate('Path') ?></th>
              <td><input type="text" name="newStorage[Path]" value="<?php echo $newStorage->Path() ?>"/></td>
            </tr>
            <tr>
              <th class="text-right " scope="row"><?php echo translate('Url') ?></th>
              <td><input type="text" name="newStorage[Url]" value="<?php echo $newStorage->Url() ?>"/></td>
            </tr>
            <tr>
              <th class="text-right " scope="row"><?php echo translate('Server') ?></th>
              <td><?php echo htmlSelect('newStorage[ServerId]', array(''=>'Remote / No Specific Server') + $ServersById, $newStorage->ServerId()) ?></td>
            </tr>
            <tr>
              <th class="text-right " scope="row"><?php echo translate('Type') ?></th>
              <td><?php echo htmlSelect('newStorage[Type]', $type_options, $newStorage->Type()) ?></td>
            </tr>
            <tr>
              <th class="text-right " scope="row"><?php echo translate('StorageScheme') ?></th>
              <td><?php echo htmlSelect('newStorage[Scheme]', $scheme_options, $newStorage->Scheme()) ?></td>
            </tr>
            <tr>
              <th class="text-right " scope="row"><?php echo translate('StorageDoDelete') ?></th>
              <td>
                <input type="radio" name="newStorage[DoDelete]" id="newStorage[DoDelete]1" value="1" <?php echo $newStorage->DoDelete() ? $checked : $null ?>/>
                <label class="form-check-label" for="newStorage[DoDelete]1">Yes</label>
                <input type="radio" name="newStorage[DoDelete]" id="newStorage[DoDelete]0" value="0" <?php echo $newStorage->DoDelete() ? $null : $checked ?>/>
                <label class="form-check-label" for="newStorage[DoDelete]0">No</label>
              </td>
            </tr>
            <tr>
              <th class="text-right " scope="row"><?php echo translate('Enabled') ?></th>
              <td>
                <input type="radio" name="newStorage[Enabled]" id="newStorage[Enabled]1" value="1" <?php echo $newStorage->Enabled() ? $checked : $null ?>/>
                <label class="form-check-label" for="newStorage[Enabled]1">Yes</label>
                <input type="radio" name="newStorage[Enabled]" id="newStorage[Enabled]0" value="0" <?php echo $newStorage->Enabled() ? $null : $checked ?>/>
                <label class="form-check-label" for="newStorage[Enabled]0">No</label>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>
      <div class="modal-footer">
        <button name="action" id="storageSubmitBtn" type="submit" class="btn btn-primary" value="save"><?php echo translate('Save') ?></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
      </div>
    </form>
    </div>
  </div>
</div>

