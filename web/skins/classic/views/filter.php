<?php
//
// ZoneMinder web filter view file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if ( !canView( 'Events' ) ) {
  $view = 'error';
  return;
}
$selectName = 'filterName';
$filterNames = array( ''=>translate('ChooseFilter') );
foreach ( dbFetchAll( 'select * from Filters order by Name' ) as $row ) {
  $filterNames[$row['Name']] = $row['Name'];
  if ( $row['Background'] )
    $filterNames[$row['Name']] .= '*';
  if ( !empty($_REQUEST['reload']) && isset($_REQUEST['filterName']) && $_REQUEST['filterName'] == $row['Name'] )
    $dbFilter = $row;
}

$backgroundStr = '';
if ( isset($dbFilter) ) {
  if ( $dbFilter['Background'] ) 
    $backgroundStr = '['.strtolower(translate('Background')).']';
  $_REQUEST['filter'] = jsonDecode( $dbFilter['Query'] );
  $_REQUEST['sort_field'] = isset($_REQUEST['filter']['sort_field'])?$_REQUEST['filter']['sort_field']:"DateTime";
  $_REQUEST['sort_asc'] = isset($_REQUEST['filter']['sort_asc'])?$_REQUEST['filter']['sort_asc']:"1";
  $_REQUEST['limit'] = isset($_REQUEST['filter']['limit'])?$_REQUEST['filter']['limit']:"";
  unset( $_REQUEST['filter']['sort_field'] );
  unset( $_REQUEST['filter']['sort_asc'] );
  unset( $_REQUEST['filter']['limit'] );
}

# reload is set when the dropdown is changed. 
if ( isset( $_REQUEST['reload'] ) and ! $_REQUEST['reload'] ) {
  $dbFilter['AutoArchive'] = isset( $_REQUEST['AutoArchive'] );
  $dbFilter['AutoExecute'] = isset( $_REQUEST['AutoExecute'] );
  $dbFilter['AutoExecuteCmd'] = $_REQUEST['AutoExecuteCmd'];
  $dbFilter['AutoEmail'] = isset( $_REQUEST['AutoEmail'] );
  $dbFilter['AutoMessage'] = isset( $_REQUEST['AutoMessage'] );
  $dbFilter['AutoUpload'] = isset( $_REQUEST['AutoUpload'] );
  $dbFilter['AutoVideo'] = isset( $_REQUEST['AutoVideo'] );
  $dbFilter['AutoDelete'] = isset( $_REQUEST['AutoDelete'] );
  $dbFilter['Name'] = $_REQUEST['filterName'];
}

$conjunctionTypes = array(
    'and' => translate('ConjAnd'),
    'or'  => translate('ConjOr')
    );
$obracketTypes = array(); 
$cbracketTypes = array();
if ( isset($_REQUEST['filter']['terms']) ) {
  for ( $i = 0; $i <= count($_REQUEST['filter']['terms'])-2; $i++ ) {
    $obracketTypes[$i] = str_repeat( "(", $i );
    $cbracketTypes[$i] = str_repeat( ")", $i );
  }
}

$attrTypes = array(
    'MonitorId'   => translate('AttrMonitorId'),
    'MonitorName' => translate('AttrMonitorName'),
    'Id'          => translate('AttrId'),
    'Name'        => translate('AttrName'),
    'Cause'       => translate('AttrCause'),
    'Notes'       => translate('AttrNotes'),
    'DateTime'    => translate('AttrDateTime'),
    'Date'        => translate('AttrDate'),
    'Time'        => translate('AttrTime'),
    'Weekday'     => translate('AttrWeekday'),
    'Length'      => translate('AttrDuration'),
    'Frames'      => translate('AttrFrames'),
    'AlarmFrames' => translate('AttrAlarmFrames'),
    'TotScore'    => translate('AttrTotalScore'),
    'AvgScore'    => translate('AttrAvgScore'),
    'MaxScore'    => translate('AttrMaxScore'),
    'Archived'    => translate('AttrArchiveStatus'),
    'DiskPercent' => translate('AttrDiskPercent'),
    'DiskBlocks'  => translate('AttrDiskBlocks'),
    'SystemLoad'  => translate('AttrSystemLoad'),
    'StateId'     => translate('AttrStateId'),
    'ServerId'    => translate('AttrServer'),
    );
$opTypes = array(
    '='   => translate('OpEq'),
    '!='  => translate('OpNe'),
    '>='  => translate('OpGtEq'),
    '>'   => translate('OpGt'),
    '<'   => translate('OpLt'),
    '<='  => translate('OpLtEq'),
    '=~'  => translate('OpMatches'),
    '!~'  => translate('OpNotMatches'),
    '=[]' => translate('OpIn'),
    '![]' => translate('OpNotIn'),
    );
$archiveTypes = array(
    '0' => translate('ArchUnarchived'),
    '1' => translate('ArchArchived')
    );
$weekdays = array();
for ( $i = 0; $i < 7; $i++ ) {
  $weekdays[$i] = strftime( '%A', mktime( 12, 0, 0, 1, $i+1, 2001 ) );
}
$sort_fields = array(
    'Id'          => translate('AttrId'),
    'Name'        => translate('AttrName'),
    'Cause'       => translate('AttrCause'),
    'Notes'       => translate('AttrNotes'),
    'MonitorName' => translate('AttrMonitorName'),
    'DateTime'    => translate('AttrDateTime'),
    'Length'      => translate('AttrDuration'),
    'Frames'      => translate('AttrFrames'),
    'AlarmFrames' => translate('AttrAlarmFrames'),
    'TotScore'    => translate('AttrTotalScore'),
    'AvgScore'    => translate('AttrAvgScore'),
    'MaxScore'    => translate('AttrMaxScore'),
    );
$sort_dirns = array(
    '1' => translate('SortAsc'),
    '0'  => translate('SortDesc')
    );
if ( empty($_REQUEST['sort_field']) ) {
    $_REQUEST['sort_field'] = ZM_WEB_EVENT_SORT_FIELD; 
    $_REQUEST['sort_asc'] = (ZM_WEB_EVENT_SORT_ORDER == "asc");
}

$hasCal = file_exists( 'tools/jscalendar/calendar.js' );

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('EventFilter') );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow();"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('EventFilter') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="filter"/>
        <input type="hidden" name="page" value="<?php echo requestVar( 'page' ) ?>"/>
        <input type="hidden" name="reload" value="0"/>
        <input type="hidden" name="execute" value="0"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="subaction" value=""/>
        <input type="hidden" name="line" value=""/>
        <input type="hidden" name="fid" value=""/>
        <hr/>
        <div id="filterSelector"><label for="<?php echo $selectName ?>"><?php echo translate('UseFilter') ?></label><?php if ( count($filterNames) > 1 ) { echo buildSelect( $selectName, $filterNames, "submitToFilter( this, 1 );" ); } else { ?><select disabled="disabled"><option><?php echo translate('NoSavedFilters') ?></option></select><?php } ?><?php echo $backgroundStr ?></div>
        <hr/>
        <table id="fieldsTable" class="filterTable" cellspacing="0">
          <tbody>
<?php
for ( $i = 0; isset($_REQUEST['filter']) && $i < count($_REQUEST['filter']['terms']); $i++ ) {
?>
            <tr>
<?php
  if ( $i == 0 ) {
?>
              <td>&nbsp;</td>
<?php
  } else {
?>
              <td><?php echo buildSelect( "filter[terms][$i][cnj]", $conjunctionTypes ); ?></td>
<?php
  }
?>
              <td><?php if ( count($_REQUEST['filter']['terms']) > 2 ) { echo buildSelect( "filter[terms][$i][obr]", $obracketTypes ); } else { ?>&nbsp;<?php } ?></td>
              <td><?php echo buildSelect( "filter[terms][$i][attr]", $attrTypes, "clearValue( this, $i ); submitToFilter( this, 0 );" ); ?></td>
<?php
  if ( isset($_REQUEST['filter']['terms'][$i]['attr']) ) {
    if ( $_REQUEST['filter']['terms'][$i]['attr'] == 'Archived' ) {
?>
              <td><?php echo translate('OpEq') ?><input type="hidden" name="filter[terms][<?php echo $i ?>][op]" value="="/></td>
              <td><?php echo buildSelect( "filter[terms][$i][val]", $archiveTypes ); ?></td>
<?php
    } elseif ( $_REQUEST['filter']['terms'][$i]['attr'] == 'DateTime' ) {
?>
              <td><?php echo buildSelect( "filter[terms][$i][op]", $opTypes ); ?></td>
              <td>
                <input name="filter[terms][<?php echo $i ?>][val]" id="filter[terms][<?php echo $i ?>][val]" value="<?php echo isset($_REQUEST['filter']['terms'][$i]['val'])?validHtmlStr($_REQUEST['filter']['terms'][$i]['val']):'' ?>"/>
<?php if ( $hasCal ) { ?>
                <script type="text/javascript">Calendar.setup( { inputField: "filter[terms][<?php echo $i ?>][val]", ifFormat: "%Y-%m-%d %H:%M", showsTime: true, timeFormat: "24", showOthers: true, weekNumbers: false });</script>
<?php } ?>
              </td>
<?php
    } elseif ( $_REQUEST['filter']['terms'][$i]['attr'] == 'Date' ) {
?>
              <td><?php echo buildSelect( "filter[terms][$i][op]", $opTypes ); ?></td>
              <td>
                <input name="filter[terms][<?php echo $i ?>][val]" id="filter[terms][<?php echo $i ?>][val]" value="<?php echo isset($_REQUEST['filter']['terms'][$i]['val'])?validHtmlStr($_REQUEST['filter']['terms'][$i]['val']):'' ?>"/>
<?php if ( $hasCal ) { ?>
                <script type="text/javascript">Calendar.setup( { inputField: "filter[terms][<?php echo $i ?>][val]", ifFormat: "%Y-%m-%d", showOthers: true, weekNumbers: false });</script>
<?php } ?>
              </td>
<?php
    } elseif ( $_REQUEST['filter']['terms'][$i]['attr'] == 'StateId' ) {
      $states = array();
      foreach ( dbFetchAll( 'SELECT Id,Name FROM States ORDER BY lower(Name) ASC' ) as $state_row ) {
        $states[$state_row['Id']] = $state_row['Name'];
      }
?>
              <td><?php echo buildSelect( "filter[terms][$i][op]", $opTypes ); ?></td>
              <td><?php echo buildSelect( "filter[terms][$i][val]", $states ); ?></td>
<?php
    } elseif ( $_REQUEST['filter']['terms'][$i]['attr'] == 'Weekday' ) {
?>
              <td><?php echo buildSelect( "filter[terms][$i][op]", $opTypes ); ?></td>
              <td><?php echo buildSelect( "filter[terms][$i][val]", $weekdays ); ?></td>
<?php
    } elseif ( false && $_REQUEST['filter']['terms'][$i]['attr'] == 'MonitorName' ) {
      $monitors = array();
      foreach ( dbFetchAll( "select Id,Name from Monitors order by Sequence asc" ) as $monitor ) {
        if ( visibleMonitor( $monitor['Id'] ) ) {
          $monitors[$monitor['Name']] = $monitor['Name'];
        }
      }
?>
              <td><?php echo buildSelect( "filter[terms][$i][op]", $opTypes ); ?></td>
              <td><?php echo buildSelect( "filter[terms][$i][val]", $monitors ); ?></td>
<?php
    } elseif ( $_REQUEST['filter']['terms'][$i]['attr'] == 'ServerId' ) {
      $servers = array();
      $servers['ZM_SERVER_ID'] = 'Current Server';
      foreach ( dbFetchAll( 'SELECT Id,Name FROM Servers ORDER BY lower(Name) ASC' ) as $server ) {
        $servers[$server['Id']] = $server['Name'];
      }
?>
            <td><?php echo buildSelect( "filter[terms][$i][op]", $opTypes ); ?></td>
            <td><?php echo buildSelect( "filter[terms][$i][val]", $servers ); ?></td>
<?php
    } else {
?>
              <td><?php echo buildSelect( "filter[terms][$i][op]", $opTypes ); ?></td>
              <td><input name="filter[terms][<?php echo $i ?>][val]" value="<?php echo $_REQUEST['filter']['terms'][$i]['val'] ?>"/></td>
<?php
    }
  } else {
?>
              <td><?php echo buildSelect( "filter[terms][$i][op]", $opTypes ); ?></td>
              <td><input name="filter[terms][<?php echo $i ?>][val]" value="<?php echo isset($_REQUEST['filter']['terms'][$i]['val'])?$_REQUEST['filter']['terms'][$i]['val']:'' ?>"/></td>
<?php
  }
?>
              <td><?php if ( count($_REQUEST['filter']['terms']) > 2 ) { echo buildSelect( "filter[terms][$i][cbr]", $cbracketTypes ); } else { ?>&nbsp;<?php } ?></td>
              <td><input type="button" onclick="addTerm( this, <?php echo $i+1 ?> )" value="+"/><?php if ( $_REQUEST['filter']['terms'] > 1 ) { ?><input type="button" onclick="delTerm( this, <?php echo $i ?> )" value="-"/><?php } ?></td>
            </tr>
<?php
} # end foreach filter
?>
          </tbody>
        </table>
        <hr/>
        <table id="sortTable" class="filterTable" cellspacing="0">
          <tbody>
            <tr>
              <td><label for="sort_field"><?php echo translate('SortBy') ?></label><?php echo buildSelect( "sort_field", $sort_fields ); ?><?php echo buildSelect( "sort_asc", $sort_dirns ); ?></td>
              <td><label for="limit"><?php echo translate('LimitResultsPre') ?></label><input type="text" size="6" id="limit" name="limit" value="<?php echo isset($_REQUEST['limit'])?validInt($_REQUEST['limit']):"" ?>"/><?php echo translate('LimitResultsPost') ?></td>
            </tr>
          </tbody>
        </table>
        <hr/>
        <table id="actionsTable" class="filterTable" cellspacing="0">
          <tbody>
            <tr>
              <td><?php echo translate('FilterArchiveEvents') ?></td>
              <td><input type="checkbox" name="AutoArchive" value="1"<?php if ( !empty($dbFilter['AutoArchive']) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/></td>
            </tr>
<?php
if ( ZM_OPT_FFMPEG )
{
?>
            <tr>
              <td><?php echo translate('FilterVideoEvents') ?></td>
              <td><input type="checkbox" name="AutoVideo" value="1"<?php if ( !empty($dbFilter['AutoVideo']) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/></td>
            </tr>
<?php
}
if ( ZM_OPT_UPLOAD )
{
?>
            <tr>
              <td><?php echo translate('FilterUploadEvents') ?></td>
              <td><input type="checkbox" name="AutoUpload" value="1"<?php if ( !empty($dbFilter['AutoUpload']) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/></td>
            </tr>
<?php
}
if ( ZM_OPT_EMAIL )
{
?>
            <tr>
              <td><?php echo translate('FilterEmailEvents') ?></td>
              <td><input type="checkbox" name="AutoEmail" value="1"<?php if ( !empty($dbFilter['AutoEmail']) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/></td>
            </tr>
<?php
}
if ( ZM_OPT_MESSAGE )
{
?>
            <tr>
              <td><?php echo translate('FilterMessageEvents') ?></td>
              <td><input type="checkbox" name="AutoMessage" value="1"<?php if ( !empty($dbFilter['AutoMessage']) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/></td>
            </tr>
<?php
}
?>
            <tr>
              <td><?php echo translate('FilterExecuteEvents') ?></td>
              <td><input type="checkbox" name="AutoExecute" value="1"<?php if ( !empty($dbFilter['AutoExecute']) ) { ?> checked="checked"<?php } ?>/><input type="text" name="AutoExecuteCmd" value="<?php echo isset($dbFilter['AutoExecuteCmd'])?$dbFilter['AutoExecuteCmd']:"" ?>" size="32" maxlength="255" onchange="updateButtons( this )"/></td>
            </tr>
            <tr>
              <td><?php echo translate('FilterDeleteEvents') ?></td>
              <td colspan="2"><input type="checkbox" name="AutoDelete" value="1"<?php if ( !empty($dbFilter['AutoDelete']) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/></td>
            </tr>
          </tbody>
        </table>
        <hr/>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Submit') ?>" onclick="submitToEvents( this );"/>
          <input type="button" name="executeButton" id="executeButton" value="<?php echo translate('Execute') ?>" onclick="executeFilter( this );"/>
<?php if ( canEdit( 'Events' ) ) { ?>
          <input type="button" value="<?php echo translate('Save') ?>" onclick="saveFilter( this );"/>
<?php if ( isset($dbFilter) && $dbFilter['Name'] ) { ?>
          <input type="button" value="<?php echo translate('Delete') ?>" onclick="deleteFilter( this, '<?php echo $dbFilter['Name'] ?>' );"/>
<?php } ?>
<?php } ?>
          <input type="button" value="<?php echo translate('Reset') ?>" onclick="submitToFilter( this, 1 );"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
