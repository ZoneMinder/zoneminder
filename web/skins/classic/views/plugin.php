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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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

$plugin_path = dirname(ZM_PLUGINS_CONFIG_PATH)."/".$plugin;

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Plugin') );


$pluginOptions=array(
    'Enabled'=>array(
          'Type'=>'select',
          'Name'=>'Enabled',
          'Choices'=>'yes,no',
          'Value'=>'no'
          )
     );

$optionNames=array();
if(file_exists($plugin_path."/config.php"))
{
   include_once($plugin_path."/config.php");
} 

$sql='SELECT * FROM PluginsConfig WHERE MonitorId=? AND ZoneId=? AND pluginName=?';
foreach( dbFetchAll( $sql, NULL, array( $mid, $zid, $plugin ) ) as $popt )
{
   if(array_key_exists($popt['Name'], $pluginOptions) 
      && $popt['Type']==$pluginOptions[$popt['Name']]['Type']
      && $popt['Choices']==$pluginOptions[$popt['Name']]['Choices']
      )
   {
      $pluginOptions[$popt['Name']]=$popt;
      array_push($optionNames, $popt['Name']);
   } else {
      dbQuery('DELETE FROM PluginsConfig WHERE Id=?', array( $popt['Id'] ) );
   }
}
foreach($pluginOptions as $name => $values)
{
   if(!in_array($name, $optionNames))
   {
      $popt=$pluginOptions[$name];
      $sql="INSERT INTO PluginsConfig VALUES ('',?,?,?,?,?,?,?)";
      dbQuery($sql, array( $popt['Name'], $popt['Value'], $popt['Type'], $popt['Choices'], $mid, $zid, $plugin ) );
   }
}

$PLANG=array();
if(file_exists($plugin_path."/lang/".$user['Language'].".php")) {
   include_once($plugin_path."/lang/".$user['Language'].".php");
}

function pLang($name)
{
   global $PLANG;
   if(array_key_exists($name, $PLANG))
      return $PLANG[$name];
   else
      return $name;
}


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
foreach($pluginOptions as $name => $popt)
{
   ?>
            <tr><th scope="row"><?php echo pLang($name) ?></th>     
   <?php
   switch($popt['Type'])
   {
      case "checkbox":
         echo "CHECKBOX";
         break;
      case "select":
         $pchoices=explode(',',$popt['Choices']);
            ?>
               <td colspan="2">
                  <select name="pluginOpt[<?php echo $popt['Name'] ?>]" id="pluginOpt[<?php echo $popt['Name'] ?>]">
            <?php
            foreach($pchoices as $pchoice)
            {
               $psel="";
               if($popt['Value']==$pchoice)
                  $psel="selected";
               ?>
                     <option value="<?php echo $pchoice ?>" <?php echo $psel ?>><?php echo pLang($pchoice) ?></option>
               <?php
            }
            ?>
               </td>
                  </select>
         <?php
         break;
      case "text":
      default:
         echo "DEFAULT";
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
