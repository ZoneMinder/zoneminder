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
if (!defined('ZM_FORCE_CSS_DEFAULT') or !defined('ZM_FORCE_SKIN_DEFAULT'))
$tabs['skins'] = translate('Display');
$tabs['system'] = translate('System');
$tabs['auth'] = translate('Authentication');
$tabs['config'] = translate('Config');
if (defined('ZM_PATH_DNSMASQ_CONF') and ZM_PATH_DNSMASQ_CONF) {
  $tabs['dnsmasq'] = translate('DHCP');
}
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
      <div id="optionsContainer" class="col">
<?php 
if ($tab == 'skins') {
?>
          <form name="optionsForm" method="get" action="?">
            <input type="hidden" name="view" value="<?php echo $view ?>"/>
            <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
            <div class="row">
              <div class="col">
                <div id="contentButtons">
                  <button value="Save" type="submit"><?php echo translate('Save') ?></button>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <label for="skin" class="col-sm-3 col-form-label"><?php echo translate('Skin')?></label>
              <div class="col-sm-6">
                <select id="skin" name="skin" class="form-control chosen">
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
                <select id="css" name="css" class="form-control chosen">
<?php
foreach (array_map('basename', glob('skins/'.$skin.'/css/*', GLOB_ONLYDIR)) as $dir) {
  echo '<option value="'.$dir.'" '.($css==$dir ? 'SELECTED="SELECTED"' : '').'>'.$dir.'</option>';
}
?>
                </select>
                <span class="form-text"><?php echo translate('CSSDescription'); ?></span>
              </div>
            </div>
          </form>
<?php
} else if ($tab == 'control') {
  if (canView('Control')) {
    include('_options_controlcaps.php');
  } else {
    $redirect = '?view=error';
    // Have to do this 
    header('Location: '.$redirect);
  }
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
  include('_options_servers.php');
} else if ($tab == 'storage') { 
  include('_options_storage.php');
} else if ($tab == 'dnsmasq' and file_exists('skins/classic/views/_options_dnsmasq.php')) {
  include('_options_dnsmasq.php');
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
//    $configCats[$tab]['ZM_LANG_DEFAULT']['Hint'] = join('|', getLanguages());
    if (defined('ZM_FORCE_SKIN_DEFAULT'))
      $configCats[$tab]['ZM_SKIN_DEFAULT']['Hint'] = ZM_FORCE_SKIN_DEFAULT;
    else
      $configCats[$tab]['ZM_SKIN_DEFAULT']['Hint'] = join('|', array_map('basename', glob('skins/*',GLOB_ONLYDIR)));
    if (defined('ZM_FORCE_CSS_DEFAULT'))
      $configCats[$tab]['ZM_CSS_DEFAULT']['Hint'] = ZM_FORCE_CSS_DEFAULT;
    else
      $configCats[$tab]['ZM_CSS_DEFAULT']['Hint'] = join('|', array_map ( 'basename', glob('skins/'.ZM_SKIN_DEFAULT.'/css/*',GLOB_ONLYDIR) ));
    $configCats[$tab]['ZM_BANDWIDTH_DEFAULT']['Hint'] = $bandwidth_options;

// create new multidim array for languages (code1|translation)
    $languagecodess=join('|', getLanguages());
    $languagecodelist=explode('|',$languagecodess);
    $languageslist = array();
    foreach ($languagecodelist as $language){
        $languageslist[$language] = translate($language);
       }

    $configCats[$tab]['ZM_LANG_DEFAULT']['Hint'] = $languageslist;


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
          <div class="row pb-2">
            <div class="col">
              <div id="contentButtons">
                <button type="submit" <?php echo $canEdit?'':' disabled="disabled"' ?>><?php echo translate('Save') ?></button>
              </div>
            </div>
          </div>
        <div class="row h-100">
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
                $attributes = ['id'=>$name, 'class'=>'form-control-sm chosen'];
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
                  $attributes = ['id'=>$name, 'class'=>'form-control-sm chosen'];
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
            </div><!-- End .col-md -->
          </div><!-- End .form-group -->
<?php
            } # end foreach config entry in the category
        } # end if category exists
?>
          </div><!--options-->
        </div><!-- .row h-100 -->
      </form>
<?php
}
?>
      </div><!-- end #optionsContainer -->
  </div> <!-- end row -->
</div>
<?php xhtmlFooter() ?>
