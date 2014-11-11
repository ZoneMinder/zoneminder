<?php
//
// ZoneMinder web zone view file, $Date$, $Revision$
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//


if ( !canView( 'Monitors' ) )
{
    $view = "error";
    return;
}

$mid = validInt($_REQUEST['mid']);
$zid = !empty($_REQUEST['zid'])?validInt($_REQUEST['zid']):0;


if ( $zid > 0 ) {
   $newZone = dbFetchOne( 'SELECT * FROM Zones WHERE MonitorId = ? AND Id = ?', NULL, array( $mid, $zid) );
} else {
   $view = "error";
   return;
}
$monitor = dbFetchMonitor ( $mid );
$plugin = $_REQUEST['pl'];

$plugin_path = dirname($_SERVER['SCRIPT_FILENAME'])."/plugins/".$plugin;

$focusWindow = true;

$generalOptions=array(
   'Enabled'=>array(
      'Type'=>'select',
      'Name'=>'Enabled',
      'Choices'=>'Yes,No',
      'Value'=>'No'
   ),
   'RequireNatDet'=>array(
      'Type'=>'select',
      'Name'=>'RequireNatDet',
      'Choices'=>'Yes,No',
      'Value'=>'No',
      'Require'=>array(
         array(
            'Name'=>'Enabled',
            'Value'=>'Yes'
         )
      )
   ),
   'IncludeNatDet'=>array(
      'Type'=>'select',
      'Name'=>'IncludeNatDet',
      'Choices'=>'Yes,No',
      'Value'=>'No',
      'Require'=>array(
         array(
            'Name'=>'Enabled',
            'Value'=>'Yes'
         ),
         array(
            'Name'=>'RequireNatDet',
            'Value'=>'Yes'
         )
      )
   ),
   'ReInitNatDet'=>array(
      'Type'=>'select',
      'Name'=>'ReInitNatDet',
      'Choices'=>'Yes,No',
      'Value'=>'No',
      'Require'=>array(
         array(
            'Name'=>'Enabled',
            'Value'=>'Yes'
         ),
         array(
            'Name'=>'RequireNatDet',
            'Value'=>'Yes'
         )
      )
   ),
   'AlarmeScore'=>array(
      'Type'=>'integer',
      'Name'=>'AlarmeScore',
      'Min'=>'1',
      'Max'=>'100',
      'Value'=>'99',
      'Require'=>array(
         array(
            'Name'=>'Enabled',
            'Value'=>'Yes'
         )
      )
   )
);

$options=$generalOptions;
$optionNames=array();
if(file_exists($plugin_path."/config.php"))
{
   include_once($plugin_path."/config.php");
   if(isset($pluginOptions))
      foreach( $pluginOptions as $optionKey => $optionValue )
      {
         // Set default dependency information if not set in configuration file
         if(!isset($optionValue['Require']))
            $optionValue['Require'] = array (
               array(
                  'Name'=>'Enabled',
                  'Value'=>'Yes'
               )
            );
         $options[$optionKey]=$optionValue;
      }
}

$sql='SELECT * FROM PluginsConfig WHERE MonitorId=? AND ZoneId=? AND pluginName=?';
foreach( dbFetchAll( $sql, NULL, array( $mid, $zid, $plugin ) ) as $popt )
{
   if(array_key_exists($popt['Name'], $options)
      && $popt['Type']==$options[$popt['Name']]['Type'])
   {
      array_push($optionNames, $popt['Name']);

      // Backup dependency information
      $require = '';
      if(isset($options[$popt['Name']]['Require']))
         $require = $options[$popt['Name']]['Require'];

      // Set value from database
      $options[$popt['Name']]=$popt;

      // Restore dependancy information from backup
      if(!empty($require))
         $options[$popt['Name']]['Require'] = $require;

      // Set default dependancy information if not set in configuration
      else if($popt['Name'] != 'Enabled')
         $options[$popt['Name']]['Require'] = array (
            array(
               'Name'=>'Enabled',
               'Value'=>'Yes'
            )
         );
   } else {
      dbQuery('DELETE FROM PluginsConfig WHERE Id=?', array( $popt['Id'] ) );
   }
}

foreach($options as $name => $values)
{
   if(!in_array($name, $optionNames))
   {
      $popt=$options[$name];
      switch($popt['Type'])
      {
        case "select":
            $sql="INSERT INTO PluginsConfig VALUES ('',?,?,?,?,'','',?,?,?)";
            dbQuery($sql, array( $popt['Name'], $popt['Value'], $popt['Type'], $popt['Choices'], $mid, $zid, $plugin ) );
        break;
        case "integer":
            $sql="INSERT INTO PluginsConfig VALUES ('',?,?,?,'',?,?,?,?,?)";
            dbQuery($sql, array( $popt['Name'], $popt['Value'], $popt['Type'], $popt['Min'], $popt['Max'], $mid, $zid, $plugin ) );
        break;
        case "checkbox":
        case "text":
        default:
            $sql="INSERT INTO PluginsConfig VALUES ('',?,?,?,'','','',?,?,?)";
            dbQuery($sql, array( $popt['Name'], $popt['Value'], $popt['Type'], $mid, $zid, $plugin ) );
      }
   }
}

$PLANG=array();
$lang_path = $plugin_path."/lang";
$userLangFile = $lang_path."/".$user['Language'].".php";
if (isset($user['Language']) && file_exists($userLangFile)) {
    include_once($userLangFile);
} else {
    $systemLangFile = $lang_path."/".ZM_LANG_DEFAULT.".php";
    if (file_exists($systemLangFile)) {
        include_once($systemLangFile);
    } else {
        $fallbackLangFile = $lang_path."/en_gb.php";
        if (file_exists($fallbackLangFile)) {
            include_once($fallbackLangFile);
        }
    }
}

function pLang($name)
{
   global $SLANG;
   global $PLANG;
   if(array_key_exists($name, $SLANG))
      return $SLANG[$name];
   else if(array_key_exists($name, $PLANG))
      return $PLANG[$name];
   else
      return $name;
}

function isEnabled($param)
{
   global $options;
   $option = $options[$param];
   if (!isset($option['Require']))
       return true;
   foreach($option['Require'] as $req_couple)
   {
      $name = $req_couple['Name'];
      if (!array_key_exists($name, $options))
         continue;
      if ($req_couple['Value'] != $options[$name]['Value'])
         return false;
   }
   return true;
}

xhtmlHeaders(__FILE__, $SLANG['Plugin'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['Monitor'] ?> <?= $monitor['Name'] ?> - <?= $SLANG['Zone'] ?> <?= $newZone['Name'] ?> - <?= $SLANG['Plugin'] ?> <?= $plugin ?></h2>
    </div>
    <div id="content">
      <form name="pluginForm" id="pluginForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value="plugin"/>
        <input type="hidden" name="mid" value="<?= $mid ?>"/>
        <input type="hidden" name="zid" value="<?= $zid ?>"/>
        <input type="hidden" name="pl" value="<?= $plugin ?>"/>

        <div id="settingsPanel">
          <table id="pluginSettings" cellspacing="0">
            <tbody>
<?
foreach($options as $name => $popt)
{
?>
            <tr><th scope="row"><?= pLang($name) ?></th>
<?
   switch($popt['Type'])
   {
      case "checkbox":
            ?>
               <td><input type="checkbox" name="pluginOpt[<?= $popt['Name'] ?>]" id="pluginOpt[<?= $popt['Name'] ?>]" <? if ($popt['Value']) echo 'checked="checked"'; if (!isEnabled($popt['Name'])) echo 'disabled="disabled"'; ?>></td>
            <?
         break;
      case "select":
         $pchoices=explode(',',$popt['Choices']);
            ?>
               <td colspan="2">
                  <select name="pluginOpt[<?= $popt['Name'] ?>]" id="pluginOpt[<?= $popt['Name'] ?>]" <? if (!isEnabled($popt['Name'])) echo 'disabled="disabled"'; ?> onchange="applyDependencies()" >
            <?
            foreach($pchoices as $pchoice)
            {
               $psel="";
               if($popt['Value']==$pchoice)
                  $psel="selected";
               ?>
                     <option value="<?= $pchoice ?>" <?= $psel ?>><?= pLang($pchoice) ?></option>
               <?
            }
            ?>
                  </select>
               </td>
         <?
         break;
      case "text":
            ?>
                <td><input type="text" name="pluginOpt[<?= $popt['Name'] ?>]" id="pluginOpt[<?= $popt['Name'] ?>]" value="<?= $popt['Value'] ?>" <? if (!isEnabled($popt['Name'])) echo 'disabled="disabled"'; ?>></td>
            <?
         break;
      case "integer":
            ?>
                <td><input type="text" name="pluginOpt[<?= $popt['Name'] ?>]" id="pluginOpt[<?= $popt['Name'] ?>]" onchange="limitRange( this, <?= $popt['Min'] ?>, <?= $popt['Max'] ?> )" value="<?= $popt['Value'] ?>" size="4" <? if (!isEnabled($popt['Name'])) echo 'disabled="disabled"'; ?>></td>
            <?
         break;
      default:
         echo "Type '".$popt['Type']."' is not implemented<br>";
   }
   ?>
            </tr>
   <?
}
?>
            </tbody>
          </table>
          <input type="submit" id="submitBtn" name="submitBtn" value="<?= $SLANG['Save'] ?>" onclick="return saveChanges( this )"<?php if (!canEdit( 'Monitors' ) || (false && $selfIntersecting)) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
