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

$filterNames = array( ''=>translate('ChooseFilter') );
$filter = NULL;

foreach ( dbFetchAll( 'SELECT * FROM Filters ORDER BY Name' ) as $row ) {
  $filterNames[$row['Id']] = $row['Name'];
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

$terms = $filter->terms();
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
    'StorageId'   => translate('AttrStorageArea'),
    'ServerId'    => translate('AttrServer'),
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
    );

$archiveTypes = array(
    '0' => translate('ArchUnarchived'),
    '1' => translate('ArchArchived')
    );

$hasCal = file_exists( 'tools/jscalendar/calendar.js' );

$focusWindow = true;

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
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?view=filter">
        <input type="hidden" name="Id" value="<?php echo $filter->Id() ?>"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="line" value=""/>
        <input type="hidden" name="object" value="filter"/>

        <hr/>
        <p>
          <label for="filter[Name]"><?php echo translate('Name') ?></label><input type="text" id="filter[Name]" name="filter[Name]" value="<?php echo $filter->Name() ?>"/>
        </p>
        <table id="fieldsTable" class="filterTable">
          <tbody>
<?php
for ( $i = 0; $i < count($terms); $i++ ) {
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
              <td><?php echo htmlSelect( "filter[Query][terms][$i][attr]", $attrTypes, $term['attr'], "clearValue( this, $i ); this.form.submit();" ); ?></td>
<?php
  if ( isset($term['attr']) ) {
    if ( $term['attr'] == 'Archived' ) {
?>
              <td><?php echo translate('OpEq') ?><input type="hidden" name="filter[Query][terms][<?php echo $i ?>][op]" value="="/></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $archiveTypes, $term['val'] ); ?></td>
<?php
    } elseif ( $term['attr'] == 'DateTime' ) {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td>
                <input name="filter[Query][terms][<?php echo $i ?>][val]" id="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?validHtmlStr($term['val']):'' ?>"/>
<?php if ( $hasCal ) { ?>
                <script type="text/javascript">Calendar.setup( { inputField: "filter[Query][terms][<?php echo $i ?>][val]", ifFormat: "%Y-%m-%d %H:%M", showsTime: true, timeFormat: "24", showOthers: true, weekNumbers: false });</script>
<?php } ?>
              </td>
<?php
    } elseif ( $term['attr'] == 'Date' ) {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td>
                <input name="filter[Query][terms][<?php echo $i ?>][val]" id="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?validHtmlStr($term['val']):'' ?>"/>
<?php if ( $hasCal ) { ?>
                <script type="text/javascript">Calendar.setup( { inputField: "filter[Query][terms][<?php echo $i ?>][val]", ifFormat: "%Y-%m-%d", showOthers: true, weekNumbers: false });</script>
<?php } ?>
              </td>
<?php
    } elseif ( $term['attr'] == 'StateId' ) {
      $states = array();
      foreach ( dbFetchAll( 'SELECT Id,Name FROM States ORDER BY lower(Name) ASC' ) as $state_row ) {
        $states[$state_row['Id']] = $state_row['Name'];
      }
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $states, $term['val'] ); ?></td>
<?php
    } elseif ( $term['attr'] == 'Weekday' ) {
      $weekdays = array();
      for ( $i = 0; $i < 7; $i++ ) {
        $weekdays[$i] = strftime( '%A', mktime( 12, 0, 0, 1, $i+1, 2001 ) );
      }
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $weekdays, $term['val'] ); ?></td>
<?php
    } elseif ( false && $term['attr'] == 'MonitorName' ) {
      $monitors = array();
      foreach ( dbFetchAll( 'select Id,Name from Monitors order by Sequence asc' ) as $monitor ) {
        if ( visibleMonitor( $monitor['Id'] ) ) {
          $monitors[$monitor['Name']] = $monitor['Name'];
        }
      }
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $monitors, $term['val'] ); ?></td>
<?php
    } elseif ( $term['attr'] == 'ServerId' ) {
      $servers = array();
      $servers['ZM_SERVER_ID'] = 'Current Server';
      foreach ( dbFetchAll( "SELECT Id,Name FROM Servers ORDER BY lower(Name) ASC" ) as $server ) {
        $servers[$server['Id']] = $server['Name'];
      }
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $servers, $term['val'] ); ?></td>
<?php
    } elseif ( $term['attr'] == 'StorageId' ) {
        $storageareas = array();
        $storageareas[0] = 'Default ' . ZM_DIR_EVENTS;
        foreach ( dbFetchAll( "SELECT Id,Name FROM Storage ORDER BY lower(Name) ASC" ) as $storage ) {
          $storageareas[$storage['Id']] = $storage['Name'];
        }
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][val]", $storageareas, $term['val'] ); ?></td>
<?php
    } else {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><input name="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo $term['val'] ?>"/></td>
<?php
    }
  } else {
?>
              <td><?php echo htmlSelect( "filter[Query][terms][$i][op]", $opTypes, $term['op'] ); ?></td>
              <td><input name="filter[Query][terms][<?php echo $i ?>][val]" value="<?php echo isset($term['val'])?$term['val']:'' ?>"/></td>
<?php
  }
?>
              <td><?php if ( count($terms) > 2 ) { echo htmlSelect( "filter[Query][terms][$i][cbr]", $cbracketTypes, $term['cbr'] ); } else { ?>&nbsp;<?php } ?></td>
              <td>
                <input type="button" onclick="addTerm( this, <?php echo $i+1 ?> )" value="+"/>
<?php
  if ( count($terms) > 1 ) {
?>
                <input type="button" onclick="delTerm( this, <?php echo $i ?> )" value="-"/>
<?php
  }
?>            </td>
            </tr>
<?php
} # end foreach filter
?>
          </tbody>
        </table>
<?php
if ( count($terms) == 0 ) {
?>
        <input type="button" onclick="addTerm( this, 1 )" value="+"/>
<?php
}
?>
        <hr/>
        <table id="sortTable" class="filterTable">
          <tbody>
            <tr>
              <td>
                <label for="filter[Query][sort_field]"><?php echo translate('SortBy') ?></label>
                <?php
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
                <input type="text" id="filter[Query][limit]" name="filter[Query][limit]" value="<?php echo (null !== $filter->limit())?validInt($filter->limit()):'' ?>"/><?php echo translate('LimitResultsPost') ?>
              </td>
            </tr>
          </tbody>
        </table>
        <hr/>
        <div id="actionsTable" class="filterTable">
        
            <p>
              <label><?php echo translate('FilterArchiveEvents') ?></label>
              <input type="checkbox" name="filter[AutoArchive]" value="1"<?php if ( !empty($filter->AutoArchive()) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
<?php
if ( ZM_OPT_FFMPEG ) {
?>
            <p>
              <label><?php echo translate('FilterVideoEvents') ?></label>
              <input type="checkbox" name="filter[AutoVideo]" value="1"<?php if ( !empty($filter->AutoVideo()) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
<?php
}
if ( ZM_OPT_UPLOAD ) {
?>
            <p>
              <label><?php echo translate('FilterUploadEvents') ?></label>
              <input type="checkbox" name="filter[AutoUpload]" value="1"<?php if ( !empty($filter->AutoUpload()) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
<?php
}
if ( ZM_OPT_EMAIL ) {
?>
            <p>
              <label><?php echo translate('FilterEmailEvents') ?></label>
              <input type="checkbox" name="filter[AutoEmail]" value="1"<?php if ( !empty($filter->AutoEmail()) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
<?php
}
if ( ZM_OPT_MESSAGE ) {
?>
            <p>
              <label><?php echo translate('FilterMessageEvents') ?></label>
              <input type="checkbox" name="filter[AutoMessage]" value="1"<?php if ( !empty($filter->AutoMessage()) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
<?php
}
?>
            <p>
              <label><?php echo translate('FilterExecuteEvents') ?></label>
              
                <input type="checkbox" name="filter[AutoExecute]" value="1"<?php if ( !empty($filter->AutoExecute()) ) { ?> checked="checked"<?php } ?>/>
                <input type="text" name="filter[AutoExecuteCmd]" value="<?php echo (null !==$filter->AutoExecuteCmd())?$filter->AutoExecuteCmd():'' ?>" maxlength="255" onchange="updateButtons( this )"/>
            </p>
            <p>
              <label><?php echo translate('FilterDeleteEvents') ?></label>
              <input type="checkbox" name="filter[AutoDelete]" value="1"<?php if ( !empty($filter->AutoDelete()) ) { ?> checked="checked"<?php } ?> onclick="updateButtons( this )"/>
            </p>
            <p>
              <label for="background"><?php echo translate('BackgroundFilter') ?></label>
              <input type="checkbox" id="filter[Background]" name="filter[Background]" value="1"<?php if ( !empty($filter->Background()) ) { ?> checked="checked"<?php } ?>/>
            </p>
            <p>
              <label for="Concurrent"><?php echo translate('ConcurrentFilter') ?></label>
              <input type="checkbox" id="filter[Concurrent]" name="filter[Concurrent]" value="1"<?php if ( !empty($filter->Concurrent()) ) { ?> checked="checked"<?php } ?>/>
            </p>
        </div>
        <hr/>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Submit') ?>" onclick="submitToEvents( this );"/>
          <input type="button" name="executeButton" id="executeButton" value="<?php echo translate('Execute') ?>" onclick="executeFilter( this );"/>
<?php 
if ( canEdit( 'Events' ) ) {
?>
          <input type="button" value="<?php echo translate('Save') ?>" onclick="saveFilter( this );"/>
<?php 
  if ( $filter->Id() ) {
 ?>
          <input type="button" value="<?php echo translate('Delete') ?>" onclick="deleteFilter( this, '<?php echo $filter->Name() ?>' );"/>
<?php 
  }
}
?>
          <input type="button" value="<?php echo translate('Reset') ?>" onclick="submitToFilter( this, 1 );"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
