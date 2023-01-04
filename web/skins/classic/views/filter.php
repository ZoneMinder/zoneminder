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
require_once('includes/FilterTerm.php');
require_once('includes/Monitor.php');
require_once('includes/Zone.php');
require_once('includes/User.php');
parseSort();

$filterNames = array(''=>translate('ChooseFilter'));
$filter = NULL;

$fid = 0;
if ( isset($_REQUEST['Id']) and $_REQUEST['Id'] ) {
  $fid = validInt($_REQUEST['Id']);
} else if ( isset($_REQUEST['filter']) and isset($_REQUEST['filter']['Id']) ) {
  $fid = validInt($_REQUEST['filter']['Id']);
}
$filter = null;
foreach ( ZM\Filter::find(null,array('order'=>'lower(Name)')) as $Filter ) {
  $filterNames[$Filter->Id()] = $Filter->Id() . ' ' . $Filter->Name();
  if ( $Filter->Background() )
    $filterNames[$Filter->Id()] .= '*';
  if ( $Filter->Concurrent() )
    $filterNames[$Filter->Id()] .= '&';

  if ( $fid == $Filter->Id() ) {
    $filter = $Filter;
  }
}
if ( !$filter )  {
  $filter = new ZM\Filter();
  $filter->addTerm(array('cnj'=>'and', 'attr'=>'Id', 'op'=> '=', 'val'=>''));
}

ZM\Debug('filter: ' . print_r($filter,true));
if ( isset($_REQUEST['filter']) ) {
  # Update our filter object with whatever changes we have made before saving
  $filter->set($_REQUEST['filter']);
  ZM\Debug("Setting filter from " . print_r($_REQUEST['filter'], true));
}
ZM\Debug('filter: ' . print_r($filter,true));

$conjunctionTypes = ZM\getFilterQueryConjunctionTypes();

if (count($filter->terms()) > 0) {
  $terms = $filter->terms();
} else {
  $terms[] = array();
}

$obracketTypes = array();
$cbracketTypes = array();
if ( count($terms) ) {
  for ( $i = 0; $i <= count($terms)-2; $i++ ) {
    $obracketTypes[$i] = str_repeat('(', $i);
    $cbracketTypes[$i] = str_repeat(')', $i);
  }
}

$attrTypes = ZM\Filter::attrTypes();

$opTypes = ZM\Filter::opTypes();
$is_isnot_opTypes = array(
  'IS'  => translate('OpIs'),
  'IS NOT'  => translate('OpIsNot'),
);

$archiveTypes = array(
  '0' => translate('ArchUnarchived'),
  '1' => translate('ArchArchived')
);

$booleanValues = array(
  'false' => translate('False'),
  'true' => translate('True')
);

$focusWindow = true;

$storageareas = array('' => array('Name'=>'NULL Unspecified'), '0' => array('Name'=>'Zero')) + ZM\ZM_Object::Objects_Indexed_By_Id('ZM\Storage');

$weekdays = array();
for ( $i = 0; $i < 7; $i++ ) {
  $weekdays[$i] = date('D', mktime(12, 0, 0, 1, $i+1, 2001));
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
echo getBodyTopHTML();
echo $navbar = getNavBarHTML();
?>
  <div id="page">
    <div id="content">
      <form name="selectForm" id="selectForm" method="get" action="?">
        <input type="hidden" name="view" value="filter"/>
        <hr/>
        <div id="filterSelector"><label for="Id"><?php echo translate('UseFilter') ?></label>
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
      <form name="contentForm" id="contentForm" method="post" class="validateFormOnSubmit" action="?view=filter&Id=<?php echo $filter->Id() ?>">
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
<?php if ( ZM_OPT_USE_AUTH ) { ?>
        <p><label><?php echo translate('FilterUser') ?></label>
<?php 
            global $user;
  echo htmlSelect('filter[UserId]',
    ZM\User::Indexed_By_Id(),
    $filter->UserId() ? $filter->UserId() : $user['Id']
  );
?>
        </p>
<?php } ?>
        <p>
<?php echo $filter->widget(); ?>

        <hr/>
        <table id="sortTable" class="filterTable">
          <tbody>
            <tr>
              <td>
                <label for="filter[Query][sort_field]"><?php echo translate('SortBy') ?></label>
                <?php
# Note: The keys need to be actual column names
$sort_fields = array(
    ''              => translate('None'),
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
echo htmlSelect('filter[Query][sort_field]', $sort_fields, $filter->sort_field());
$sort_dirns = array(
  '1' => translate('SortAsc'),
  '0'  => translate('SortDesc')
);
echo htmlSelect('filter[Query][sort_asc]', $sort_dirns, $filter->sort_asc());
?>
              </td>
              <td>
                <label for="filter[Query][skip_locked]"><?php echo translate('Skip Locked') ?></label>
<?php
echo htmlSelect('filter[Query][skip_locked]',
  array('0'=>translate('No'), '1'=>translate('Yes')),
  $filter->skip_locked());
?>
              </td>
              <td>  
                <label for="filter[Query][limit]"><?php echo translate('LimitResultsPre') ?></label>
                <input type="number" id="filter[Query][limit]" name="filter[Query][limit]" value="<?php echo (null !== $filter->limit())?validInt($filter->limit()):'' ?>" min="0" step="1"/>
                <?php echo translate('LimitResultsPost') ?>
              </td>
            </tr>
          </tbody>
        </table>
<div id="ActionsAndOptions">
        <div id="actionsTable" class="filterTable">
          <fieldset><legend><?php echo translate('Actions') ?></legend>
            <p>
              <label><?php echo translate('FilterArchiveEvents') ?></label>
              <input type="checkbox" name="filter[AutoArchive]" value="1"<?php if ( $filter->AutoArchive() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
            <p>
              <label><?php echo translate('FilterUnarchiveEvents') ?></label>
              <input type="checkbox" name="filter[AutoUnarchive]" value="1"<?php if ( $filter->AutoUnarchive() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
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
          </fieldset>
        </div>
        <div id="optionsTable" class="filterTable">
          <fieldset><legend><?php echo translate('Options') ?></legend>
            <p>
              <label for="filter[Background]"><?php echo translate('BackgroundFilter') ?></label>
              <input type="checkbox" id="filter[Background]" name="filter[Background]" value="1"<?php if ( $filter->Background() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
            <p>
              <label for="ExecuteInterval"><?php echo translate('Execute Interval') ?></label>
              <input type="number" id="filter[ExecuteInterval]" name="filter[ExecuteInterval]" min="0" step="1" value="<?php echo $filter->ExecuteInterval() ?>" /><?php echo translate('seconds'); ?>
            </p>
            <p>
              <label for="Concurrent"><?php echo translate('ConcurrentFilter') ?></label>
              <input type="checkbox" id="filter[Concurrent]" name="filter[Concurrent]" value="1"<?php if ( $filter->Concurrent() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
            <p>
              <label for="LockRows"><?php echo translate('FilterLockRows') ?></label>
              <input type="checkbox" id="filter[LockRows]" name="filter[LockRows]" value="1"<?php if ( $filter->LockRows() ) { ?> checked="checked"<?php } ?> data-on-click-this="updateButtons"/>
            </p>
<?php
if ( ZM_OPT_EMAIL ) {
?>
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
                <textarea name="filter[EmailBody]" rows="<?php echo count(explode("\n", $filter->EmailBody())) ?>"><?php echo validHtmlStr($filter->EmailBody()) ?></textarea>
              </p>
            </div>
<?php
}
?>
          </fieldset>
        </div>
</div><!--ActionsAndOptions-->
        <div id="contentButtons">
          <button type="button" data-on-click-this="submitToEvents"><?php echo translate('ListMatches') ?></button>
          <button type="button" data-on-click-this="submitToMontageReview"><?php echo translate('ViewMatches') ?></button>
          <button type="button" data-on-click-this="submitToExport"><?php echo translate('ExportMatches') ?></button>
          <button type="button" data-on-click-this="submitAction" value="execute" id="executeButton"><?php echo translate('Execute') ?></button>
<?php
$canEdit = (canEdit('System') or ($filter->UserId() == $user['Id']));
$canSave = !$filter->Id() or $canEdit;
$canDelete = $filter->Id() and $canEdit;
?>
          <button type="button" data-on-click-this="submitAction" value="Save" id="Save"<?php echo $canSave ? '' : ' disabled="disabled"' ?>><?php echo translate('Save') ?></button>
          <button type="button" data-on-click-this="submitAction" value="SaveAs" id="SaveAs"<?php echo $canSave ? '' : ' disabled="disabled"' ?>><?php echo translate('SaveAs') ?></button>
          <button type="button" value="delete" data-on-click-this="deleteFilter" id="Delete"<?php echo $canDelete ? '' : ' disabled="disabled"' ?>><?php echo translate('Delete') ?></button>
          <button type="button" value="Debug" data-on-click-this="debugFilter"><?php echo translate('Debug') ?></button>
          <button type="button" value="Reset" data-on-click-this="resetFilter"><?php echo translate('Reset') ?></button>
        </div>
      </form>
    </div><!--content-->
  </div><!--page-->
<?php xhtmlFooter() ?>
