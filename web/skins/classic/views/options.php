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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

if ( !canView( 'System' ) )
{
    $view = "error";
    return;
}

$canEdit = canEdit( 'System' );

$tabs = array();
$tabs['skins'] = $SLANG['Display']; // change me to be supported by SLANG...
$tabs['system'] = $SLANG['System'];
$tabs['config'] = $SLANG['Config'];
$tabs['paths'] = $SLANG['Paths'];
$tabs['web'] = $SLANG['Web'];
$tabs['images'] = $SLANG['Images'];
$tabs['logging'] = $SLANG['Logging'];
$tabs['network'] = $SLANG['Network'];
$tabs['mail'] = $SLANG['Email'];
$tabs['upload'] = $SLANG['Upload'];
$tabs['x10'] = $SLANG['X10'];
$tabs['highband'] = $SLANG['HighBW'];
$tabs['medband'] = $SLANG['MediumBW'];
$tabs['lowband'] = $SLANG['LowBW'];
$tabs['phoneband'] = $SLANG['PhoneBW'];
$tabs['eyeZm'] = "eyeZm";
$tabs['users'] = $SLANG['Users'];

if ( isset($_REQUEST['tab']) )
    $tab = validHtmlStr($_REQUEST['tab']);
else
    $tab = "system";

$focusWindow = true;

xhtmlHeaders( __FILE__, $SLANG['Options'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo $SLANG['Options'] ?></h2>
    </div>
    <div id="content">
      <ul class="tabList">
<?php
foreach ( $tabs as $name=>$value )
{
    if ( $tab == $name )
    {
?>
        <li class="active"><?php echo $value ?></li>
<?php
    }
    else
    {
?>
        <li><a href="?view=<?php echo $view ?>&amp;tab=<?php echo $name ?>"><?php echo $value ?></a></li>
<?php
    }
}
?>
      </ul>
      <div class="clear"></div>
<?php 
if($tab == 'skins') {
	$current_skin = $_COOKIE['zmSkin'];
	if (isset($_GET['skin-choice'])) {
		setcookie('zmSkin',$_GET['skin-choice']);
		//header("Location: index.php?view=options&tab=skins&reset_parent=1");
		echo "<script type=\"text/javascript\">window.opener.location.reload();window.location.href=\"{$_SERVER['PHP_SELF']}?view={$view}&tab={$tab}\"</script>";
	}

?>
	<form name="optionsForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
		<table class="contentTable major optionTable" cellspacing="0">
			<thead><tr><th><?php echo $SLANG['Name'] ?></th><th><?php echo $SLANG['Description'] ?></th> <th><?php echo $SLANG['Value'] ?></th></tr></thead>
			<tbody>
			<td>ZM_SKIN</td>
			<td><?php echo $SLANG['SkinDescription']; ?></td>
			<td><select name="skin-choice">
				<?php
					foreach(glob('skins/*',GLOB_ONLYDIR) as $dir) {
						$dir = basename($dir);
						echo '<option value="'.$dir.'" '.($current_skin==$dir ? 'SELECTED' : '').'>'.$dir.'</option>';
					}
				?>
				</select>
			</td>
		</table>
        <div id="contentButtons">
          <input type="submit" value="<?php echo $SLANG['Save'] ?>"<?php echo $canEdit?'':' disabled="disabled"' ?>/>
		  <input type="button" value="<?php echo $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
        </div>
     </form>
	
      <?php
}
elseif ( $tab == "users" )
{
?>
      <form name="userForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <table id="contentTable" class="major userTable" cellspacing="0">
          <thead>
            <tr>
              <th class="colUsername"><?php echo $SLANG['Username'] ?></th>
              <th class="colLanguage"><?php echo $SLANG['Language'] ?></th>
              <th class="colEnabled"><?php echo $SLANG['Enabled'] ?></th>
              <th class="colStream"><?php echo $SLANG['Stream'] ?></th>
              <th class="colEvents"><?php echo $SLANG['Events'] ?></th>
              <th class="colControl"><?php echo $SLANG['Control'] ?></th>
              <th class="colMonitors"><?php echo $SLANG['Monitors'] ?></th>
              <th class="colSystem"><?php echo $SLANG['System'] ?></th>
              <th class="colBandwidth"><?php echo $SLANG['Bandwidth'] ?></th>
              <th class="colMonitor"><?php echo $SLANG['Monitor'] ?></th>
              <th class="colMark"><?php echo $SLANG['Mark'] ?></th>
            </tr>
          </thead>
          <tbody>
<?php
    $sql = "select * from Monitors order by Sequence asc";
    $monitors = array();
    foreach( dbFetchAll( $sql ) as $monitor )
    {
        $monitors[$monitor['Id']] = $monitor;
    }

    $sql = "select * from Users";
    foreach( dbFetchAll( $sql ) as $row )
    {
        $userMonitors = array();
        if ( !empty($row['MonitorIds']) )
        {
            foreach ( explode( ",", $row['MonitorIds'] ) as $monitorId )
            {
                $userMonitors[] = $monitors[$monitorId]['Name'];
            }
        }
?>
            <tr>
              <td class="colUsername"><?php echo makePopupLink( '?view=user&amp;uid='.$row['Id'], 'zmUser', 'user', validHtmlStr($row['Username']).($user['Username']==$row['Username']?"*":""), $canEdit ) ?></td>
              <td class="colLanguage"><?php echo $row['Language']?validHtmlStr($row['Language']):'default' ?></td>
              <td class="colEnabled"><?php echo $row['Enabled']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colStream"><?php echo validHtmlStr($row['Stream']) ?></td>
              <td class="colEvents"><?php echo validHtmlStr($row['Events']) ?></td>
              <td class="colControl"><?php echo validHtmlStr($row['Control']) ?></td>
              <td class="colMonitors"><?php echo validHtmlStr($row['Monitors']) ?></td>
              <td class="colSystem"><?php echo validHtmlStr($row['System']) ?></td>
              <td class="colBandwidth"><?php echo $row['MaxBandwidth']?$bwArray[$row['MaxBandwidth']]:'&nbsp;' ?></td>
              <td class="colMonitor"><?php echo $row['MonitorIds']?(join( ", ", $userMonitors )):"&nbsp;" ?></td>
              <td class="colMark"><input type="checkbox" name="markUids[]" value="<?php echo $row['Id'] ?>" onclick="configureDeleteButton( this );"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
    }
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="button" value="<?php echo $SLANG['AddNewUser'] ?>" onclick="createPopup( '?view=user&amp;uid=0', 'zmUser', 'user' );"<?php if ( !canEdit( 'System' ) ) { ?> disabled="disabled"<?php } ?>/><input type="submit" name="deleteBtn" value="<?php echo $SLANG['Delete'] ?>" disabled="disabled"/><input type="button" value="<?php echo $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
        </div>
      </form>
<?php
}
else
{
    if ( $tab == "system" )
    {
        $configCats[$tab]['ZM_LANG_DEFAULT']['Hint'] = join( '|', getLanguages() );
    }
?>
      <form name="optionsForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="options"/>
        <table id="contentTable" class="major optionTable" cellspacing="0">
          <thead>
            <tr>
              <th><?php echo $SLANG['Name'] ?></th>
              <th><?php echo $SLANG['Description'] ?></th>
              <th><?php echo $SLANG['Value'] ?></th>
            </tr>
          </thead>
          <tbody>
<?php
    $configCat = $configCats[$tab];
    foreach ( $configCat as $name=>$value )
    {
        $shortName = preg_replace( '/^ZM_/', '', $name );
        $optionPromptText = !empty($OLANG[$shortName])?$OLANG[$shortName]['Prompt']:$value['Prompt'];
?>
            <tr>
              <td><?php echo $shortName ?></td>
              <td><?php echo validHtmlStr($optionPromptText) ?>&nbsp;(<?php echo makePopupLink( '?view=optionhelp&amp;option='.$name, 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td>
<?php   
        if ( $value['Type'] == "boolean" )
        {
?>
              <td><input type="checkbox" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="1"<?php if ( $value['Value'] ) { ?> checked="checked"<?php } ?><?php echo $canEdit?'':' disabled="disabled"' ?>/></td>
<?php
        }
        elseif ( preg_match( "/\|/", $value['Hint'] ) )
        {
?>
              <td class="nowrap">
<?php
            $options = explode( '|', $value['Hint'] );
            if ( count( $options ) > 3 )
            {
?>
                <select name="newConfig[<?php echo $name ?>]"<?php echo $canEdit?'':' disabled="disabled"' ?>>
<?php
                foreach ( $options as $option )
                {
                    if ( preg_match( '/^([^=]+)=(.+)$/', $option, $matches ) )
                    {
                        $optionLabel = $matches[1];
                        $optionValue = $matches[2];
                    }
                    else
                    {
                        $optionLabel = $optionValue = $option;
                    }
?>
                  <option value="<?php echo $optionValue ?>"<?php if ( $value['Value'] == $optionValue ) { echo ' selected="selected"'; } ?>><?php echo htmlspecialchars($optionLabel) ?></option>
<?php
                }
?>
                </select>
<?php
            }
            else
            {
                foreach ( $options as $option )
                {
                    if ( preg_match( '/^([^=]+)=(.+)$/', $option ) )
                    {
                        $optionLabel = $matches[1];
                        $optionValue = $matches[2];
                    }
                    else
                    {
                        $optionLabel = $optionValue = $option;
                    }
?>
                <span><input type="radio" id="<?php echo $name.'_'.preg_replace( '/[^a-zA-Z0-9]/', '', $optionValue ) ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo $optionValue ?>"<?php if ( $value['Value'] == $optionValue ) { ?> checked="checked"<?php } ?><?php echo $canEdit?'':' disabled="disabled"' ?>/>&nbsp;<?php echo htmlspecialchars($optionLabel) ?></span>
<?php
                }
            }
?>
              </td>
<?php
        }
        elseif ( $value['Type'] == "text" )
        {
?>
              <td><textarea id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" rows="5" cols="40"<?php echo $canEdit?'':' disabled="disabled"' ?>><?php echo validHtmlStr($value['Value']) ?></textarea></td>
<?php
        }
        elseif ( $value['Type'] == "integer" )
        {
?>
              <td><input type="text" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" class="small"<?php echo $canEdit?'':' disabled="disabled"' ?>/></td>
<?php
        }
        elseif ( $value['Type'] == "hexadecimal" )
        {
?>
              <td><input type="text" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" class="medium"<?php echo $canEdit?'':' disabled="disabled"' ?>/></td>
<?php
        }
        elseif ( $value['Type'] == "decimal" )
        {
?>
              <td><input type="text" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" class="small"<?php echo $canEdit?'':' disabled="disabled"' ?>/></td>
<?php
        }
        else
        {
?>
              <td><input type="text" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" class="large"<?php echo $canEdit?'':' disabled="disabled"' ?>/></td>
<?php
        }
?>
            </tr>
<?php
    }
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="submit" value="<?php echo $SLANG['Save'] ?>"<?php echo $canEdit?'':' disabled="disabled"' ?>/><input type="button" value="<?php echo $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
        </div>
      </form>
<?php
}
?>
    </div>
  </div>
</body>
</html>
