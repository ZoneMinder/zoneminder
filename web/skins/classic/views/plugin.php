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

xhtmlHeaders(__FILE__, translate('Plugin') );


$generalOptions = array(
   'Enabled' => array(
      'Type' => 'select',
      'Choices' => 'Yes,No',
      'Value' => 'No',
   ),
   'RequireNatDet' => array(
      'Type' => 'select',
      'Choices' => 'Yes,No',
      'Value' => 'No',
      'Require' => array(
         array(
            'Name' => 'Enabled',
            'Value' => 'Yes',
         ),
      ),
   ),
   'IncludeNatDet' => array(
      'Type' => 'select',
      'Choices' => 'Yes,No',
      'Value' => 'No',
      'Require' => array(
         array(
            'Name' => 'Enabled',
            'Value' => 'Yes',
         ),
         array(
            'Name' => 'RequireNatDet',
            'Value' => 'Yes',
         ),
      ),
   ),
   'ReInitNatDet' => array(
      'Type' => 'select',
      'Choices' => 'Yes,No',
      'Value' => 'No',
      'Require' => array(
         array(
            'Name' => 'Enabled',
            'Value' => 'Yes',
         ),
         array(
            'Name' => 'RequireNatDet',
            'Value' => 'Yes',
         ),
      ),
   ),
   'AlarmScore'=>array(
      'Type'=>'integer',
      'Min'=>'1',
      'Max'=>'100',
      'Value'=>'99',
      'Require'=>array(
         array(
            'Name' => 'Enabled',
            'Value' => 'Yes',
         ),
      ),
   ),
);

$pOptions=$generalOptions;
$optionNames=array();
if(file_exists($plugin_path."/config.php"))
{
   include_once($plugin_path."/config.php");
   if(isset($pluginOptions))
      foreach( $pluginOptions as $optionKey => $optionValue )
      {
         // Set default dependency information if not set in configuration file
         if(!isset($optionValue['Require']))
         {
            $optionValue['Require'] = array (
               array(
                  'Name' => 'Enabled',
                  'Value' => 'Yes',
               ),
            );
         }
         elseif(is_array($optionValue['Require']))
         {
            $optionValue['Require'][] = array (
               'Name' => 'Enabled',
               'Value' => 'Yes',
            );
         }
         else
         {
            // Wrong type
            continue;
         }
         $pOptions[$optionKey]=$optionValue;
      }
}

$sql='SELECT * FROM PluginsConfig WHERE MonitorId=? AND ZoneId=? AND pluginName=?';
foreach( dbFetchAll( $sql, NULL, array( $mid, $zid, $plugin ) ) as $popt )
{
   if(array_key_exists($popt['Name'], $pOptions)
      && $popt['Type']==$pOptions[$popt['Name']]['Type'])
   {
      array_push($optionNames, $popt['Name']);

      // Backup dependency information
      $require = '';
      if(isset($pOptions[$popt['Name']]['Require']))
         $require = $pOptions[$popt['Name']]['Require'];

      // Set value from database
      $pOptions[$popt['Name']]=$popt;

      // Restore dependancy information from backup
      if(!empty($require))
         $pOptions[$popt['Name']]['Require'] = $require;

      // Set default dependancy information if not set in configuration
      else if($popt['Name'] != 'Enabled')
         $pOptions[$popt['Name']]['Require'] = array (
            array(
               'Name' => 'Enabled',
               'Value' => 'Yes',
            ),
         );
   } else {
      dbQuery('DELETE FROM PluginsConfig WHERE Id=?', array( $popt['Id'] ) );
   }
}

// Add option in database if missing
foreach($pOptions as $key => $popt)
{
   if(!in_array($key, $optionNames))
   {
      switch($popt['Type'])
      {
        case "select":
            $sql="INSERT INTO PluginsConfig VALUES ('',?,?,?,?,'','',?,?,?)";
            dbQuery($sql, array( $key, $popt['Value'], $popt['Type'], $popt['Choices'], $mid, $zid, $plugin ) );
            break;
        case "integer":
            $sql="INSERT INTO PluginsConfig VALUES ('',?,?,?,'',?,?,?,?,?)";
            dbQuery($sql, array( $key, $popt['Value'], $popt['Type'], $popt['Min'], $popt['Max'], $mid, $zid, $plugin ) );
            break;
        case "list":
            $sql="INSERT INTO PluginsConfig VALUES ('',?,'',?,'','','',?,?,?)";
            dbQuery($sql, array( $key, $popt['Type'], $mid, $zid, $plugin ) );
            break;
        case "checkbox":
        case "text":
        default:
            $sql="INSERT INTO PluginsConfig VALUES ('',?,?,?,'','','',?,?,?)";
            dbQuery($sql, array( $key, $popt['Value'], $popt['Type'], $mid, $zid, $plugin ) );
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
   global $pOptions;
   $option = $pOptions[$param];
   if (!isset($option['Require']))
       return true;
   foreach($option['Require'] as $req_couple)
   {
      $name = $req_couple['Name'];
      if (!array_key_exists($name, $pOptions))
         continue;
      if ($req_couple['Value'] != $pOptions[$name]['Value'])
         return false;
   }
   return true;
}

xhtmlHeaders(__FILE__, $SLANG['Plugin'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Monitor') ?> <?php echo $monitor['Name'] ?> - <?php echo translate('Zone') ?> <?php echo $newZone['Name'] ?> - <?php echo translate('Plugin') ?> <?php echo $plugin ?></h2>
    </div>
    <div id="content">
      <form name="pluginForm" id="pluginForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value="plugin"/>
        <input type="hidden" name="mid" value="<?php echo $mid ?>"/>
        <input type="hidden" name="zid" value="<?php echo $zid ?>"/>
        <input type="hidden" name="pl" value="<?php echo $plugin ?>"/>

        <div id="settingsPanel">
          <table id="pluginSettings" cellspacing="0">
            <tbody>
<?php
foreach($pOptions as $key => $popt)
{
   ?>
            <tr><th scope="row"><?php echo pLang($key) ?></th>
   <?php
   switch($popt['Type'])
   {
      case "checkbox":
            ?>
               <td>
                  <input type="checkbox" name="dsp_pluginOpt[<?php echo $key; ?>]" id="dsp_pluginOpt[<?php echo $key; ?>]" <?php if ($popt['Value'] == "Yes") echo 'checked="checked"'; if (!isEnabled($key)) echo 'disabled="disabled"'; ?> onchange="applyChanges();">
                  <input type="hidden" name="pluginOpt[<?php echo $key; ?>]" id="pluginOpt[<?php echo $key; ?>]" value="<?php if ($popt['Value'] == "Yes") echo "Yes"; else echo "No"; ?>">
               </td>
            <?php
         break;
      case "select":
         $pchoices=explode(',',$popt['Choices']);
            ?>
               <td colspan="2">
                  <select name="dsp_pluginOpt[<?php echo $key; ?>]" id="dsp_pluginOpt[<?php echo $key; ?>]" <?php if (!isEnabled($key)) echo 'disabled="disabled"'; ?> onchange="applyChanges();">
            <?php
            foreach($pchoices as $pchoice) {
               $psel="";
               if($popt['Value']==$pchoice)
                  $psel="selected=\"selected\"";
            ?>
                     <option value="<?php echo $pchoice ?>" <?php echo $psel ?>><?php echo pLang($pchoice); ?></option>
            <?php
            }
            ?>
                  </select>
                  <input type="hidden" name="pluginOpt[<?php echo $key; ?>]" id="pluginOpt[<?php echo $key; ?>]" value="<?php echo $popt['Value']; ?>" />
               </td>
         <?php
         break;
      case "text":
            ?>
                <td>
                  <input type="text" name="dsp_pluginOpt[<?php echo $key; ?>]" id="dsp_pluginOpt[<?php echo $key; ?>]" value="<?php echo $popt['Value']; ?>" <?php if (!isEnabled($key)) echo 'disabled="disabled"'; ?> onchange="applyChanges();">
                  <input type="hidden" name="pluginOpt[<?php echo $key; ?>]" id="pluginOpt[<?php echo $key; ?>]" value="<?php echo $popt['Value']; ?>" />
                </td>
            <?php
         break;
      case "integer":
            ?>
                <td>
                  <input type="text" name="dsp_pluginOpt[<?php echo $key; ?>]" id="dsp_pluginOpt[<?php echo $key; ?>]" onchange="limitRange( this, <?php echo $popt['Min'] ?>, <?php echo $popt['Max']; ?> ); applyChanges();" value="<?php echo $popt['Value']; ?>" size="4" <?php if (!isEnabled($key)) echo 'disabled="disabled"'; ?>>
                  <input type="hidden" name="pluginOpt[<?php echo $key; ?>]" id="pluginOpt[<?php echo $key; ?>]" value="<?php echo $popt['Value']; ?>" />
                </td>
            <?php
         break;
      case "list":
         $nbopt = 0;
         $pvalues=explode(',',$popt['Value']);
            ?>
                <td style="padding:0px;"><table class="listSetting">
                  <tr>
                    <td>
                      <input type="text" name="dsp_input_pluginOpt[<?php echo $key; ?>]" id="dsp_input_pluginOpt[<?php echo $key; ?>]" <?php if (!isEnabled($key)) echo 'disabled="disabled"'; ?> onkeyup="updateAddBtn('<?php echo $key; ?>');" />
                    </td>
                    <td>
                      <input type="button" name="addBtn[<?php echo $key; ?>]" id="addBtn[<?php echo $key; ?>]" value="<?php echo $SLANG['Add'] ?>" onclick="addOption('<?php echo $key; ?>');" disabled="disabled" />
                    </td>
                  </tr><tr>
                    <td>
                      <select multiple="multiple" name="dsp_pluginOpt[<?php echo $key; ?>]" id="dsp_pluginOpt[<?php echo $key; ?>]" <?php if (!isEnabled($key)) echo 'disabled="disabled"'; ?>>
            <?php
            foreach($pvalues as $pvalue) {
               if(!empty($pvalue)) {
                  $nbopt++;
            ?>
                        <option value="<?php echo $pvalue; ?>"><?php echo $pvalue; ?></option>
            <?php
               }
            }
            ?>
                      </select>
                      <input type="hidden" name="pluginOpt[<?php echo $key; ?>]" id="pluginOpt[<?php echo $key; ?>]" value="<?php echo $popt['Value']; ?>" />
                    </td>
                    <td>
                      <input type="button" name="removeBtn[<?php echo $key; ?>]" id="removeBtn[<?php echo $key; ?>]" value="<?php echo $SLANG['Remove'] ?>" onclick="removeOptionSelected('<?php echo $key; ?>');" <?php if ($nbopt == 0) echo 'disabled="disabled"'; ?> />
                    </td>
                  </tr>
                </table></td>
            <?php
         break;
      default:
         echo "Type '".$popt['Type']."' is not implemented<br>";
   }
   ?>
            </tr>
   <?php
}
?>
            </tbody>
          </table>
          <input type="submit" id="submitBtn" name="submitBtn" value="<?php echo translate('Save') ?>" onclick="return saveChanges( this )"<?php if (!canEdit( 'Monitors' ) || (false && $selfIntersecting)) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
