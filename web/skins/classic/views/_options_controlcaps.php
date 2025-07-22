<?php
$controls = dbFetchAll('SELECT * FROM Controls ORDER BY Name');
?>
<div class="row">
  <div class="col">
    <div id="contentButtons">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      <button id="addNewBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('AddNewControl') ?>" data-on-click-this="addNewControl" data-url="?view=controlcap"><i class="fa fa-plus"></i></button>
      <button id="editBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('EditControl') ?>" data-on-click-this="editControl" data-url="?view=controlcap&cid=" disabled><i class="fa fa-pencil"></i></button>
      <button id="deleteBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>" disabled><i class="fa fa-trash"></i></button>
    </div>
  </div> <!-- .col -->
</div> <!-- .row -->
<div class="wrapper-scroll-table">
  <div class="row">
    <div class="col">
      <table id="controlTable" class="table-sm table-borderless"
        data-locale="<?php echo i18n() ?>"
        data-search="true"
        data-cookie="true"
        data-cookie-id-table="zmControlTable"
        data-cookie-expire="2y"
        data-remember-order="true"
        data-click-to-select="true"
        data-maintain-meta-data="true"
        data-buttons-class="btn btn-normal"
        data-toolbar="#toolbar"
        data-show-columns="true"
      >
        <thead>
          <tr>
            <th data-sortable="false" data-field="toggleCheck" data-checkbox="true"></th>
            <th class="colId" data-sortable="true" data-field="Id"><?php echo translate('Id') ?></th>
            <th class="colName" data-sortable="true" data-field="Name"><?php echo translate('Name') ?></th>
            <th class="colType" data-sortable="true" data-field="Type"><?php echo translate('Type') ?></th>
            <th class="colProtocol" data-sortable="true" data-field="Protocol"><?php echo translate('Protocol') ?></th>
            <th class="colCanMove" data-sortable="true" data-field="CanMove"><?php echo translate('CanMove') ?></th>
            <th class="colCanZoom" data-sortable="true" data-field="CanZoom"><?php echo translate('CanZoom') ?></th>
            <th class="colCanFocus" data-sortable="true" data-field="CanFocus"><?php echo translate('CanFocus') ?></th>
            <th class="colCanIris" data-sortable="true" data-field="CanIris"><?php echo translate('CanIris') ?></th>
            <th class="colCanWhiteBal" data-sortable="true" data-field="CanWhiteBal"><?php echo translate('CanWhiteBal') ?></th>
            <th class="colHasPresets" data-sortable="true" data-field="HasPresets"><?php echo translate('HasPresets') ?></th>
          </tr>
        </thead>
        <tbody>
<?php
foreach( $controls as $control ) {
?>
          <tr>
            <td class="colMark" data-checkbox="true"></td>
            <td class="colId"><?php echo $control['Id'] ?></td>
            <td class="colName"><?php echo $control['Name'] ?></td>
            <td class="colType"><?php echo $control['Type'] ?></td>
            <td class="colProtocol"><?php echo validHtmlStr($control['Protocol']) ?></td>
            <td class="colCanMove"><?php echo $control['CanMove']?translate('Yes'):translate('No') ?></td>
            <td class="colCanZoom"><?php echo $control['CanZoom']?translate('Yes'):translate('No') ?></td>
            <td class="colCanFocus"><?php echo $control['CanFocus']?translate('Yes'):translate('No') ?></td>
            <td class="colCanIris"><?php echo $control['CanIris']?translate('Yes'):translate('No') ?></td>
            <td class="colCanWhiteBal"><?php echo $control['CanWhite']?translate('Yes'):translate('No') ?></td>
            <td class="colHasPresets"><?php echo $control['HasHomePreset']?'H':'' ?><?php echo $control['HasPresets']?$control['NumPresets']:'0' ?></td>
          </tr>
<?php
}
?>
        </tbody>
      </table>
    </div> <!-- .col -->
  </div> <!-- .row -->
</div> <!-- .wrapper-scroll-table -->
<script src="skins/<?php echo $skin ?>/views/js/controlcaps.js" nonce="<?php echo $cspNonce ?>"></script>
