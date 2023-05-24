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

if (!canView('System')) {
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
$tabs['groups'] = translate('Groups');
$tabs['control'] = translate('Control');
$tabs['privacy'] = translate('Privacy');
$tabs['MQTT'] = translate('MQTT');
$tabs['telemetry'] = translate('Telemetry');
$tabs['version'] = translate('Versions');
if (file_exists('skins/classic/views/_options_dnsmasq.php')) {
  $tabs['dnsmasq'] = translate('DHCP');
}

$tab = isset($_REQUEST['tab']) ? validHtmlStr($_REQUEST['tab']) : 'system';

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Options'));
getBodyTopHTML();
echo getNavBarHTML();
?>
  <div class="container-fluid" id="content">
    <div class="row flex-nowrap h-100">
      <nav id="sidebar">
        <ul class="nav nav-pills flex-column">
<?php
foreach ($tabs as $name=>$value) {
  echo '<li class="nav-item form-control-sm mb-2 '.$name.'"><a class="nav-link'.($tab == $name ? ' active' : '').'" href="?view='.$view.'&amp;tab='.$name.'">'.$value.'</a></li>'.PHP_EOL;
}
?>
        </ul>
      </nav>
      <div class="container-fluid col-sm-offset-2 h-100 pr-0">
<?php 
if ($tab == 'skins') {
?>
          <form name="optionsForm" method="get" action="?">
            <input type="hidden" name="view" value="<?php echo $view ?>"/>
            <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
            <div class="form-group row">
              <label for="skin" class="col-sm-3 col-form-label"><?php echo translate('Skin')?></label>
              <div class="col-sm-6">
                <select name="skin" class="form-control chosen">
<?php
# Have to do this stuff up here before including header.php because of the cookie setting
$skin_options = array_map('basename', glob('skins/*', GLOB_ONLYDIR));
foreach ($skin_options as $dir) {
  echo '<option value="'.$dir.'" '.($skin==$dir ? 'SELECTED="SELECTED"' : '').'>'.$dir.'</option>';
}
?>
                </select>
                <span class="form-text"><?php echo translate('SkinDescription'); ?></span>
              </div>
            </div>
            <div class="form-group row">
              <label for="css" class="col-sm-3 col-form-label">CSS</label>
              <div class="col-sm-6">
                <select name="css" class="form-control chosen">
<?php
foreach (array_map('basename', glob('skins/'.$skin.'/css/*', GLOB_ONLYDIR)) as $dir) {
  echo '<option value="'.$dir.'" '.($css==$dir ? 'SELECTED="SELECTED"' : '').'>'.$dir.'</option>';
}
?>
                </select>
                <span class="form-text"><?php echo translate('CSSDescription'); ?></span>
              </div>
            </div>
            <div id="contentButtons">
              <button value="Save" type="submit"><?php echo translate('Save') ?></button>
            </div>
          </form>
<?php
} else if ($tab == 'control') {
  if (canView('Control')) {
    $redirect = '?view=controlcaps';
  } else {
    $redirect = '?view=error';
  }
  // Have to do this 
  header('Location: '.$redirect);
} else if ($tab == 'privacy') {
  if (canView('System')) {
    $redirect = '?view=privacy';
  } else {
    $redirect = '?view=error';
  }
  // Have to do this 
  header('Location: '.$redirect);
} else if ($tab == 'groups') {
  if (canView('Groups')) {
    $redirect = '?view=groups';
  } else {
    $redirect = '?view=error';
  }
  // Have to do this 
  header('Location: '.$redirect);
} else if ($tab == 'servers') {
?>
          <form name="serversForm" method="post" action="?">
            <input type="hidden" name="view" value="<?php echo $view ?>"/>
            <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="object" value="server"/>
            <div id="contentButtons">
              <button type="button" id="NewServerBtn" value="<?php echo translate('AddNewServer') ?>" disabled="disabled"><?php echo translate('AddNewServer') ?></button>
              <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
            </div>
            <table id="contentTable" class="table table-striped">
              <thead class="thead-highlight">
                <tr>
                  <th class="colMark"><?php echo translate('Mark') ?></th>
                  <th class="colId"><?php echo translate('Id') ?></th>
                  <th class="colName"><?php echo translate('Name') ?></th>
                  <th class="colUrl"><?php echo translate('Url') ?></th>
                  <th class="colPathToIndex"><?php echo translate('PathToIndex') ?></th>
                  <th class="colPathToZMS"><?php echo translate('PathToZMS') ?></th>
                  <th class="colPathToAPI"><?php echo translate('PathToApi') ?></th>
                  <th class="colStatus"><?php echo translate('Status') ?></th>
                  <th class="colMonitorCount"><?php echo translate('Monitors') ?></th>
                  <th class="colCpuLoad"><?php echo translate('CpuLoad') ?></th>
                  <th class="colMemory"><?php echo translate('Free').'/'.translate('Total') . ' ' . translate('Memory') ?></th>
                  <th class="colSwap"><?php echo translate('Free').'/'.translate('Total') . ' ' . translate('Swap') ?></th>
                  <th class="colStats"><?php echo translate('RunStats') ?></th>
                  <th class="colAudit"><?php echo translate('RunAudit') ?></th>
                  <th class="colTrigger"><?php echo translate('RunTrigger') ?></th>
                  <th class="colEventNotification"><?php echo translate('RunEventNotification') ?></th>
                </tr>
              </thead>
              <tbody>
<?php
          $monitor_counts = dbFetchAssoc('SELECT Id,(SELECT COUNT(Id) FROM Monitors WHERE Deleted!=1 AND ServerId=Servers.Id) AS MonitorCount FROM Servers', 'Id', 'MonitorCount');
          foreach (ZM\Server::find() as $Server) {
            $svr_opt = 'class="serverCol" data-sid="'.$Server->Id().'"';
            ?>
                <tr>
                  <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $Server->Id() ?>" data-on-click-this="configureDeleteButton"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
                  <td class="colId"><?php echo makeLink('#', $Server->Id(), $canEdit, $svr_opt ) ?></td>
                  <td class="colName"><?php echo makeLink('#', validHtmlStr($Server->Name()), $canEdit, $svr_opt ) ?></td>
                  <td class="colUrl"><?php echo makeLink('#', validHtmlStr($Server->Url()), $canEdit, $svr_opt ) ?></td>
                  <td class="colPathToIndex"><?php echo makeLink('#', validHtmlStr($Server->PathToIndex()), $canEdit, $svr_opt ) ?></td>
                  <td class="colPathToZMS"><?php echo makeLink('#', validHtmlStr($Server->PathToZMS()), $canEdit, $svr_opt ) ?></td>
                  <td class="colPathToAPI"><?php echo makeLink('#', validHtmlStr($Server->PathToAPI()), $canEdit, $svr_opt ) ?></td>
                  <td class="colStatus <?php if ( $Server->Status() == 'NotRunning' ) { echo 'danger'; } ?>">
                      <?php echo makeLink('#', validHtmlStr($Server->Status()), $canEdit, $svr_opt) ?></td>
                  <td class="colMonitorCount"><?php echo makeLink('#', validHtmlStr($monitor_counts[$Server->Id()]), $canEdit, $svr_opt) ?></td>
                  <td class="colCpuLoad <?php if ( $Server->CpuLoad() > 5 ) { echo 'danger'; } ?>"><?php echo makeLink('#', $Server->CpuLoad(), $canEdit, $svr_opt) ?></td>
                  <td class="colMemory <?php if ( (!$Server->TotalMem()) or ($Server->FreeMem()/$Server->TotalMem() < .1) ) { echo 'danger'; } ?>">
                      <?php echo makeLink('#', human_filesize($Server->FreeMem()) . ' / ' . human_filesize($Server->TotalMem()), $canEdit, $svr_opt) ?></td>
                  <td class="colSwap <?php if ( (!$Server->TotalSwap()) or ($Server->FreeSwap()/$Server->TotalSwap() < .1) ) { echo 'danger'; } ?>">
                      <?php echo makeLink('#', human_filesize($Server->FreeSwap()) . ' / ' . human_filesize($Server->TotalSwap()) , $canEdit, $svr_opt) ?></td>
                  <td class="colStats"><?php echo makeLink('#', $Server->zmstats() ? 'yes' : 'no', $canEdit, $svr_opt) ?></td>
                  <td class="colAudit"><?php echo makeLink('#', $Server->zmaudit() ? 'yes' : 'no', $canEdit, $svr_opt) ?></td>
                  <td class="colTrigger"><?php echo makeLink('#', $Server->zmtrigger() ? 'yes' : 'no', $canEdit, $svr_opt) ?></td>
                  <td class="colEventNotification"><?php echo makeLink('#', $Server->zmeventnotification() ? 'yes' : 'no', $canEdit, $svr_opt) ?></td>
                </tr>
<?php } #end foreach Server ?>
              </tbody>
            </table>
          </form>
<?php
} else if ($tab == 'storage') { 
?>
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
<?php
  foreach (ZM\Storage::find(null, array('order'=>'lower(Name)')) as $Storage) { 
    $filter = new ZM\Filter();
    $filter->addTerm(array('attr'=>'StorageId','op'=>'=','val'=>$Storage->Id()));
    if (count($user->unviewableMonitorIds())) {
      $filter = $filter->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'IN', 'val'=>$user->viewableMonitorIds()));
    }

    $str_opt = 'class="storageCol" data-sid="'.$Storage->Id().'"';
  ?>
                <tr>
                  <td class="colId"><?php echo makeLink('#', validHtmlStr($Storage->Id()), $canEdit, $str_opt) ?></td>
                  <td class="colName"><?php echo makeLink('#', validHtmlStr($Storage->Name()), $canEdit, $str_opt) ?></td>
                  <td class="colPath"><?php echo makeLink('#', validHtmlStr($Storage->Path()), $canEdit, $str_opt) ?></td>
                  <td class="colType"><?php echo makeLink('#', validHtmlStr($Storage->Type()), $canEdit, $str_opt) ?></td>
                  <td class="colScheme"><?php echo makeLink('#', validHtmlStr($Storage->Scheme()), $canEdit, $str_opt) ?></td>
                  <td class="colServer"><?php echo makeLink('#', validHtmlStr($Storage->Server()->Name()), $canEdit, $str_opt) ?></td>
                  <td class="colDiskSpace"><?php
    if ($Storage->disk_total_space()) {
      echo intval(100*$Storage->disk_used_space()/$Storage->disk_total_space()).'% ';
    }
    echo human_filesize($Storage->disk_used_space()) . ' of ' . human_filesize($Storage->disk_total_space()) ?></td>
                  <td class="ColEvents"><?php echo makeLink('?view=events'.$filter->querystring(), $Storage->EventCount().' using '.human_filesize($Storage->event_disk_space() ? $Storage->event_disk_space() : 0) ); ?></td>
                  <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $Storage->Id() ?>" data-on-click-this="configureDeleteButton"
<?php
    echo ($Storage->EventCount() or !$canEdit) ? ' disabled="disabled"' : '';
    echo $Storage->EventCount() ? ' title="Can\'t delete as long as there are events stored here."' : '';
?>
                  /></td>
                </tr>
<?php
  } #end foreach Server
?>
              </tbody>
            </table>
            <div id="contentButtons">
              <button type="button" id="NewStorageBtn" value="<?php echo translate('AddNewStorage') ?>" disabled="disabled"><?php echo translate('AddNewStorage') ?></button>
              <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
            </div>
          </form>
<?php
} else if ($tab == 'dnsmasq' and file_exists('skins/classic/views/_options_dnsmasq')) {
  include('_options_users.php');
} else if ($tab == 'users') {
  include('_options_users.php');
} else if ($tab == 'API') {
  include('_options_api.php');
}  // $tab == API
  else { 
  $config = array();
  $configCats = array();

  $result = $dbConn->query('SELECT * FROM `Config` ORDER BY `Id` ASC');
  if (!$result) {
    echo mysql_error();
  } else {
    while ($row = dbFetchNext($result)) {
      $config[$row['Name']] = $row;
      if ( !($configCat = &$configCats[$row['Category']]) ) {
        $configCats[$row['Category']] = array();
      }
      $configCats[$row['Category']][$row['Name']] = &$config[$row['Name']];
    }
  }

  if ($tab == 'web') {
    $configCats[$tab]['ZM_WEB_HOMEVIEW']['Hint'] = [
      'console'=>translate('Console'),
      'events'=>'Events',
      'map'   =>  'Map',
      'montage'=>'Montage',
      'montagereview'=>'Montage Review',
      'watch' => 'Watch',
    ];
  } else if ($tab == 'system') {
    $configCats[$tab]['ZM_LANG_DEFAULT']['Hint'] = join('|', getLanguages());
    $configCats[$tab]['ZM_SKIN_DEFAULT']['Hint'] = join('|', array_map('basename', glob('skins/*',GLOB_ONLYDIR)));
    $configCats[$tab]['ZM_CSS_DEFAULT']['Hint'] = join('|', array_map ( 'basename', glob('skins/'.ZM_SKIN_DEFAULT.'/css/*',GLOB_ONLYDIR) ));
    $configCats[$tab]['ZM_BANDWIDTH_DEFAULT']['Hint'] = $bandwidth_options;

    function timezone_list() {
      static $timezones = null;

      if ($timezones === null) {
        $timezones = [];
        $offsets = [];
        $now = new DateTime('now', new DateTimeZone('UTC'));

        foreach (DateTimeZone::listIdentifiers() as $timezone) {
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

    $configCats[$tab]['ZM_LOCALE_DEFAULT']['Hint'] = array(''=> translate('System Default'));
    $locales = ResourceBundle::getLocales('');
    if ($locales) {
      foreach ( $locales as $locale) {
        $configCats[$tab]['ZM_LOCALE_DEFAULT']['Hint'][$locale] = $locale;
      }
    }
  } # end if tab == system
?>
      <form name="optionsForm" method="post" action="?">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="options"/>
        <div id="contentButtons">
          <button type="submit" <?php echo $canEdit?'':' disabled="disabled"' ?>><?php echo translate('Save') ?></button>
        </div>
        <div id="options">
<?php
        if (!isset($configCats[$tab])) {
          echo 'There are no config entries for category '.$tab.'.<br/>';
        } else {
          foreach ($configCats[$tab] as $name=>$value) {
            $shortName = preg_replace( '/^ZM_/', '', $name );
            $optionPromptText = !empty($OLANG[$shortName])?$OLANG[$shortName]['Prompt']:$value['Prompt'];
            $optionCanEdit = $canEdit && !$value['System'];
?>
        <div class="form-group form-row <?php echo $name ?>">
          <label for="<?php echo $name ?>" class="col-md-4 control-label text-md-right"><?php echo $shortName ?></label>
          <div class="col-md">
<?php   
            if ($value['Type'] == 'boolean') {
              echo '<input type="checkbox" id="'.$name.'" name="newConfig['.$name.']" value="1"'.
              ( $value['Value'] ? ' checked="checked"' : '').
              ( $optionCanEdit ? '' : ' disabled="disabled"').' />'.PHP_EOL;
            } else if (is_array($value['Hint'])) {
              $attributes = ['class'=>'form-control-sm'.(count($value['Hint'])>10?' chosen':'')];
              if (!$optionCanEdit) $attributes['disabled']='disabled';
              echo htmlSelect("newConfig[$name]", $value['Hint'], $value['Value'], $attributes);
            } else if (preg_match('/\|/', $value['Hint'])) {
              $options = explode('|', $value['Hint']);
              if (count($options) > 3) {
                $html_options = array();
                foreach ($options as $option) {
                  if (preg_match('/^([^=]+)=(.+)$/', $option, $matches)) {
                    $html_options[$matches[2]] = $matches[1];
                  } else {
                    $html_options[$option] = $option;
                  }
                }
                $attributes = ['class'=>'form-control-sm'.(count($html_options)>10?' chosen':'')];
                if (!$optionCanEdit) $attributes['disabled']='disabled';
                echo htmlSelect("newConfig[$name]", $html_options, $value['Value'], $attributes);
              } else {
                foreach ($options as $option) {
                  if (preg_match('/^([^=]+)=(.+)$/', $option)) {
                    $optionLabel = $matches[1];
                    $optionValue = $matches[2];
                  } else {
                    $optionLabel = $optionValue = $option;
                  }
?>
                <label class="font-weight-bold form-control-sm">
                  <input type="radio" id="<?php echo $name.'_'.preg_replace('/[^a-zA-Z0-9]/', '', $optionValue) ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo $optionValue ?>"<?php if ( $value['Value'] == $optionValue ) { ?> checked="checked"<?php } ?><?php echo $optionCanEdit?'':' disabled="disabled"' ?>/>
                  <?php echo htmlspecialchars($optionLabel) ?>
                </label>
<?php
                } # end foreach option
              } # end if count options > 3
            } else if ( $value['Type'] == 'text' ) {
              echo '<textarea class="form-control-sm" id="'.$name.'" name="newConfig['.$name.']" rows="5" cols="40"'.($optionCanEdit?'':' disabled="disabled"').'>'.validHtmlStr($value['Value']).'</textarea>'.PHP_EOL;
            } else if ( $value['Type'] == 'integer' ) {
              echo '<input type="number" class="form-control-sm" id="'.$name.'" name="newConfig['.$name.']" value="'.validHtmlStr($value['Value']).'" '.($optionCanEdit?'':' disabled="disabled"' ).' step="1"/>'.PHP_EOL;
            } else if ( $value['Type'] == 'hexadecimal' ) {
              echo '<input type="text" class="form-control-sm" id="'.$name.'" name="newConfig['.$name.']" value="'.validHtmlStr($value['Value']).'" '.($optionCanEdit?'':' disabled="disabled"' ).'/>'.PHP_EOL;
            } else if ( $value['Type'] == 'decimal' ) {
              echo '<input type="text" class="form-control-sm" id="'.$name.'" name="newConfig['.$name.']" value="'.validHtmlStr($value['Value']).'" '.($optionCanEdit?'':' disabled="disabled"' ).'/>'.PHP_EOL;
            } else if ( $value['Type'] == 'password' ) {
              echo '<input type="password" class="form-control-sm" id="'.$name.'" name="newConfig['.$name.']" value="'.validHtmlStr($value['Value']).'" '.($optionCanEdit?'':' disabled="disabled"' ).'/>'.PHP_EOL;
              echo '<span class="material-icons md-18" data-on-click-this="toggle_password_visibility" data-password-input="'.$name.'">visibility</span>';
            } else {
              echo '<input type="text" class="form-control-sm" id="'.$name.'" name="newConfig['.$name.']" value="'.validHtmlStr($value['Value']).'" '.($optionCanEdit?'':' disabled="disabled"' ).'/>'.PHP_EOL;
            }
            if ($value['Value'] != constant($name)) {
              echo '<p class="warning">Note: This value has been overriden via configuration files in '.ZM_CONFIG. ' or ' . ZM_CONFIG_SUBDIR.'.<br/>The overriden value is: '.constant($name).'</p>'.PHP_EOL;
            }
?>
            <span class="form-text form-control-sm"><?php echo validHtmlStr($optionPromptText); echo makeHelpLink($name) ?></span>
	        </div><!-- End .col-sm-9 -->
        </div><!-- End .form-group -->
<?php
          } # end foreach config entry in the category
      } # end if category exists
?>
        </div><!--options-->        
      </form>
<?php
}
?>
      </div><!-- end #options -->
  </div> <!-- end row -->
</div>
<?php xhtmlFooter() ?>
