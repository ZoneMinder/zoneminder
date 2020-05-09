<?php
//
// ZoneMinder web filter view file
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

if ( !canView('Events') ) {
  $view = 'error';
  return;
}
require_once('includes/Object.php');
require_once('includes/Storage.php');
require_once('includes/Filter.php');
require_once('includes/Monitor.php');
require_once('includes/Zone.php');
parseSort();

$filterNames = array(''=>translate('ChooseFilter'));
$filter = NULL;

foreach ( ZM\Filter::find(null,array('order'=>'lower(Name)')) as $Filter ) {
  $filterNames[$Filter->Id()] = $Filter->Id() . ' ' . $Filter->Name();
  if ( $Filter->Background() )
    $filterNames[$Filter->Id()] .= '*';
  if ( $Filter->Concurrent() )
    $filterNames[$Filter->Id()] .= '&';

  if ( isset($_REQUEST['Id']) && ($_REQUEST['Id'] == $Filter->Id()) ) {
    $filter = $Filter;
  }
}
if ( !$filter ) {
  $filter = new ZM\Filter();
}

if ( isset($_REQUEST['filter']) ) {
  # Update our filter object with whatever changes we have made before saving
  #$filter->set($_REQUEST['filter']);
}

$conjunctionTypes = getFilterQueryConjunctionTypes();
$obracketTypes = array();
$cbracketTypes = array();

if ( count($filter->terms()) > 0 ) {
  $terms = $filter->terms();
} else {
  $terms[] = array();
}

if ( count($terms) ) {
  for ( $i = 0; $i <= count($terms)-2; $i++ ) {
    $obracketTypes[$i] = str_repeat('(', $i);
    $cbracketTypes[$i] = str_repeat(')', $i);
  }
}

$attrTypes = array(
    'AlarmFrames' => translate('AttrAlarmFrames'),
		'AlarmedZoneId'	=>	translate('AttrAlarmedZone'),
    'Archived'    => translate('AttrArchiveStatus'),
    'AvgScore'    => translate('AttrAvgScore'),
    'Cause'       => translate('AttrCause'),
    'DiskBlocks'  => translate('AttrDiskBlocks'),
    'DiskPercent' => translate('AttrDiskPercent'),
    'DiskSpace'   => translate('AttrDiskSpace'),
    'EndDateTime'    => translate('AttrEndDateTime'),
    'EndDate'        => translate('AttrEndDate'),
    'EndTime'        => translate('AttrEndTime'),
    'EndWeekday'     => translate('AttrEndWeekday'),
    'FilterServerId'     => translate('AttrFilterServer'),
    'Frames'      => translate('AttrFrames'),
    'Id'          => translate('AttrId'),
    'Length'      => translate('AttrDuration'),
    'MaxScore'    => translate('AttrMaxScore'),
    'MonitorId'   => translate('AttrMonitorId'),
    'MonitorName' => translate('AttrMonitorName'),
    'MonitorServerId'    => translate('AttrMonitorServer'),
    'Name'        => translate('AttrName'),
    'Notes'       => translate('AttrNotes'),
    'SecondaryStorageId'   => translate('AttrSecondaryStorageArea'),
    'ServerId'           => translate('AttrMonitorServer'),
    'StartDateTime'    => translate('AttrStartDateTime'),
    'StartDate'        => translate('AttrStartDate'),
    'StartTime'        => translate('AttrStartTime'),
    'StartWeekday'     => translate('AttrStartWeekday'),
    'StateId'            => translate('AttrStateId'),
    'StorageId'           => translate('AttrStorageArea'),
    'StorageServerId'    => translate('AttrStorageServer'),
    'SystemLoad'  => translate('AttrSystemLoad'),
    'TotScore'    => translate('AttrTotalScore'),
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
    'LIKE' => translate('OpLike'),
    'NOT LIKE' => translate('OpNotLike'),
    );

$archiveTypes = array(
    '0' => translate('ArchUnarchived'),
    '1' => translate('ArchArchived')
    );

$focusWindow = true;

$storageareas = array('' => 'All') + ZM\ZM_Object::Objects_Indexed_By_Id('ZM\Storage');

$weekdays = array();
for ( $i = 0; $i < 7; $i++ ) {
  $weekdays[$i] = strftime('%A', mktime(12, 0, 0, 1, $i+1, 2001));
}
$states = array();
foreach ( dbFetchAll('SELECT `Id`, `Name` FROM `States` ORDER BY lower(`Name`) ASC') as $state_row ) {
  $states[$state_row['Id']] = validHtmlStr($state_row['Name']);
}
$servers = array();
$servers['ZM_SERVER_ID'] = 'Current Server';
$servers['NULL'] = 'No Server';
foreach ( dbFetchAll('SELECT `Id`, `Name` FROM `Servers` ORDER BY lower(`Name`) ASC') as $server ) {
  $servers[$server['Id']] = validHtmlStr($server['Name']);
}
$monitors = array();
$monitor_names = array();
foreach ( dbFetchAll('SELECT `Id`, `Name` FROM `Monitors` ORDER BY lower(`Name`) ASC') as $monitor ) {
  if ( visibleMonitor($monitor['Id']) ) {
    $monitors[$monitor['Id']] = new ZM\Monitor($monitor);
		$monitor_names[] = validHtmlStr($monitor['Name']);
  }
}
$zones = array();
foreach ( dbFetchAll('SELECT Id, Name, MonitorId FROM Zones ORDER BY lower(`Name`) ASC') as $zone ) {
  if ( visibleMonitor($zone['MonitorId']) ) {
    if ( isset($monitors[$zone['MonitorId']]) ) {
      $zone['Name'] = validHtmlStr($monitors[$zone['MonitorId']]->Name().': '.$zone['Name']);
      $zones[$zone['Id']] = new ZM\Zone($zone);
    }
  }
}

xhtmlHeaders(__FILE__, translate('EventFilter'));
?>
<body>
  <div id="page">
<?php echo $navbar = getNavBarHTML(); ?>
    <div id="content">
      <form name="selectForm" id="selectForm" method="get" action="?">
        <input type="hidden" name="view" value="filter"/>
        <hr/>
        <div id="filterSelector"><label for="<?php echo 'Id' ?>"><?php echo translate('UseFilter') ?></label>
<?php
if ( count($filterNames) > 1 ) {
   echo htmlSelect('Id', $filterNames, $filter->Id(), array('data-on-change-this'=>'selectFilter'));
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
      <form name="contentForm" id="contentForm" method="post" class="validateFormOnSubmit" action="?view=filter">
        <input type="hidden" name="Id" value="<?php echo $filter->Id() ?>"/>
        <input type="hidden" name="action"/>
        <input type="hidden" name="object" value="filter"/>

        <hr/>
        <?php if ( $filter->Id() ) { ?>
        <p class="Id"><label><?php echo translate('Id') ?></label><?php echo $filter->Id() ?></p>
        <?php } ?>
        <p class="Name">
          <label for="filter[Name]"><?php echo translate('Name') ?></label>
          <input type="text" id="filter[Name]" name="filter[Name]" value="<?php echo validHtmlStr($filter->Name()) ?>" data-on-input-this="updateButtons"/>
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
              <td><?php echo htmlSelect("filter[Query][terms][$i][cnj]", $conjunctionTypes, $term['cnj']); ?></td>
<?php
  }
?>
              <td><?php if ( count($terms) > 2 ) { echo htmlSelect("filter[Query][terms][$i][obr]", $obracketTypes, $term['obr']); } else { ?>&nbsp;<?php } ?></td>
              <td><?php echo htmlSelect("filter[Query][terms][$i][attr]", $attrTypes, $term['attr'], array('data-on-change-this'=>'checkValue')); ?></td>
<?php
  if ( isset($term['attr']) ) {
    if ( $term['attr'] == 'Archived' ) {
?>
              <td><?php echo translate('OpEq') ?><input type="hidden" name="filter[Query][terms][<?php echo $i ?>][op]" value="="/></td>
              <td><?php echo htmlSelect("filter[Query][terms][$i][val]", $archiveTypes, $term['val']); ?></td>
<?php
    } elseif ( $term['attr'] == 'DateTime' || $term['attr'] == 'StartDateTime' || $term['attr'] == 'EndDateTime') {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td>
                <input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" id="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?validHtmlStr(str_replace('T', ' ', $term['val'])):'' ?>"/>
                <script nonce="<?php echo $cspNonce;?>">$j("[name$='\\[<?php echo $i ?>\\]\\[val\\]']").datetimepicker({timeFormat: "HH:mm:ss", dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false}); </script>
              </td>
<?php
    } elseif ( $term['attr'] == 'Date' || $term['attr'] == 'StartDate' || $term['attr'] == 'EndDate' ) {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td>
                <input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" id="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?validHtmlStr($term['val']):'' ?>"/>
                <script nonce="<?php echo $cspNonce;?>">$j("[name$='\\[<?php echo $i ?>\\]\\[val\\]']").datepicker({dateFormat: "yy-mm-dd", maxDate: 0, constrainInput: false});</script>
              </td>
<?php
    } elseif ( $term['attr'] == 'StartTime' || $term['attr'] == 'EndTime' ) {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td>
                <input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" id="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?validHtmlStr(str_replace('T', ' ', $term['val'])):'' ?>"/>
                <script nonce="<?php echo $cspNonce;?>">$j("[name$='\\[<?php echo $i ?>\\]\\[val\\]']").timepicker({timeFormat: "HH:mm:ss", constrainInput: false}); </script>
              </td>
<?php
    } elseif ( $term['attr'] == 'StateId' ) {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td><?php echo htmlSelect("filter[Query][terms][$i][val]", $states, $term['val']); ?></td>
<?php
    } elseif ( strpos($term['attr'], 'Weekday') !== false ) {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td><?php echo htmlSelect("filter[Query][terms][$i][val]", $weekdays, $term['val']); ?></td>
<?php
    } elseif ( $term['attr'] == 'Monitor' ) {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td><?php echo htmlSelect("filter[Query][terms][$i][val]", $monitors, $term['val']); ?></td>
<?php
    } elseif ( $term['attr'] == 'MonitorName' ) {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td><?php echo htmlSelect("filter[Query][terms][$i][val]", array_combine($monitor_names,$monitor_names), $term['val']); ?></td>
<?php
    } elseif ( $term['attr'] == 'ServerId' || $term['attr'] == 'MonitorServerId' || $term['attr'] == 'StorageServerId' || $term['attr'] == 'FilterServerId' ) {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td><?php echo htmlSelect("filter[Query][terms][$i][val]", $servers, $term['val']); ?></td>
<?php
    } elseif ( ($term['attr'] == 'StorageId') || ($term['attr'] == 'SecondaryStorageId') ) {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td><?php echo htmlSelect("filter[Query][terms][$i][val]", $storageareas, $term['val']); ?></td>
<?php
    } elseif ( $term['attr'] == 'AlarmedZoneId' ) {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td><?php echo htmlSelect("filter[Query][terms][$i][val]", $zones, $term['val']); ?></td>
<?php
    } else {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td><input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo validHtmlStr($term['val']) ?>"/></td>
<?php
    }
  } else {
?>
              <td><?php echo htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']); ?></td>
              <td><input type="text" name="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?validHtmlStr($term['val']):'' ?>"/></td>
<?php
  }
?>
              <td><?php if ( count($terms) > 2 ) { echo htmlSelect("filter[Query][terms][$i][cbr]", $cbracketTypes, $term['cbr']); } else { ?>&nbsp;<?php } ?></td>
              <td>
                <button type="button" data-on-click-this="addTerm">+</button>
                <button type="button" data-on-click-this="delTerm" <?php echo count($terms) == 1 ? 'disabled' : '' ?>>-</button>
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
              <input type="checkbox" name="filter[AutoArchive]" value="1"<?php if ( $filter->AutoArchive() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
            <p><label><?php echo translate('FilterUpdateDiskSpace') ?></label>
              <input type="checkbox" name="filter[UpdateDiskSpace]" value="1"<?php echo !$filter->UpdateDiskSpace() ? '' : ' checked="checked"' ?> data-on-click-this="updateButtons"/>
            </p>
<?php
if ( ZM_OPT_FFMPEG ) {
?>
            <p>
              <label><?php echo translate('FilterVideoEvents') ?></label>
              <input type="checkbox" name="filter[AutoVideo]" value="1"<?php if ( $filter->AutoVideo() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
<?php
}
if ( ZM_OPT_UPLOAD ) {
?>
            <p>
              <label><?php echo translate('FilterUploadEvents') ?></label>
              <input type="checkbox" name="filter[AutoUpload]" value="1"<?php if ( $filter->AutoUpload() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
<?php
}
if ( ZM_OPT_EMAIL ) {
?>
            <p>
              <label><?php echo translate('FilterEmailEvents') ?></label>
              <input type="checkbox" name="filter[AutoEmail]" value="1"<?php if ( $filter->AutoEmail() ) { ?> checked="checked"<?php } ?> data-on-click-this="click_AutoEmail"/>
            </p>
						<div id="EmailOptions"<?php echo $filter->AutoEmail() ? '' : ' style="display:none;"' ?>>
							<p>
								<label><?php echo translate('FilterEmailTo') ?></label>
								<input type="email" name="filter[EmailTo]" value="<?php echo validHtmlStr($filter->EmailTo()) ?>" multiple/>
							</p>
							<p>
								<label><?php echo translate('FilterEmailSubject') ?></label>
								<input type="text" name="filter[EmailSubject]" value="<?php echo validHtmlStr($filter->EmailSubject()) ?>"/>
							</p>
							<p>
								<label><?php echo translate('FilterEmailBody') ?></label>
								<textarea name="filter[EmailBody]"><?php echo validHtmlStr($filter->EmailBody()) ?></textarea>
							</p>
						</div>
<?php
}
if ( ZM_OPT_MESSAGE ) {
?>
            <p>
              <label><?php echo translate('FilterMessageEvents') ?></label>
              <input type="checkbox" name="filter[AutoMessage]" value="1"<?php if ( $filter->AutoMessage() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
<?php
}
?>
            <p>
              <label><?php echo translate('FilterExecuteEvents') ?></label>
              <input type="checkbox" name="filter[AutoExecute]" value="1"<?php if ( $filter->AutoExecute() ) { ?> checked="checked"<?php } ?>/>
              <input type="text" name="filter[AutoExecuteCmd]" value="<?php echo (null !==$filter->AutoExecuteCmd())?validHtmlStr($filter->AutoExecuteCmd()):'' ?>" maxlength="255" data-on-change-this="updateButtons"/>
            </p>
            <p>
              <label><?php echo translate('FilterDeleteEvents') ?></label>
              <input type="checkbox" name="filter[AutoDelete]" value="1"<?php if ( $filter->AutoDelete() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
            <p>
              <label><?php echo translate('FilterCopyEvents') ?></label>
              <input type="checkbox" name="filter[AutoCopy]" value="1"<?php if ( $filter->AutoCopy() ) { ?> checked="checked"<?php } ?> data-on-click-this="click_autocopy"/>
              <?php echo htmlSelect('filter[AutoCopyTo]', $storageareas, $filter->AutoCopyTo(), $filter->AutoCopy() ? null : array('style'=>'display:none;')); ?>
            </p>
            <p>
              <label><?php echo translate('FilterMoveEvents') ?></label>
              <input type="checkbox" name="filter[AutoMove]" value="1"<?php if ( $filter->AutoMove() ) { ?> checked="checked"<?php } ?> data-on-click-this="click_automove"/>
              <?php echo htmlSelect('filter[AutoMoveTo]', $storageareas, $filter->AutoMoveTo(), $filter->AutoMove() ? null : array('style'=>'display:none;')); ?>
            </p>
            <p>
              <label for="background"><?php echo translate('BackgroundFilter') ?></label>
              <input type="checkbox" id="filter[Background]" name="filter[Background]" value="1"<?php if ( $filter->Background() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
            <p>
              <label for="Concurrent"><?php echo translate('ConcurrentFilter') ?></label>
              <input type="checkbox" id="filter[Concurrent]" name="filter[Concurrent]" value="1"<?php if ( $filter->Concurrent() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
        </div>
        <hr/>
        <div id="contentButtons">
          <button type="submit" data-on-click-this="submitToEvents"><?php echo translate('ListMatches') ?></button>
          <button type="button" data-on-click-this="submitToMontageReview"><?php echo translate('ViewMatches') ?></button>
          <button type="button" data-on-click-this="submitToExport"><?php echo translate('ExportMatches') ?></button>
          <button type="button" name="executeButton" id="executeButton" data-on-click-this="executeFilter"><?php echo translate('Execute') ?></button>
<?php 
if ( canEdit('Events') ) {
?>
          <button type="submit" name="Save" value="Save" data-on-click-this="saveFilter"><?php echo translate('Save') ?></button>
          <button type="submit" name="SaveAs" value="SaveAs" data-on-click-this="saveFilter"><?php echo translate('SaveAs') ?></button>
<?php 
  if ( $filter->Id() ) {
 ?>
          <button type="button" value="Delete" data-on-click-this="deleteFilter"><?php echo translate('Delete') ?></button>
<?php 
  }
}
?>
          <button type="button" value="Reset" data-on-click-this="resetFilter"><?php echo translate('Reset') ?></button>
        </div>
      </form>
    </div><!--content-->
  </div><!--page-->
<?php xhtmlFooter() ?>
