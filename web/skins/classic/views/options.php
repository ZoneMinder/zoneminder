<?php
//
// ZoneMinder web options view file, $Date$, $Revision$
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

if ( !canView('System') ) {
  $view = 'error';
  return;
}

$canEdit = canEdit('System');

$tabs = array();
$tabs['skins'] = translate('Display');
$tabs['system'] = translate('System');
$tabs['config'] = translate('Config');
$tabs['API'] = translate('API');
$tabs['servers'] = translate('Servers');
$tabs['storage'] = translate('Storage');
$tabs['web'] = translate('Web');
$tabs['images'] = translate('Images');
$tabs['logging'] = translate('Logging');
$tabs['network'] = translate('Network');
$tabs['mail'] = translate('Email');
$tabs['upload'] = translate('Upload');
$tabs['x10'] = translate('X10');
$tabs['highband'] = translate('HighBW');
$tabs['medband'] = translate('MediumBW');
$tabs['lowband'] = translate('LowBW');
$tabs['users'] = translate('Users');

if ( isset($_REQUEST['tab']) )
  $tab = validHtmlStr($_REQUEST['tab']);
else
  $tab = 'system';

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Options'));

?>
<body>
  <?php echo getNavBarHTML(); ?>
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-2 sidebar">
        <ul class="nav nav-pills nav-stacked">
<?php
foreach ( $tabs as $name=>$value ) {
?>
          <li<?php echo $tab == $name ? ' class="active"' : '' ?>><a href="?view=<?php echo $view ?>&amp;tab=<?php echo $name ?>"><?php echo $value ?></a></li>
<?php
}
?>
        </ul>
      </div>
      <div class="col-sm-10 col-sm-offset-2">
        <br/>
        <div id="options">
<?php 
if ( $tab == 'skins' ) {
?>
          <form name="optionsForm" class="form-horizontal" method="get" action="?">
            <input type="hidden" name="view" value="<?php echo $view ?>"/>
            <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
            <div class="form-group">
            <label for="skin" class="col-sm-3 control-label"><?php echo translate('Skin')?></label>
              <div class="col-sm-6">
                <select name="skin" class="form-control chosen">
<?php
  # Have to do this stuff up here before including header.php because fof the cookie setting
$skin_options = array_map('basename', glob('skins/*',GLOB_ONLYDIR));
foreach ( $skin_options as $dir ) {
  echo '<option value="'.$dir.'" '.($skin==$dir ? 'SELECTED="SELECTED"' : '').'>'.$dir.'</option>';
}
?>
                </select>
                <span class="help-block"><?php echo translate('SkinDescription'); ?></span>
              </div>
            </div>
            <div class="form-group">
              <label for="css" class="col-sm-3 control-label">CSS</label>
              <div class="col-sm-6">
                <select name="css" class="form-control chosen">
<?php
foreach ( array_map('basename', glob('skins/'.$skin.'/css/*',GLOB_ONLYDIR)) as $dir ) {
  echo '<option value="'.$dir.'" '.($css==$dir ? 'SELECTED="SELECTED"' : '').'>'.$dir.'</option>';
}
?>
                </select>
                <span class="help-block"><?php echo translate('CSSDescription'); ?></span>
              </div>
            </div>
            <div id="contentButtons">
              <button value="Save" type="submit"><?php echo translate('Save') ?></button>
            </div>
         </form>
<?php
} else if ( $tab == 'users' ) {
?>
      <form name="userForm" method="post" action="?">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <table id="contentTable" class="table table-striped">
          <thead class="thead-highlight">
            <tr>
              <th class="colUsername"><?php echo translate('Username') ?></th>
              <th class="colLanguage"><?php echo translate('Language') ?></th>
              <th class="colEnabled"><?php echo translate('Enabled') ?></th>
              <th class="colStream"><?php echo translate('Stream') ?></th>
              <th class="colEvents"><?php echo translate('Events') ?></th>
              <th class="colControl"><?php echo translate('Control') ?></th>
              <th class="colMonitors"><?php echo translate('Monitors') ?></th>
              <th class="colGroups"><?php echo translate('Groups') ?></th>
              <th class="colSystem"><?php echo translate('System') ?></th>
              <th class="colBandwidth"><?php echo translate('Bandwidth') ?></th>
              <th class="colMonitor"><?php echo translate('Monitor') ?></th>
              <?php if ( ZM_OPT_USE_API ) { ?><th class="colAPIEnabled"><?php echo translate('APIEnabled') ?></th><?php } ?>
              <th class="colMark"><?php echo translate('Mark') ?></th>
            </tr>
          </thead>
          <tbody>
<?php
    $sql = 'SELECT * FROM Monitors ORDER BY Sequence ASC';
    $monitors = array();
    foreach( dbFetchAll($sql) as $monitor ) {
      $monitors[$monitor['Id']] = $monitor;
    }

    $sql = 'SELECT * FROM Users ORDER BY Username';
    foreach( dbFetchAll($sql) as $row ) {
      $userMonitors = array();
      if ( !empty($row['MonitorIds']) ) {
        foreach ( explode(',', $row['MonitorIds']) as $monitorId ) {
          // A deleted monitor will cause an error since we don't update 
          // the user monitors list on monitor delete
          if ( ! isset($monitors[$monitorId]) ) continue;
          $userMonitors[] = $monitors[$monitorId]['Name'];
        }
      }
?>
            <tr>
              <td class="colUsername"><?php echo makePopupLink('?view=user&amp;uid='.$row['Id'], 'zmUser', 'user', validHtmlStr($row['Username']).($user['Username']==$row['Username']?"*":""), $canEdit) ?></td>
              <td class="colLanguage"><?php echo $row['Language']?validHtmlStr($row['Language']):'default' ?></td>
              <td class="colEnabled"><?php echo $row['Enabled']?translate('Yes'):translate('No') ?></td>
              <td class="colStream"><?php echo validHtmlStr($row['Stream']) ?></td>
              <td class="colEvents"><?php echo validHtmlStr($row['Events']) ?></td>
              <td class="colControl"><?php echo validHtmlStr($row['Control']) ?></td>
              <td class="colMonitors"><?php echo validHtmlStr($row['Monitors']) ?></td>
              <td class="colGroups"><?php echo validHtmlStr($row['Groups']) ?></td>
              <td class="colSystem"><?php echo validHtmlStr($row['System']) ?></td>
              <td class="colBandwidth"><?php echo $row['MaxBandwidth']?$bandwidth_options[$row['MaxBandwidth']]:'&nbsp;' ?></td>
              <td class="colMonitor"><?php echo $row['MonitorIds']?(join( ", ", $userMonitors )):"&nbsp;" ?></td>
              <?php if ( ZM_OPT_USE_API ) { ?><td class="colAPIEnabled"><?php echo $row['APIEnabled']?translate('Yes'):translate('No') ?></td><?php } ?>
              <td class="colMark"><input type="checkbox" name="markUids[]" value="<?php echo $row['Id'] ?>" data-on-click-this="configureDeleteButton"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
    }
?>
          </tbody>
        </table>
        <div id="contentButtons">
        <?php echo makePopupButton('?view=user&uid=0', 'zmUser', 'user', translate("AddNewUser"), canEdit('System')); ?>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </form>
<?php
} else if ( $tab == 'servers' ) { ?>
      <form name="serversForm" method="post" action="?">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <input type="hidden" name="object" value="server"/>
        <table id="contentTable" class="table table-striped">
          <thead class="thead-highlight">
            <tr>
              <th class="colName"><?php echo translate('Name') ?></th>
              <th class="colUrl"><?php echo translate('Url') ?></th>
              <th class="colPathToIndex"><?php echo translate('PathToIndex') ?></th>
              <th class="colPathToZMS"><?php echo translate('PathToZMS') ?></th>
              <th class="colPathToApi"><?php echo translate('PathToApi') ?></th>
              <th class="colStatus"><?php echo translate('Status') ?></th>
              <th class="colMonitorCount"><?php echo translate('Monitors') ?></th>
              <th class="colCpuLoad"><?php echo translate('CpuLoad') ?></th>
              <th class="colMemory"><?php echo translate('Free').'/'.translate('Total') . ' ' . translate('Memory') ?></th>
              <th class="colSwap"><?php echo translate('Free').'/'.translate('Total') . ' ' . translate('Swap') ?></th>
              <th class="colStats"><?php echo translate('RunStats') ?></th>
              <th class="colAudit"><?php echo translate('RunAudit') ?></th>
              <th class="colTrigger"><?php echo translate('RunTrigger') ?></th>
              <th class="colEventNotification"><?php echo translate('RunEventNotification') ?></th>
              <th class="colMark"><?php echo translate('Mark') ?></th>
			</tr>
          </thead>
          <tbody>
<?php
  $monitor_counts = dbFetchAssoc('SELECT Id,(SELECT COUNT(Id) FROM Monitors WHERE ServerId=Servers.Id) AS MonitorCount FROM Servers', 'Id', 'MonitorCount');
  foreach ( ZM\Server::find() as $Server ) {
?>
            <tr>
              <td class="colName"><?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', validHtmlStr($Server->Name()), $canEdit) ?></td>
              <td class="colUrl"><?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', validHtmlStr($Server->Url()), $canEdit) ?></td>
              <td class="colPathToIndex"><?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', validHtmlStr($Server->PathToIndex()), $canEdit) ?></td>
              <td class="colPathToZMS"><?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', validHtmlStr($Server->PathToZMS()), $canEdit) ?></td>
              <td class="colPathToApi"><?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', validHtmlStr($Server->PathToApi()), $canEdit) ?></td>
              <td class="colStatus <?php if ( $Server->Status() == 'NotRunning' ) { echo 'danger'; } ?>">
                  <?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', validHtmlStr($Server->Status()), $canEdit) ?></td>
              <td class="colMonitorCount">
                  <?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', validHtmlStr($monitor_counts[$Server->Id()]), $canEdit) ?>
              </td>
              <td class="colCpuLoad <?php if ( $Server->CpuLoad() > 5 ) { echo 'danger'; } ?>">
                  <?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server',$Server->CpuLoad(), $canEdit) ?>
              </td>
              <td class="colMemory <?php if ( (!$Server->TotalMem()) or ($Server->FreeMem()/$Server->TotalMem() < .1) ) { echo 'danger'; } ?>">
                  <?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', human_filesize($Server->FreeMem()) . ' / ' . human_filesize($Server->TotalMem()), $canEdit) ?>
              </td>
              <td class="colSwap <?php if ( (!$Server->TotalSwap()) or ($Server->FreeSwap()/$Server->TotalSwap() < .1) ) { echo 'danger'; } ?>">
                <?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', human_filesize($Server->FreeSwap()) . ' / ' . human_filesize($Server->TotalSwap()) , $canEdit) ?>
              </td>
              <td class="colStats"><?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', $Server->zmstats() ? 'yes' : 'no', $canEdit) ?></td>
              <td class="colAudit"><?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', $Server->zmaudit() ? 'yes' : 'no', $canEdit) ?></td>
              <td class="colTrigger"><?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', $Server->zmtrigger() ? 'yes' : 'no', $canEdit) ?></td>
              <td class="colEventNotification"><?php echo makePopupLink('?view=server&amp;id='.$Server->Id(), 'zmServer', 'server', $Server->zmeventnotification() ? 'yes' : 'no', $canEdit) ?></td>

              <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $Server->Id() ?>" data-on-click-this="configureDeleteButton"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
			</tr>
<?php } #end foreach Server ?>
          </tbody>
        </table>
        <div id="contentButtons">
        <?php echo makePopupButton('?view=server&id=0', 'zmServer', 'server', translate('AddNewServer'), canEdit('System')); ?>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </form>
<?php
} else if ( $tab == 'storage' ) { ?>
      <form name="storageForm" method="post" action="?">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <input type="hidden" name="object" value="storage"/>
        <table id="contentTable" class="table table-striped">
          <thead class="thead-highlight">
            <tr>
              <th class="colId"><?php echo translate('Id') ?></th>
              <th class="colName"><?php echo translate('Name') ?></th>
              <th class="colPath"><?php echo translate('Path') ?></th>
              <th class="colType"><?php echo translate('Type') ?></th>
              <th class="colScheme"><?php echo translate('StorageScheme') ?></th>
              <th class="colServer"><?php echo translate('Server') ?></th>
              <th class="colDiskSpace"><?php echo translate('DiskSpace') ?></th>
              <th class="colEvents"><?php echo translate('Events') ?></th>
              <th class="colMark"><?php echo translate('Mark') ?></th>
            </tr>
          </thead>
          <tbody>
<?php foreach( ZM\Storage::find( null, array('order'=>'lower(Name)') ) as $Storage ) { ?>
            <tr>
              <td class="colId"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Id()), $canEdit ) ?></td>
              <td class="colName"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Name()), $canEdit ) ?></td>
              <td class="colPath"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Path()), $canEdit ) ?></td>
              <td class="colType"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Type()), $canEdit ) ?></td>
              <td class="colScheme"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Scheme()), $canEdit ) ?></td>
              <td class="colServer"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Server()->Name()), $canEdit ) ?></td>
              <td class="colDiskSpace"><?php echo human_filesize($Storage->disk_used_space()) . ' of ' . human_filesize($Storage->disk_total_space()) ?></td>
              <td class="ColEvents"><?php echo $Storage->EventCount().' using '.human_filesize($Storage->event_disk_space()) ?></td>
              <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $Storage->Id() ?>" data-on-click-this="configureDeleteButton"<?php if ( $Storage->EventCount() or !$canEdit ) { ?> disabled="disabled"<?php } ?><?php echo $Storage->EventCount() ? ' title="Can\'t delete as long as there are events stored here."' : ''?>/></td>
            </tr>
<?php } #end foreach Server ?>
          </tbody>
        </table>
        <div id="contentButtons">
          <?php echo makePopupButton('?view=storage&id=0', 'zmStorage', 'storage', translate('AddNewStorage'), canEdit('System')); ?>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </form>

  <?php
  } else if ($tab == 'API') {
  
    $apiEnabled = dbFetchOne("SELECT Value FROM Config WHERE Name='ZM_OPT_USE_API'");
    if ($apiEnabled['Value']!='1') {
      echo "<div class='errorText'>APIs are disabled. To enable, please turn on OPT_USE_API in Options->System</div>";
    }
    else {
  ?>

    <form name="userForm" method="post" action="?">
      <button class="pull-left" type="submit" name="updateSelected" id="updateSelected"><?php echo translate('Update')?></button>
      <button class="btn-danger pull-right" type="submit" name="revokeAllTokens" id="revokeAllTokens"><?php echo translate('RevokeAllTokens')?></button>
      <br/>
      <?php
      function revokeAllTokens() {
        $minTokenTime = time();
        dbQuery('UPDATE `Users` SET `TokenMinExpiry`=?', array($minTokenTime));
        echo '<span class="timedSuccessBox">'.translate('AllTokensRevoked').'</span>';
      }

      function updateSelected() {
        # Turn them all off, then selectively turn the checked ones back on
        dbQuery('UPDATE `Users` SET `APIEnabled`=0');

        if ( isset($_REQUEST['tokenUids']) ) {
          foreach ( $_REQUEST['tokenUids'] as $markUid ) {
            $minTime = time();
            dbQuery('UPDATE `Users` SET `TokenMinExpiry`=? WHERE `Id`=?', array($minTime, $markUid));
          }
        }
        if ( isset($_REQUEST['apiUids']) ) {
          foreach ( $_REQUEST['apiUids'] as $markUid ) {
            dbQuery('UPDATE `Users` SET `APIEnabled`=1 WHERE `Id`=?', array($markUid));
          }
        }
        echo '<span class="timedSuccessBox">'.translate('Updated').'</span>';
      }

      if ( array_key_exists('revokeAllTokens', $_POST) ) {
        revokeAllTokens();
      }

      if ( array_key_exists('updateSelected', $_POST) ) {
        updateSelected();
      }
    ?>
      <br/><br/>
      <input type="hidden" name="view" value="<?php echo $view ?>"/>
      <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
      <input type="hidden" name="action" value="delete"/>
      <table id="contentTable" class="table table-striped">
        <thead class="thead-highlight">
          <tr>
            <th class="colUsername"><?php echo translate('Username') ?></th>
            <th class="colMark"><?php echo translate('Revoke Token') ?></th>
            <th class="colMark"><?php echo translate('API Enabled') ?></th>
          </tr>
        </thead>
        <tbody>
        <?php
          
            $sql = 'SELECT * FROM Users ORDER BY Username';
            foreach ( dbFetchAll($sql) as $row ) {
        ?>
                <tr>
                  <td class="colUsername"><?php echo validHtmlStr($row['Username']) ?></td>
                  <td class="colMark"><input type="checkbox" name="tokenUids[]" value="<?php echo $row['Id'] ?>" /></td>
                  <td class="colMark"><input type="checkbox" name="apiUids[]" value="<?php echo $row['Id']?>"  <?php echo $row['APIEnabled']?'checked':''?> /></td>
                </tr>
    <?php
        }
    ?>
          </tbody>
        </table>
      </form>


<?php 
    } // API enabled
  }  // $tab == API
  else { 
    $config = array();
    $configCat = array();
    $configCats = array();

    $result = $dbConn->query('SELECT * FROM `Config` ORDER BY `Id` ASC');
    if ( !$result )
      echo mysql_error();
    while( $row = dbFetchNext($result) ) {
      $config[$row['Name']] = $row;
      if ( !($configCat = &$configCats[$row['Category']]) ) {
        $configCats[$row['Category']] = array();
        $configCat = &$configCats[$row['Category']];
      }
      $configCat[$row['Name']] = $row;
    }

    if ( $tab == 'system' ) {
        $configCats[$tab]['ZM_LANG_DEFAULT']['Hint'] = join('|', getLanguages());
        $configCats[$tab]['ZM_SKIN_DEFAULT']['Hint'] = join('|', array_map('basename', glob('skins/*',GLOB_ONLYDIR)));
        $configCats[$tab]['ZM_CSS_DEFAULT']['Hint'] = join('|', array_map ( 'basename', glob('skins/'.ZM_SKIN_DEFAULT.'/css/*',GLOB_ONLYDIR) ));
        $configCats[$tab]['ZM_BANDWIDTH_DEFAULT']['Hint'] = $bandwidth_options;

        function timezone_list() {
          static $timezones = null;

          if ( $timezones === null ) {
            $timezones = [];
            $offsets = [];
            $now = new DateTime('now', new DateTimeZone('UTC'));

            foreach ( DateTimeZone::listIdentifiers() as $timezone ) {
              $now->setTimezone(new DateTimeZone($timezone));
              $offsets[] = $offset = $now->getOffset();
              $timezones[$timezone] = '(' . format_GMT_offset($offset) . ') ' . format_timezone_name($timezone);
            }

            array_multisort($offsets, $timezones);
          }

          return $timezones;
        }

        function format_GMT_offset($offset) {
          $hours = intval($offset / 3600);
          $minutes = abs(intval($offset % 3600 / 60));
          return 'GMT' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
        }

        function format_timezone_name($name) {
          $name = str_replace('/', ', ', $name);
          $name = str_replace('_', ' ', $name);
          $name = str_replace('St ', 'St. ', $name);
          return $name;
        }
        $configCats[$tab]['ZM_TIMEZONE']['Hint'] = array(''=> translate('TZUnset')) + timezone_list();
    } # end if tab == system
?>
      <form name="optionsForm" class="form-horizontal" method="post" action="?">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="options"/>
<?php
        $configCat = $configCats[$tab];
        foreach ( $configCat as $name=>$value ) {
          $shortName = preg_replace( '/^ZM_/', '', $name );
          $optionPromptText = !empty($OLANG[$shortName])?$OLANG[$shortName]['Prompt']:$value['Prompt'];
?>
            <div class="form-group">
              <label for="<?php echo $name ?>" class="col-sm-3 control-label"><?php echo $shortName ?></label>
              <div class="col-sm-6">
<?php   
        if ( $value['Type'] == 'boolean' ) {
?>
              <input type="checkbox" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="1"<?php if ( $value['Value'] ) { ?> checked="checked"<?php } ?><?php echo $canEdit?'':' disabled="disabled"' ?>/>
<?php
        } elseif ( is_array($value['Hint']) ) {
          echo htmlSelect("newConfig[$name]", $value['Hint'], $value['Value']);
        } elseif ( preg_match('/\|/', $value['Hint']) ) {
?>

<?php
            $options = explode('|', $value['Hint']);
            if ( count($options) > 3 ) {
?>
                <select class="form-control" name="newConfig[<?php echo $name ?>]"<?php echo $canEdit?'':' disabled="disabled"' ?>>
<?php
                foreach ( $options as $option ) {
                  if ( preg_match('/^([^=]+)=(.+)$/', $option, $matches) ) {
                    $optionLabel = $matches[1];
                    $optionValue = $matches[2];
                  } else {
                    $optionLabel = $optionValue = $option;
                  }
?>
                  <option value="<?php echo $optionValue ?>"<?php if ( $value['Value'] == $optionValue ) { echo ' selected="selected"'; } ?>><?php echo htmlspecialchars($optionLabel) ?></option>
<?php
                }
?>
                </select>
<?php
            } else {
                foreach ( $options as $option ) {
                  if ( preg_match('/^([^=]+)=(.+)$/', $option) ) {
                    $optionLabel = $matches[1];
                    $optionValue = $matches[2];
                  } else {
                    $optionLabel = $optionValue = $option;
                  }
?>
                <label>
                  <input type="radio" id="<?php echo $name.'_'.preg_replace('/[^a-zA-Z0-9]/', '', $optionValue) ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo $optionValue ?>"<?php if ( $value['Value'] == $optionValue ) { ?> checked="checked"<?php } ?><?php echo $canEdit?'':' disabled="disabled"' ?>/>
                  <?php echo htmlspecialchars($optionLabel) ?>
                </label>
<?php
                }
            }
?>
<?php
        } else if ( $value['Type'] == 'text' ) {
?>
              <textarea class="form-control" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" rows="5" cols="40"<?php echo $canEdit?'':' disabled="disabled"' ?>><?php echo validHtmlStr($value['Value']) ?></textarea>
<?php
        } else if ( $value['Type'] == 'integer' ) {
?>
              <input type="number" class="form-control small" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" <?php echo $canEdit?'':' disabled="disabled"' ?>/>
<?php
        } else if ( $value['Type'] == 'hexadecimal' ) {
?>
              <input type="text" class="form-control medium" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" <?php echo $canEdit?'':' disabled="disabled"' ?>/>
<?php
        } else if ( $value['Type'] == 'decimal' ) {
?>
              <input type="text" class="form-control small" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" <?php echo $canEdit?'':' disabled="disabled"' ?>/>
<?php
        } else {
?>
              <input type="text" class="form-control large" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" <?php echo $canEdit?'':' disabled="disabled"' ?>/>
<?php
        }
?>
              <span class="help-block"><?php echo validHtmlStr($optionPromptText) ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option='.$name, 'zmOptionHelp', 'optionhelp', '?' ) ?>)</span>
	    </div><!-- End .col-sm-9 -->
            </div><!-- End .form-group -->
<?php
    }
?>

        <div id="contentButtons">
          <button type="submit" <?php echo $canEdit?'':' disabled="disabled"' ?>><?php echo translate('Save') ?></button>
        </div>
      </form>
<?php
}
?>



    </div><!-- end #options -->
	</div>
</div> <!-- end row -->
    </div>
<?php xhtmlFooter() ?>
