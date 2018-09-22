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

# Have to do this stuff up here before including header.php because fof the cookie setting
$skin_options = array_map('basename', glob('skins/*',GLOB_ONLYDIR));
if ( $tab == 'skins' ) {
  $current_skin = $_COOKIE['zmSkin'];
  $reload = false;
  if ( isset($_GET['skin-choice']) && ( $_GET['skin-choice'] != $current_skin ) ) {
    setcookie('zmSkin',$_GET['skin-choice'], time()+3600*24*30*12*10 );
    //header("Location: index.php?view=options&tab=skins&reset_parent=1");
    $reload = true;
  }
  $current_css = $_COOKIE['zmCSS'];
  if ( isset($_GET['css-choice']) and ( $_GET['css-choice'] != $current_css ) ) {
    setcookie('zmCSS',$_GET['css-choice'], time()+3600*24*30*12*10 );
    array_map('unlink', glob(ZM_PATH_WEB.'/cache/*')); //cleanup symlinks from cache_bust
    //header("Location: index.php?view=options&tab=skins&reset_parent=1");
    $reload = true;
  }
  if ( $reload )
    echo "<script type=\"text/javascript\">if(window.opener){window.opener.location.reload();}window.location.href=\"{$_SERVER['PHP_SELF']}?view={$view}&tab={$tab}\"</script>";
} # end if tab == skins

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
          <form name="optionsForm" class="form-horizontal" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
            <input type="hidden" name="view" value="<?php echo $view ?>"/>
            <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
            <div class="form-group">
              <label for="skin-choice" class="col-sm-3 control-label">SKIN</label>
              <div class="col-sm-6">
                <select name="skin-choice" class="form-control chosen">
<?php
foreach ( $skin_options as $dir ) {
  echo '<option value="'.$dir.'" '.($current_skin==$dir ? 'SELECTED="SELECTED"' : '').'>'.$dir.'</option>';
}
?>
                </select>
                <span class="help-block"><?php echo translate('SkinDescription'); ?></span>
              </div>
            </div>
            <div class="form-group">
              <label for="css-choice" class="col-sm-3 control-label">CSS</label>
              <div class="col-sm-6">
                <select name="css-choice" class="form-control chosen">
<?php
foreach ( array_map('basename', glob('skins/'.$current_skin.'/css/*',GLOB_ONLYDIR)) as $dir ) {
  echo '<option value="'.$dir.'" '.($current_css==$dir ? 'SELECTED="SELECTED"' : '').'>'.$dir.'</option>';
}
?>
                </select>
                <span class="help-block"><?php echo translate('CSSDescription'); ?></span>
              </div>
            </div>
            <div id="contentButtons">
              <button value="Save" type="submit"<?php echo $canEdit?'':' disabled="disabled"' ?>><?php echo translate('Save') ?></button>
            </div>
         </form>
	
      <?php
} else if ( $tab == 'users' ) {
?>
      <form name="userForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
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
              <td class="colMark"><input type="checkbox" name="markUids[]" value="<?php echo $row['Id'] ?>" onclick="configureDeleteButton( this );"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
    }
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <button type="button" value="Add New User" onclick="createPopup('?view=user&amp;uid=0', 'zmUser', 'user');"<?php if ( !canEdit( 'System' ) ) { ?> disabled="disabled"<?php } ?>><?php echo translate('AddNewUser') ?></button>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </form>
<?php
} else if ( $tab == 'servers' ) { ?>
      <form name="serversForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="tab" value="<?php echo $tab ?>"/>
        <input type="hidden" name="action" value="delete"/>
        <input type="hidden" name="object" value="server"/>
        <table id="contentTable" class="table table-striped">
          <thead class="thead-highlight">
            <tr>
              <th class="colName"><?php echo translate('Name') ?></th>
              <th class="colHostname"><?php echo translate('Hostname') ?></th>
              <th class="colStatus"><?php echo translate('Status') ?></th>
              <th class="colMonitorCount"><?php echo translate('Monitors') ?></th>
              <th class="colCpuLoad"><?php echo translate('CpuLoad') ?></th>
              <th class="colMemory"><?php echo translate('Free').'/'.translate('Total') . ' ' . translate('Memory') ?></th>
              <th class="colSwap"><?php echo translate('Free').'/'.translate('Total') . ' ' . translate('Swap') ?></th>
              <th class="colStats"><?php echo translate('RunStats') ?></th>
              <th class="colAudit"><?php echo translate('RunAudit') ?></th>
              <th class="colTrigger"><?php echo translate('RunTrigger') ?></th>
              <th class="colMark"><?php echo translate('Mark') ?></th>
			</tr>
          </thead>
          <tbody>
<?php
  foreach( dbFetchAll( 'SELECT *,(SELECT COUNT(Id) FROM Monitors WHERE ServerId=Servers.Id) AS MonitorCount FROM Servers ORDER BY Id' ) as $row ) {
?>
            <tr>
              <td class="colName"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server', validHtmlStr($row['Name']), $canEdit ) ?></td>
              <td class="colHostname"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server', validHtmlStr($row['Hostname']), $canEdit ) ?></td>
              <td class="colStatus
<?php if ( $row['Status'] == 'NotRunning' ) { echo 'danger'; } ?>
"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server', validHtmlStr($row['Status']), $canEdit ) ?></td>
              <td class="colMonitorCount"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server', validHtmlStr($row['MonitorCount']), $canEdit ) ?></td>
              <td class="colCpuLoad
<?php if ( $row['CpuLoad'] > 5 ) { echo 'danger'; } ?>
"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server',$row['CpuLoad'], $canEdit ) ?></td>
              <td class="colMemory
<?php if ( $row['FreeMem']/$row['TotalMem'] < .1 ) { echo 'danger'; } ?>"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server', human_filesize($row['FreeMem']) . ' / ' . human_filesize($row['TotalMem']), $canEdit ) ?></td>
              <td class="colSwap
<?php if ( (!$row['TotalSwap']) or ($row['FreeSwap']/$row['TotalSwap'] < .1) ) { echo 'danger'; } ?>"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server', human_filesize($row['FreeSwap']) . ' / ' . human_filesize($row['TotalSwap']) , $canEdit ) ?></td>
              <td class="colStats"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server', $row['zmstats'] ? 'yes' : 'no', $canEdit ) ?></td>
              <td class="colAudit"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server', $row['zmaudit'] ? 'yes' : 'no', $canEdit ) ?></td>
              <td class="colTrigger"><?php echo makePopupLink( '?view=server&amp;id='.$row['Id'], 'zmServer', 'server', $row['zmtrigger'] ? 'yes' : 'no', $canEdit ) ?></td>

              <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $row['Id'] ?>" onclick="configureDeleteButton( this );"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
			</tr>
<?php } #end foreach Server ?>
          </tbody>
        </table>
        <div id="contentButtons">
          <button type="button" value="Add New Server" onclick="createPopup('?view=server&amp;id=0','zmServer','server');"<?php if ( !canEdit( 'System' ) ) { ?> disabled="disabled"<?php } ?>><?php echo translate('AddNewServer') ?></button>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </form>
<?php
} else if ( $tab == 'storage' ) { ?>
      <form name="storageForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
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
              <th class="colMark"><?php echo translate('Mark') ?></th>
            </tr>
          </thead>
          <tbody>
<?php foreach( Storage::find( null, array('order'=>'lower(Name)') ) as $Storage ) { ?>
            <tr>
              <td class="colId"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Id()), $canEdit ) ?></td>
              <td class="colName"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Name()), $canEdit ) ?></td>
              <td class="colPath"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Path()), $canEdit ) ?></td>
              <td class="colType"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Type()), $canEdit ) ?></td>
              <td class="colScheme"><?php echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Scheme()), $canEdit ) ?></td>
              <td class="colServer"><?php
              echo makePopupLink('?view=storage&amp;id='.$Storage->Id(), 'zmStorage', 'storage', validHtmlStr($Storage->Name()), $canEdit ) ?></td>
              <td class="colDiskSpace"><?php echo human_filesize($Storage->disk_used_space()) . ' of ' . human_filesize($Storage->disk_total_space()) ?></td>
              <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $Storage->Id() ?>" onclick="configureDeleteButton(this);"<?php if ( !$canEdit ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php } #end foreach Server ?>
          </tbody>
        </table>
        <div id="contentButtons">
          <button type="button" value="Add New Storage" onclick="createPopup('?view=storage&amp;id=0','zmStorage','storage');"<?php if ( !canEdit( 'System' ) ) { ?> disabled="disabled"<?php } ?>><?php echo translate('AddNewStorage') ?></button>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete" disabled="disabled"><?php echo translate('Delete') ?></button>
        </div>
      </form>
<?php
} else {
    if ( $tab == 'system' ) {
        $configCats[$tab]['ZM_LANG_DEFAULT']['Hint'] = join( '|', getLanguages() );
        $configCats[$tab]['ZM_SKIN_DEFAULT']['Hint'] = join( '|', $skin_options );
        $configCats[$tab]['ZM_CSS_DEFAULT']['Hint'] = join( '|', array_map ( 'basename', glob('skins/'.ZM_SKIN_DEFAULT.'/css/*',GLOB_ONLYDIR) ) );
        $configCats[$tab]['ZM_BANDWIDTH_DEFAULT']['Hint'] = $bandwidth_options;
    }
?>
      <form name="optionsForm" class="form-horizontal" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
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
        } elseif ( is_array( $value['Hint'] ) ) {
          echo htmlSelect( "newConfig[$name]", $value['Hint'], $value['Value'] );
        } elseif ( preg_match( '/\|/', $value['Hint'] ) ) {
?>

<?php
            $options = explode( '|', $value['Hint'] );
            if ( count( $options ) > 3 ) {
?>
                <select class="form-control" name="newConfig[<?php echo $name ?>]"<?php echo $canEdit?'':' disabled="disabled"' ?>>
<?php
                foreach ( $options as $option ) {
                  if ( preg_match( '/^([^=]+)=(.+)$/', $option, $matches ) ) {
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
                  if ( preg_match( '/^([^=]+)=(.+)$/', $option ) ) {
                    $optionLabel = $matches[1];
                    $optionValue = $matches[2];
                  } else {
                    $optionLabel = $optionValue = $option;
                  }
?>
                <label>
                  <input type="radio" id="<?php echo $name.'_'.preg_replace( '/[^a-zA-Z0-9]/', '', $optionValue ) ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo $optionValue ?>"<?php if ( $value['Value'] == $optionValue ) { ?> checked="checked"<?php } ?><?php echo $canEdit?'':' disabled="disabled"' ?>/>
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
              <input type="number" class="form-control" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" class="small"<?php echo $canEdit?'':' disabled="disabled"' ?>/>
<?php
        } else if ( $value['Type'] == 'hexadecimal' ) {
?>
              <input type="text" class="form-control" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" class="medium"<?php echo $canEdit?'':' disabled="disabled"' ?>/>
<?php
        } else if ( $value['Type'] == 'decimal' ) {
?>
              <input type="text" class="form-control" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" class="small"<?php echo $canEdit?'':' disabled="disabled"' ?>/>
<?php
        } else {
?>
              <input type="text" class="form-control" id="<?php echo $name ?>" name="newConfig[<?php echo $name ?>]" value="<?php echo validHtmlStr($value['Value']) ?>" class="large"<?php echo $canEdit?'':' disabled="disabled"' ?>/>
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
          <button type="submit" value="Save"<?php echo $canEdit?'':' disabled="disabled"' ?>><?php echo translate('Save') ?></button>
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
