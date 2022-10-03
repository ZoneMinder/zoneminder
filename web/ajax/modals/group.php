<?php
// This is the HTML representing the Group modal accessed from the Groups (plural) view

//
// DEFINE SUPPORTING FUNCTIONS
//

function get_Id( $G ) {
  return $G->Id();
}

function get_children($Group) {
  global $children;

  $kids = array();
  if ( isset( $children[$Group->Id()] ) ) {
    $kids += array_map('get_Id', $children[$Group->Id()]);
    foreach ( $children[$Group->Id()] as $G ) {
      foreach ( get_children($G) as $id ) {
        $kids[] = $id;
      }
    }
  }
  return $kids;
}

function parentGrpSelect($newGroup) {
  $Groups = array();
  foreach ( ZM\Group::find() as $Group ) {
    $Groups[$Group->Id()] = $Group;
  }

  # This  array is indexed by parent_id
  $children = array();

  foreach ( $Groups as $id=>$Group ) {
    if ( $Group->ParentId() != null ) {
      if ( ! isset( $children[$Group->ParentId()] ) )
        $children[$Group->ParentId()] = array();
      $children[$Group->ParentId()][] = $Group;
    }
  }

  $kids = get_children($newGroup);
  if ( $newGroup->Id() )
    $kids[] = $newGroup->Id();
  $sql = 'SELECT Id,Name FROM `Groups`'.(count($kids)?' WHERE Id NOT IN ('.implode(',',array_map(function(){return '?';}, $kids)).')' : '').' ORDER BY Name';
  $options = array(''=>'None');

  foreach ( dbFetchAll($sql, null, $kids) as $option ) {
    $options[$option['Id']] = str_repeat('&nbsp;&nbsp;', $Groups[$option['Id']]->depth()) . $option['Name'];
  }

  return htmlSelect('newGroup[ParentId]', $options, $newGroup->ParentId(), array('data-on-change'=>'configModalBtns'));
}

function monitorList($newGroup) {
  $result = '';

  $monitors = dbFetchAll('SELECT Id,Name FROM Monitors ORDER BY Sequence ASC');
  $monitorIds = $newGroup->MonitorIds();
  foreach ( $monitors as $monitor ) {
    if ( visibleMonitor($monitor['Id']) ) {
      $result .= '<option value="' .$monitor['Id']. '"' .( in_array( $monitor['Id'], $monitorIds ) ? ' selected="selected"' : ''). '>' .validHtmlStr($monitor['Name']). '</option>'.PHP_EOL;
    }
  }
  
  return $result;
}

//
// INITIAL SANITY CHECKS
//

if ( !canEdit('Groups') ) {
  $view = 'error';
  return;
}

if ( !empty($_REQUEST['gid']) ) {
  $newGroup = new ZM\Group($_REQUEST['gid']);
} else {
  $newGroup = new ZM\Group();
}

//
// BEGIN HTML
//
?>
<div id="groupModal" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo translate('Group').($newGroup->Name() ? ' - ' .validHtmlStr($newGroup->Name()) : '') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="groupForm" name="groupForm" method="post" action="?view=group">
        <?php
        // We have to manually insert the csrf key into the form when using a modal generated via ajax call
        echo getCSRFinputHTML();
        ?>
        <input type="hidden" name="gid" value="<?php echo $newGroup->Id() ?>"/>
        <div class="modal-body">
          <table id="groupModalTable" class="table-sm table-borderless">
            <tbody>
              <tr>
                <th class="text-right pr-3" scope="row"><?php echo translate('Name') ?></th>
                <td><input type="text" name="newGroup[Name]" value="<?php echo validHtmlStr($newGroup->Name()) ?>" data-on-input="configModalBtns"/></td>
              </tr>
              <tr>
                <th class="text-right pr-3" scope="row"><?php echo translate('ParentGroup') ?></th>
                <td><?php echo parentGrpSelect($newGroup) ?></td>
              </tr>
              <tr>
                <th class="text-right pr-3" scope="row"><?php echo translate('Monitor') ?></th>
                <td>
                  <select name="newGroup[MonitorIds][]" class="chosen" multiple="multiple" data-on-change="configModalBtns">
                    <?php echo monitorList($newGroup) ?>
                  </select>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" name="action" value="save" id="groupModalSaveBtn"<?php $newGroup->Id() ? '' : ' disabled="disabled"'?>><?php echo translate('Save') ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
