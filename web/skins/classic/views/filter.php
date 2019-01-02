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
require_once 'includes/Filter.php';
parseSort();

$filterNames = array( ''=>translate('ChooseFilter') );
$filter = NULL;

foreach ( dbFetchAll( 'SELECT * FROM Filters ORDER BY Name' ) as $row ) {
  $filterNames[$row['Id']] = $row['Id'] . ' ' . $row['Name'];
  if ( $row['Background'] )
    $filterNames[$row['Id']] .= '*';
  if ( $row['Concurrent'] )
    $filterNames[$row['Id']] .= '&';

  if ( isset($_REQUEST['Id']) && $_REQUEST['Id'] == $row['Id'] ) {
    $filter = new Filter( $row );
  }
}
if ( ! $filter ) {
  $filter = new Filter();
}

if ( isset($_REQUEST['sort_field']) && isset($_REQUEST['filter']) ) {
  $_REQUEST['filter']['Query']['sort_field'] = $_REQUEST['sort_field'];
  $_REQUEST['filter']['Query']['sort_asc'] = $_REQUEST['sort_asc'];
  $_REQUEST['filter']['Query']['limit'] = $_REQUEST['limit'];
}

if ( isset($_REQUEST['filter']) ) {
  $filter->set( $_REQUEST['filter'] );
  # Update our filter object with whatever changes we have made before saving
}

$conjunctionTypes = array(
    'and' => translate('ConjAnd'),
    'or'  => translate('ConjOr')
    );
$obracketTypes = array();
$cbracketTypes = array();

if (count($filter->terms()) > 0) {
  $terms = $filter->terms();
} else {
  $terms[] = array();
}

if ( count($terms) ) {
  for ( $i = 0; $i <= count($terms)-2; $i++ ) {
    $obracketTypes[$i] = str_repeat( '(', $i );
    $cbracketTypes[$i] = str_repeat( ')', $i );
  }
}

$attrTypes = array(
    'MonitorId'   => translate('AttrMonitorId'),
    'MonitorName' => translate('AttrMonitorName'),
    'Id'          => translate('AttrId'),
    'Name'        => translate('AttrName'),
    'Cause'       => translate('AttrCause'),
    'Notes'       => translate('AttrNotes'),
    'StartDateTime'    => translate('AttrStartDateTime'),
    'StartDate'        => translate('AttrStartDate'),
    'StartTime'        => translate('AttrStartTime'),
    'StartWeekday'     => translate('AttrStartWeekday'),
    'EndDateTime'    => translate('AttrEndDateTime'),
    'EndDate'        => translate('AttrEndDate'),
    'EndTime'        => translate('AttrEndTime'),
    'EndWeekday'     => translate('AttrEndWeekday'),
    'Length'      => translate('AttrDuration'),
    'Frames'      => translate('AttrFrames'),
    'AlarmFrames' => translate('AttrAlarmFrames'),
    'TotScore'    => translate('AttrTotalScore'),
    'AvgScore'    => translate('AttrAvgScore'),
    'MaxScore'    => translate('AttrMaxScore'),
    'Archived'    => translate('AttrArchiveStatus'),
    'DiskBlocks'  => translate('AttrDiskBlocks'),
    'DiskPercent' => translate('AttrDiskPercent'),
    'DiskSpace'   => translate('AttrDiskSpace'),
    'SystemLoad'  => translate('AttrSystemLoad'),
    'StorageId'   => translate('AttrStorageArea'),
    'ServerId'    => translate('AttrMonitorServer'),
    'FilterServerId'     => translate('AttrFilterServer'),
    'MonitorServerId'    => translate('AttrMonitorServer'),
    'StorageServerId'    => translate('AttrStorageServer'),
    'StateId'     => translate('AttrStateId'),
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
    'IS'  => translate('OpIs'),
    'IS NOT'  => translate('OpIsNot'),
    );

$archiveTypes = array(
    '0' => translate('ArchUnarchived'),
    '1' => translate('ArchArchived')
    );

$focusWindow = true;

$storageareas = array( '' => 'All' );
//$storageareas[0] = 'Default ' . ZM_DIR_EVENTS;
foreach ( dbFetchAll( 'SELECT Id,Name FROM Storage ORDER BY lower(Name) ASC' ) as $storage ) {
  $storageareas[$storage['Id']] = $storage['Name'];
}
$weekdays = array();
for ( $i = 0; $i < 7; $i++ ) {
  $weekdays[$i] = strftime( '%A', mktime( 12, 0, 0, 1, $i+1, 2001 ) );
}
$states = array();
foreach ( dbFetchAll( 'SELECT Id,Name FROM States ORDER BY lower(Name) ASC' ) as $state_row ) {
  $states[$state_row['Id']] = $state_row['Name'];
}
$servers = array();
$servers['ZM_SERVER_ID'] = 'Current Server';
$servers['NULL'] = 'No Server';
foreach ( dbFetchAll( 'SELECT Id,Name FROM Servers ORDER BY lower(Name) ASC' ) as $server ) {
  $servers[$server['Id']] = $server['Name'];
}
$monitors = array();
foreach ( dbFetchAll( 'select Id,Name from Monitors order by Name asc' ) as $monitor ) {
  if ( visibleMonitor( $monitor['Id'] ) ) {
    $monitors[$monitor['Name']] = $monitor['Name'];
  }
}

xhtmlHeaders(__FILE__, translate('EventFilter') );
?>
<body>
  <div id="page">
<?php echo $navbar = getNavBarHTML(); ?>
    <div id="content">
      <form name="selectForm" id="selectForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="filter"/>
        <hr/>
        <div id="filterSelector"><label for="<?php echo 'Id' ?>"><?php echo translate('UseFilter') ?></label>
<?php
if ( count($filterNames) > 1 ) {
   echo htmlSelect( 'Id', $filterNames, $filter->Id(), 'this.form.submit();' );
} else {
?><select disabled="disabled"><option><?php echo translate('NoSavedFilters') ?></option></select>
<?php
}
if ( (null !== $filter->Background()) and $filter->Background() ) 
  echo '['.strtolower(translate('Background')).']';
if ( (null !== $filter->Concurrent()) and $filter->Concurrent() ) 
  echo '['.strtolower(translate('Concurrent')).']';
?>
        </div>
      </form>
      <form name="contentForm" id="contentForm" method="post" onsubmit="return validateForm(this);">
        <input type="hidden" name="Id" value="<?php echo $filter->Id() ?>"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="object" value="filter"/>

        <hr/>
        <?php if ( $filter->Id() ) { ?>
        <p class="Id"><label><?php echo translate('Id') ?></label><?php echo $filter->Id() ?></p>
        <?php } ?>
        <p class="Name">
          <label for="filter[Name]"><?php echo translate('Name') ?></label>
          <input type="text" id="filter[Name]" name="filter[Name]" value="<?php echo $filter->Name() ?>" oninput="updateButtons(this);"/>
        </p>
        <table id="fieldsTable" class="filterTable">
          <tbody>
<?php
for ( $i=0; $i < count($terms); $i++ ) {
  $term = $terms[$i];
  if ( ! isset( $term['op'] ) )
    $term['op'] = '=';
  if ( ! isset( $term['attr'] ) )
    $term['attr'] = '';
  if ( ! isset( $term['val'] ) )
    $term['val'] = '';
  if ( ! isset( $term['cnj'] ) )
    $term['cnj'] = 'and';
  if ( ! isset( $term['cbr'] ) )
    $term['cbr'] = '';
  if ( ! isset( $term['obr'] ) )
    $term['obr'] = '';
?>
            <tr>
<?php
  if ( $i == 0 ) {
?>
              <td>&nbsp;</td>
<?php
  } else {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][cnj]", $conjunctionTypes, $term['cnj'] ); ?></td>
<?php
  }
?>
              <td><?php if ( count($terms) > 2 ) { echo htmlSelect( "filter[Query][terms][$i][obr]", $obracketTypes, $term['obr'] ); } else { ?>&nbsp;<?php } ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][attr]", $attrTypes, $term['attr'], "checkValue( this );" ); ?></td>
<?php
  if ( isset($term['attr']) ) {
    if ( $term['attr'] == 'Archived' ) {
?>
              <td><?php echo translate('OpEq') ?><input type="hidden" name="filter[Query][terms][<?php echo $i ?>][op]" value="="/></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $archiveTypes, $term['val'] ); ?></td>
<?php
    } elseif ( $term['attr'] == 'DateTime' || $term['attr'] == 'StartDateTime' || $term['attr'] == 'EndDateTime') {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td>
                <input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" id="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?validHtmlStr(str_replace('T', ' ', $term['val'])):'' ?>"/>
                <script type="text/javascript">$j("[name$='\\[<?php echo $i ?>\\]\\[val\\]']").datetimepicker({timeFormat: "HH:mm:ss", dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false}); </script>
              </td>
<?php
    } elseif ( $term['attr'] == 'Date' || $term['attr'] == 'StartDate' || $term['attr'] == 'EndDate') {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td>
                <input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" id="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?validHtmlStr($term['val']):'' ?>"/>
                <script type="text/javascript">$j("[name$='\\[<?php echo $i ?>\\]\\[val\\]']").datepicker({dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false}); </script>
              </td>
<?php
    } elseif ( $term['attr'] == 'StartTime' || $term['attr'] == 'EndTime') {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td>
                <input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" id="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?validHtmlStr(str_replace('T', ' ', $term['val'])):'' ?>"/>
                <script type="text/javascript">$j("[name$='\\[<?php echo $i ?>\\]\\[val\\]']").timepicker({timeFormat: "HH:mm:ss", constrainInput: false}); </script>
              </td>
<?php
    } elseif ( $term['attr'] == 'StateId' ) {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $states, $term['val'] ); ?></td>
<?php
    } elseif ( strpos($term['attr'], 'Weekday') !== false ) {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $weekdays, $term['val'] ); ?></td>
<?php
    } elseif ( $term['attr'] == 'MonitorName' ) {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $monitors, $term['val'] ); ?></td>
<?php
    } elseif ( $term['attr'] == 'ServerId' || $term['attr'] == 'MonitorServerId' || $term['attr'] == 'StorageServerId' || $term['attr'] == 'FilterServerId' ) {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $servers, $term['val'] ); ?></td>
<?php
    } elseif ( $term['attr'] == 'StorageId' ) {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $storageareas, $term['val'] ); ?></td>
<?php
    } else {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo $term['val'] ?>"/></td>
<?php
    }
  } else {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?$term['val']:'' ?>"/></td>
<?php
  }
?>
              <td><?php if ( count($terms) > 2 ) { echo htmlSelect( "filter[Query][terms][$i][cbr]", $cbracketTypes, $term['cbr'] ); } else { ?>&nbsp;<?php } ?></td>
              <td>
                <input type="button" onclick="addTerm( this )" value="+"/>
                <input type="button" onclick="delTerm( this )" value="-" <?php echo count($terms) == 1 ? 'disabled' : '' ?>/>
              </td>
            </tr>
<?php
} # end foreach filter
?>
          </tbody>
        </table>
        <hr/>
        <table id="sortTable" class="filterTable">
          <tbody>
            <tr>
              <td>
                <label for="filter[Query][sort_field]"><?php echo translate('SortBy') ?></label>
                <?php
$sort_fields = array(
    'Id'            => translate('AttrId'),
    'Name'          => translate('AttrName'),
    'Cause'         => translate('AttrCause'),
    'DiskSpace'     => translate('AttrDiskSpace'),
    'Notes'         => translate('AttrNotes'),
    'MonitorName'   => translate('AttrMonitorName'),
    'StartDateTime' => translate('AttrStartDateTime'),
    'Length'        => translate('AttrDuration'),
    'Frames'        => translate('AttrFrames'),
    'AlarmFrames'   => translate('AttrAlarmFrames'),
    'TotScore'      => translate('AttrTotalScore'),
    'AvgScore'      => translate('AttrAvgScore'),
    'MaxScore'      => translate('AttrMaxScore'),
    );
echo htmlSelect( 'filter[Query][sort_field]', $sort_fields, $filter->sort_field() );
$sort_dirns = array(
    '1' => translate('SortAsc'),
    '0'  => translate('SortDesc')
    );
echo htmlSelect( 'filter[Query][sort_asc]', $sort_dirns, $filter->sort_asc() );
?>
              </td>
              <td>  
                <label for="filter[Query][limit]"><?php echo translate('LimitResultsPre') ?></label>
                <input type="number" id="filter[Query][limit]" name="filter[Query][limit]" value="<?php echo (null !== $filter->limit())?validInt($filter->limit()):'' ?>"/><?php echo translate('LimitResultsPost') ?>
              </td>
            </tr>
          </tbody>
        </table>
        <hr/>
        <div id="actionsTable" class="filterTable">
            <p>
              <label><?php echo translate('FilterArchiveEvents') ?></label>
              <input type="checkbox" name="filter[AutoArchive]" value="1"<?php if ( $filter->AutoArchive() ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
            <p><label><?php echo translate('FilterUpdateDiskSpace') ?></label>
              <input type="checkbox" name="filter[UpdateDiskSpace]" value="1"<?php echo !$filter->UpdateDiskSpace() ? '' : ' checked="checked"' ?> onclick="updateButtons(this);"/>
            </p>
<?php
if ( ZM_OPT_FFMPEG ) {
?>
            <p>
              <label><?php echo translate('FilterVideoEvents') ?></label>
              <input type="checkbox" name="filter[AutoVideo]" value="1"<?php if ( $filter->AutoVideo() ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
<?php
}
if ( ZM_OPT_UPLOAD ) {
?>
            <p>
              <label><?php echo translate('FilterUploadEvents') ?></label>
              <input type="checkbox" name="filter[AutoUpload]" value="1"<?php if ( $filter->AutoUpload() ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
<?php
}
if ( ZM_OPT_EMAIL ) {
?>
            <p>
              <label><?php echo translate('FilterEmailEvents') ?></label>
              <input type="checkbox" name="filter[AutoEmail]" value="1"<?php if ( $filter->AutoEmail() ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
<?php
}
if ( ZM_OPT_MESSAGE ) {
?>
            <p>
              <label><?php echo translate('FilterMessageEvents') ?></label>
              <input type="checkbox" name="filter[AutoMessage]" value="1"<?php if ( $filter->AutoMessage() ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
<?php
}
?>
            <p>
              <label><?php echo translate('FilterExecuteEvents') ?></label>
              
                <input type="checkbox" name="filter[AutoExecute]" value="1"<?php if ( $filter->AutoExecute() ) { ?> checked="checked"<?php } ?>/>
                <input type="text" name="filter[AutoExecuteCmd]" value="<?php echo (null !==$filter->AutoExecuteCmd())?$filter->AutoExecuteCmd():'' ?>" maxlength="255" onchange="updateButtons( this )"/>
            </p>
            <p>
              <label><?php echo translate('FilterDeleteEvents') ?></label>
              <input type="checkbox" name="filter[AutoDelete]" value="1"<?php if ( $filter->AutoDelete() ) { ?> checked="checked"<?php } ?> onclick="updateButtons(this)"/>
            </p>
            <p><label><?php echo translate('FilterMoveEvents') ?></label>
              <input type="checkbox" name="filter[AutoMove]" value="1"<?php if ( $filter->AutoMove() ) { ?> checked="checked"<?php } ?> onclick="updateButtons(this);if(this.checked){$j(this.form.elements['filter[AutoMoveTo]']).css('display','inline');}else{this.form.elements['filter[AutoMoveTo]'].hide();};"/>
              <?php echo htmlSelect( "filter[AutoMoveTo]", $storageareas, $filter->AutoMoveTo(), $filter->AutoMove() ? null : array('style'=>'display:none;' ) ); ?>
            </p>
            <p>
              <label for="background"><?php echo translate('BackgroundFilter') ?></label>
              <input type="checkbox" id="filter[Background]" name="filter[Background]" value="1"<?php if ( $filter->Background() ) { ?> checked="checked"<?php } ?> onclick="updateButtons(this);"/>
            </p>
            <p>
              <label for="Concurrent"><?php echo translate('ConcurrentFilter') ?></label>
              <input type="checkbox" id="filter[Concurrent]" name="filter[Concurrent]" value="1"<?php if ( $filter->Concurrent() ) { ?> checked="checked"<?php } ?> onclick="updateButtons(this);"/>
            </p>
        </div>
        <hr/>
        <div id="contentButtons">
          <button type="submit" onclick="submitToEvents(this);"><?php echo translate('ListMatches') ?></button>
          <button type="submit" name="executeButton" id="executeButton" onclick="executeFilter( this );"><?php echo translate('Execute') ?></button>
<?php 
if ( canEdit( 'Events' ) ) {
?>
          <button type="submit" name="Save" value="Save" onclick="saveFilter(this);"><?php echo translate('Save') ?></button>
          <button type="submit" name="SaveAs" value="SaveAs" onclick="saveFilter(this);"><?php echo translate('SaveAs') ?></button>
<?php 
  if ( $filter->Id() ) {
 ?>
   <button type="button" value="Delete" onclick="deleteFilter(this, '<?php echo $filter->Name() ?>');"><?php echo translate('Delete') ?></button>
<?php 
  }
}
?>
          <button type="button" value="Reset" onclick="resetFilter( this );"><?php echo translate('Reset') ?></button>
        </div>
      </form>
    </div><!--content-->
  </div><!--page-->
<?php xhtmlFooter() ?>
