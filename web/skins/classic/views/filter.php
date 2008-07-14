<?php
//
// ZoneMinder web filter view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

if ( !canView( 'Events' ) )
{
    $_REQUEST['view'] = "error";
    return;
}
$selectName = "filterName";
$filterNames = array( ''=>$SLANG['ChooseFilter'] );
foreach ( dbFetchAll( "select * from Filters order by Name" ) as $row )
{
    $filterNames[$row['Name']] = $row['Name'];
    if ( $row['Background'] )
        $filterNames[$row['Name']] .= "*";
    if ( !empty($_REQUEST['reload']) && isset($_REQUEST['filterName']) && $_REQUEST['filterName'] == $row['Name'] )
        $dbFilter = $row;
}

$backgroundStr = "";
if ( isset($dbFilter) )
{
    if ( $dbFilter['Background'] ) 
        $backgroundStr = '['.strtolower($SLANG['Background']).']';
    $_REQUEST['filter'] = unserialize( $dbFilter['Query'] );
    $_REQUEST['sort_field'] = isset($_REQUEST['filter']['sort_field'])?$_REQUEST['filter']['sort_field']:"DateTime";
    $_REQUEST['sort_asc'] = isset($_REQUEST['filter']['sort_asc'])?$_REQUEST['filter']['sort_asc']:"1";
    $_REQUEST['limit'] = isset($_REQUEST['filter']['limit'])?$_REQUEST['filter']['limit']:"";
    unset( $_REQUEST['filter']['sort_field'] );
    unset( $_REQUEST['filter']['sort_asc'] );
    unset( $_REQUEST['filter']['limit'] );
}

$conjunction_types = array(
    'and' => $SLANG['ConjAnd'],
    'or'  => $SLANG['ConjOr']
);
$obracket_types = array(); 
$cbracket_types = array();
if ( isset($_REQUEST['filter']['terms']) )
{
    for ( $i = 0; $i <= count($_REQUEST['filter']['terms'])-2; $i++ )
    {
        $obracket_types[$i] = str_repeat( "(", $i );
        $cbracket_types[$i] = str_repeat( ")", $i );
    }
}

$attr_types = array(
    'MonitorId'   => $SLANG['AttrMonitorId'],
    'MonitorName' => $SLANG['AttrMonitorName'],
    'Id'          => $SLANG['AttrId'],
    'Name'        => $SLANG['AttrName'],
    'Cause'       => $SLANG['AttrCause'],
    'Notes'       => $SLANG['AttrNotes'],
    'DateTime'    => $SLANG['AttrDateTime'],
    'Date'        => $SLANG['AttrDate'],
    'Time'        => $SLANG['AttrTime'],
    'Weekday'     => $SLANG['AttrWeekday'],
    'Length'      => $SLANG['AttrDuration'],
    'Frames'      => $SLANG['AttrFrames'],
    'AlarmFrames' => $SLANG['AttrAlarmFrames'],
    'TotScore'    => $SLANG['AttrTotalScore'],
    'AvgScore'    => $SLANG['AttrAvgScore'],
    'MaxScore'    => $SLANG['AttrMaxScore'],
    'Archived'    => $SLANG['AttrArchiveStatus'],
    'DiskPercent' => $SLANG['AttrDiskPercent'],
    'DiskBlocks'  => $SLANG['AttrDiskBlocks'],
    'SystemLoad'  => $SLANG['AttrSystemLoad'],
);
$op_types = array(
    '='   => $SLANG['OpEq'],
    '!='  => $SLANG['OpNe'],
    '>='  => $SLANG['OpGtEq'],
    '>'   => $SLANG['OpGt'],
    '<'   => $SLANG['OpLt'],
    '<='  => $SLANG['OpLtEq'],
    '=~'  => $SLANG['OpMatches'],
    '!~'  => $SLANG['OpNotMatches'],
    '=[]' => $SLANG['OpIn'],
    '![]' => $SLANG['OpNotIn'],
);
$archive_types = array(
    '0' => $SLANG['ArchUnarchived'],
    '1' => $SLANG['ArchArchived']
);
$weekdays = array();
for ( $i = 0; $i < 7; $i++ )
{
    $weekdays[$i] = strftime( "%A", mktime( 12, 0, 0, 1, $i+1, 2001 ) );
}
$sort_fields = array(
    'Id'          => $SLANG['AttrId'],
    'Name'        => $SLANG['AttrName'],
    'Cause'       => $SLANG['AttrCause'],
    'Notes'       => $SLANG['AttrNotes'],
    'MonitorName' => $SLANG['AttrMonitorName'],
    'DateTime'    => $SLANG['AttrDateTime'],
    'Length'      => $SLANG['AttrDuration'],
    'Frames'      => $SLANG['AttrFrames'],
    'AlarmFrames' => $SLANG['AttrAlarmFrames'],
    'TotScore'    => $SLANG['AttrTotalScore'],
    'AvgScore'    => $SLANG['AttrAvgScore'],
    'MaxScore'    => $SLANG['AttrMaxScore'],
);
$sort_dirns = array(
    '1' => $SLANG['SortAsc'],
    '0'  => $SLANG['SortDesc']
);
if ( empty($_REQUEST['sort_field']) )
{
    $_REQUEST['sort_field'] = ZM_WEB_EVENT_SORT_FIELD; 
    $_REQUEST['sort_asc'] = (ZM_WEB_EVENT_SORT_ORDER == "asc");
}

$hasCal = file_exists( 'calendar/calendar.js' );

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['EventFilter'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow();"><?= $SLANG['Close'] ?></a>
      </div>
      <h2><?= $SLANG['EventFilter'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="filter"/>
        <input type="hidden" name="page" value="<?= empty($_REQUEST['page'])?"":$_REQUEST['page'] ?>"/>
        <input type="hidden" name="reload" value="0"/>
        <input type="hidden" name="execute" value="0"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="subaction" value=""/>
        <input type="hidden" name="line" value=""/>
        <input type="hidden" name="fid" value=""/>
        <hr/>
        <div id="filterSelector"><label for="<?= $selectName ?>"><?= $SLANG['UseFilter'] ?></label><?php if ( count($filterNames) > 1 ) { echo buildSelect( $selectName, $filterNames, "submitToFilter( this, 1 );" ); } else { ?><select disabled="disabled"><option><?= $SLANG['NoSavedFilters'] ?></option></select><?php } ?><?= $backgroundStr ?></div>
        <hr/>
        <table id="fieldsTable" class="filterTable" cellspacing="0">
          <tbody>
<?php
for ( $i = 0; $i < count($_REQUEST['filter']['terms']); $i++ )
{
?>
            <tr>
<?php
    if ( $i == 0 )
    {
?>
              <td>&nbsp;</td>
<?php
    }
    else
    {
?>
              <td><?= buildSelect( "filter[terms][$i][cnj]", $conjunction_types ); ?></td>
<?php
    }
?>
              <td><?php if ( count($_REQUEST['filter']['terms']) > 2 ) { echo buildSelect( "filter[terms][$i][obr]", $obracket_types ); } else { ?>&nbsp;<?php } ?></td>
              <td><?= buildSelect( "filter[terms][$i][attr]", $attr_types, "clearValue( this, $i ); submitToFilter( this, 0 );" ); ?></td>
<?php
    if ( isset($_REQUEST['filter']['terms'][$i]['attr']) )
    {
        if ( $_REQUEST['filter']['terms'][$i]['attr'] == "Archived" )
        {
?>
              <td><?= $SLANG['OpEq'] ?><input type="hidden" name="filter[terms][<?= $i ?>][op]" value="="/></td>
              <td><?= buildSelect( "filter[terms][$i][val]", $archive_types ); ?></td>
<?php
        }
        elseif ( $_REQUEST['filter']['terms'][$i]['attr'] == "DateTime" )
        {
?>
              <td><?= buildSelect( "filter[terms][$i][op]", $op_types ); ?></td>
              <td><input name="filter[terms][<?= $i ?>][val]" id="filter[terms][<?= $i ?>][val]" value="<?= isset($_REQUEST['filter']['terms'][$i]['val'])?$_REQUEST['filter']['terms'][$i]['val']:'' ?>"/><?php if ( $hasCal ) { ?><script type="text/javascript">Calendar.setup( { inputField: "filter[terms][<?= $i ?>][val]", ifFormat: "%Y-%m-%d %H:%M", showsTime: true, timeFormat: "24", showOthers: true, weekNumbers: false });</script><?php } ?></td>
<?php
        }
        elseif ( $_REQUEST['filter']['terms'][$i]['attr'] == "Date" )
        {
?>
              <td><?= buildSelect( "filter[terms][$i][op]", $op_types ); ?></td>
              <td><input name="filter[terms][<?= $i ?>][val]" id="filter[terms][<?= $i ?>][val]" value="<?= isset($_REQUEST['filter']['terms'][$i]['val'])?$_REQUEST['filter']['terms'][$i]['val']:'' ?>"/><?php if ( $hasCal ) { ?><script type="text/javascript">Calendar.setup( { inputField: "filter[terms][<?= $i ?>][val]", ifFormat: "%Y-%m-%d", showOthers: true, weekNumbers: false });</script><?php } ?></td>
<?php
        }
        elseif ( $_REQUEST['filter']['terms'][$i]['attr'] == "Weekday" )
        {
?>
              <td><?= buildSelect( "filter[terms][$i][op]", $op_types ); ?></td>
              <td><?= buildSelect( "filter[terms][$i][val]", $weekdays ); ?></td>
<?php
        }
        else
        {
?>
              <td><?= buildSelect( "filter[terms][$i][op]", $op_types ); ?></td>
              <td><input name="filter[terms][<?= $i ?>][val]" value="<?= $_REQUEST['filter']['terms'][$i]['val'] ?>"/></td>
<?php
        }
    }
    else
    {
?>
              <td><?= buildSelect( "filter[terms][$i][op]", $op_types ); ?></td>
              <td><input name="filter[terms][<?= $i ?>][val]" value="<?= isset($_REQUEST['filter']['terms'][$i]['val'])?$_REQUEST['filter']['terms'][$i]['val']:'' ?>"/></td>
<?php
    }
?>
              <td><?php if ( count($_REQUEST['filter']['terms']) > 2 ) { echo buildSelect( "filter[terms][$i][cbr]", $cbracket_types ); } else { ?>&nbsp;<?php } ?></td>
              <td><input type="button" onclick="addTerm( this, <?= $i+1 ?> )" value="+"/><?php if ( $_REQUEST['filter']['terms'] > 1 ) { ?><input type="button" onclick="delTerm( this, <?= $i ?> )" value="-"/><?php } ?></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
        <hr/>
        <table id="sortTable" class="filterTable" cellspacing="0">
          <tbody>
            <tr>
              <td><label for="sort_field"><?= $SLANG['SortBy'] ?></label><?= buildSelect( "sort_field", $sort_fields ); ?><?= buildSelect( "sort_asc", $sort_dirns ); ?></td>
              <td><label for="limit"><?= $SLANG['LimitResultsPre'] ?></label><input type="text" size="6" name="limit" value="<?= isset($_REQUEST['limit'])?$_REQUEST['limit']:"" ?>"/><?= $SLANG['LimitResultsPost'] ?></td>
            </tr>
          </tbody>
        </table>
        <hr/>
        <table id="actionsTable" class="filterTable" cellspacing="0">
          <tbody>
            <tr>
              <td><?= $SLANG['FilterArchiveEvents'] ?></td>
              <td><input type="checkbox" name="autoArchive" value="1"<?php if ( !empty($dbFilter['AutoArchive']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
<?php
if ( ZM_OPT_MPEG != "no" )
{
?>
            <tr>
              <td><?= $SLANG['FilterVideoEvents'] ?></td>
              <td><input type="checkbox" name="autoVideo" value="1"<?php if ( !empty($dbFilter['AutoVideo']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
<?php
}
if ( ZM_OPT_UPLOAD )
{
?>
            <tr>
              <td><?= $SLANG['FilterUploadEvents'] ?></td>
              <td><input type="checkbox" name="autoUpload" value="1"<?php if ( !empty($dbFilter['AutoUpload']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
<?php
}
if ( ZM_OPT_EMAIL )
{
?>
            <tr>
              <td><?= $SLANG['FilterEmailEvents'] ?></td>
              <td><input type="checkbox" name="autoEmail" value="1"<?php if ( !empty($dbFilter['AutoEmail']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
<?php
}
if ( ZM_OPT_MESSAGE )
{
?>
            <tr>
              <td><?= $SLANG['FilterMessageEvents'] ?></td>
              <td><input type="checkbox" name="autoMessage" value="1"<?php if ( !empty($dbFilter['AutoMessage']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
<?php
}
?>
            <tr>
              <td><?= $SLANG['FilterExecuteEvents'] ?></td>
              <td><input type="checkbox" name="autoExecute" value="1"<?php if ( !empty($dbFilter['AutoExecute']) ) { ?> checked="checked"<?php } ?>/><input type="text" name="autoExecuteCmd" value="<?= isset($dbFilter['AutoExecuteCmd'])?$dbFilter['AutoExecuteCmd']:"" ?>" size="32" maxlength="255"/></td>
            </tr>
            <tr>
              <td><?= $SLANG['FilterDeleteEvents'] ?></td>
              <td colspan="2"><input type="checkbox" name="autoDelete" value="1"<?php if ( !empty($dbFilter['AutoDelete']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
          </tbody>
        </table>
        <hr/>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Submit'] ?>" onclick="submitToEvents( this );"/>
          <input type="button" value="<?= $SLANG['Execute'] ?>" onclick="executeFilter( this );"/>
<?php if ( canEdit( 'Events' ) ) { ?>
          <input type="button" value="<?= $SLANG['Save'] ?>" onclick="saveFilter( this );"/><?php } ?>
<?php if ( canEdit( 'Events' ) && isset($dbFilter) ) { ?>
          <input type="button" value="<?= $SLANG['Delete'] ?>" onclick="deleteFilter( this, '<?= $dbFilter['Name'] ?>' );"/><?php } ?>
          <input type="button" value="<?= $SLANG['Reset'] ?>" onclick="submitToFilter( this, 1 );"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
