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
      <h2><?= $SLANG['Options'] ?></h2>
    </div>
    <div id="content">
      <ul class="tabList">
<?php
foreach ( $tabs as $name=>$value )
{
    if ( $tab == $name )
    {
?>
        <li class="active"><?= $value ?></li>
<?php
    }
    else
    {
?>
        <li><a href="?view=<?= $view ?>&amp;tab=<?= $name ?>"><?= $value ?></a></li>
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
		setcookie('zmSkin',$_GET['skin-choice'], time()+3600*24*30*12*10 );
		//header("Location: index.php?view=options&tab=skins&reset_parent=1");
		echo "<script type=\"text/javascript\">window.opener.location.reload();window.location.href=\"{$_SERVER['PHP_SELF']}?view={$view}&tab={$tab}\"</script>";
	}

?>
	<form name="optionsForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="tab" value="<?= $tab ?>"/>
		<table class="contentTable major optionTable" cellspacing="0">
			<thead><tr><th><?= $SLANG['Name'] ?></th><th><?= $SLANG['Description'] ?></th> <th><?= $SLANG['Value'] ?></th></tr></thead>
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
          <input type="submit" value="<?= $SLANG['Save'] ?>"<?= $canEdit?'':' disabled="disabled"' ?>/>
		  <input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
        </div>
     </form>
	
      <?php
}
elseif ( $tab == "users" )
{
?>
      <form name="userForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="tab" value="<?= $tab ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <table id="contentTable" class="major userTable" cellspacing="0">
          <thead>
            <tr>
              <th class="colUsername"><?= $SLANG['Username'] ?></th>
              <th class="colLanguage"><?= $SLANG['Language'] ?></th>
              <th class="colEnabled"><?= $SLANG['Enabled'] ?></th>
              <th class="colStream"><?= $SLANG['Stream'] ?></th>
              <th class="colEvents"><?= $SLANG['Events'] ?></th>
              <th class="colControl"><?= $SLANG['Control'] ?></th>
              <th class="colMonitors"><?= $SLANG['Monitors'] ?></th>
              <th class="colSystem"><?= $SLANG['System'] ?></th>
              <th class="colBandwidth"><?= $SLANG['Bandwidth'] ?></th>
              <th class="colMonitor"><?= $SLANG['Monitor'] ?></th>
              <th class="colMark"><?= $SLANG['Mark'] ?></th>
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
              <td class="colUsername"><?= makePopupLink( '?view=user&amp;uid='.$row['Id'], 'zmUser', 'user', validHtmlStr($row['Username']).($user['Username']==$row['Username']?"*":""), $canEdit ) ?></td>
              <td class="colLanguage"><?= $row['Language']?validHtmlStr($row['Language']):'default' ?></td>
              <td class="colEnabled"><?= $row['Enabled']?$SLANG['Yes']:$SLANG['No'] ?></td>
              <td class="colStream"><?= validHtmlStr($row['Stream']) ?></td>
              <td class="colEvents"><?= validHtmlStr($row['Events']) ?></td>
              <td class="colControl"><?= validHtmlStr($row['Control']) ?></td>
              <td class="colMonitors"><?= validHtmlStr($row['Monitors']) ?></td>
              <td class="colSystem"><?= validHtmlStr($row['System']) ?></td>
              <td class="colBandwidth"><?= $row['MaxBandwidth']?$bwArray[$row['MaxBandwidth']]:'&nbsp;' ?></td>
              <td class="colMonitor"><?= $row['MonitorIds']?(join( ", ", $userMonitors )):"&nbsp;" ?></td>
              <td class="colMark"><input type="checkbox" name="markUids[]" value="<?= $row['Id'] ?>" onclick="configureDeleteButton( this );"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
    }
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="button" value="<?= $SLANG['AddNewUser'] ?>" onclick="createPopup( '?view=user&amp;uid=0', 'zmUser', 'user' );"<?php if ( !canEdit( 'System' ) ) { ?> disabled="disabled"<?php } ?>/><input type="submit" name="deleteBtn" value="<?= $SLANG['Delete'] ?>" disabled="disabled"/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
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
      <form name="optionsForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="tab" value="<?= $tab ?>"/>
        <input type="hidden" name="action" value="options"/>
        <table id="contentTable" class="major optionTable" cellspacing="0">
          <thead>
            <tr>
              <th><?= $SLANG['Name'] ?></th>
              <th><?= $SLANG['Description'] ?></th>
              <th><?= $SLANG['Value'] ?></th>
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
              <td><?= $shortName ?></td>
              <td><?= validHtmlStr($optionPromptText) ?>&nbsp;(<?= makePopupLink( '?view=optionhelp&amp;option='.$name, 'zmOptionHelp', 'optionhelp', '?' ) ?>)</td>
<?php   
        if ( $value['Type'] == "boolean" )
        {
?>
              <td><input type="checkbox" id="<?= $name ?>" name="newConfig[<?= $name ?>]" value="1"<?php if ( $value['Value'] ) { ?> checked="checked"<?php } ?><?= $canEdit?'':' disabled="disabled"' ?>/></td>
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
                <select name="newConfig[<?= $name ?>]"<?= $canEdit?'':' disabled="disabled"' ?>>
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
                  <option value="<?= $optionValue ?>"<?php if ( $value['Value'] == $optionValue ) { echo ' selected="selected"'; } ?>><?= htmlspecialchars($optionLabel) ?></option>
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
                <span><input type="radio" id="<?= $name.'_'.preg_replace( '/[^a-zA-Z0-9]/', '', $optionValue ) ?>" name="newConfig[<?= $name ?>]" value="<?= $optionValue ?>"<?php if ( $value['Value'] == $optionValue ) { ?> checked="checked"<?php } ?><?= $canEdit?'':' disabled="disabled"' ?>/>&nbsp;<?= htmlspecialchars($optionLabel) ?></span>
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
              <td><textarea id="<?= $name ?>" name="newConfig[<?= $name ?>]" rows="5" cols="40"<?= $canEdit?'':' disabled="disabled"' ?>><?= validHtmlStr($value['Value']) ?></textarea></td>
<?php
        }
        elseif ( $value['Type'] == "integer" )
        {
?>
              <td><input type="text" id="<?= $name ?>" name="newConfig[<?= $name ?>]" value="<?= validHtmlStr($value['Value']) ?>" class="small"<?= $canEdit?'':' disabled="disabled"' ?>/></td>
<?php
        }
        elseif ( $value['Type'] == "hexadecimal" )
        {
?>
              <td><input type="text" id="<?= $name ?>" name="newConfig[<?= $name ?>]" value="<?= validHtmlStr($value['Value']) ?>" class="medium"<?= $canEdit?'':' disabled="disabled"' ?>/></td>
<?php
        }
        elseif ( $value['Type'] == "decimal" )
        {
?>
              <td><input type="text" id="<?= $name ?>" name="newConfig[<?= $name ?>]" value="<?= validHtmlStr($value['Value']) ?>" class="small"<?= $canEdit?'':' disabled="disabled"' ?>/></td>
<?php
        }
        else
        {
?>
              <td><input type="text" id="<?= $name ?>" name="newConfig[<?= $name ?>]" value="<?= validHtmlStr($value['Value']) ?>" class="large"<?= $canEdit?'':' disabled="disabled"' ?>/></td>
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
          <input type="submit" value="<?= $SLANG['Save'] ?>"<?= $canEdit?'':' disabled="disabled"' ?>/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
        </div>
      </form>
<?php
}
?>
    </div>
  </div>
</body>
</html>
