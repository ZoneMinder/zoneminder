<?php
//
// ZoneMinder web run state view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

if ( !canEdit( 'Monitors' ) )
{
	$view = "error";
	return;
}

$monitor = dbFetchOne( "select C.*,M.* from Monitors as M inner join Controls as C on (M.ControlId = C.Id ) where M.Id = '$mid'" );

$sql = "select * from ControlPresets where MonitorId = '".$monitor['Id']."'";
$labels = array();
foreach( dbFetchAll( $sql ) as $row )
{
    $labels[$row['Preset']] = $row['Label'];
}

$presets = array();
for ( $i = 1; $i <= $monitor['NumPresets']; $i++ )
{
	$presets[$i] = "$zmSlangPreset $i";
	if ( $labels[$i] )
    {
        $presets[$i] .= " (".htmlentities(addslashes($labels[$i])).")";
    }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangSetPreset ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( !empty($refresh_parent) )
{
?>
opener.location.reload(true);
<?php
}
?>
window.focus();
var labels = new Array();
<?php
foreach ( $labels as $index=>$label )
{
?>
labels[<?= $index ?>] = "<?= htmlentities(addslashes($label)) ?>";
<?php
}
?>
function updateLabel( form )
{
    var preset_index = form.preset.options[form.preset.selectedIndex].value;
    if ( labels[preset_index] )
    {
        form.new_label.value = labels[preset_index];
    }
    else
    {
        form.new_label.value = "";
    }
}
function refreshWindow()
{
	window.location.reload(true);
}
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<form name="preset_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="action" value="control">
<input type="hidden" name="control" value="preset_set">
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="4" align="center" class="head">ZoneMinder - <?= $zmSlangSetPreset ?></td>
</tr>
<tr>
<td align="right"><?= buildSelect( "preset", $presets, "updateLabel( this.form )" ) ?></td>
<td align="left" class="text"><?= $zmSlangNewLabel ?>&nbsp;<input type="text" name="new_label" value="<?= $new_user['MonitorIds'] ?>" size="16" class="form"></td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="1" width="100%">
<tr>
<td align="right"><input type="submit" value="<?= $zmSlangSave ?>" class="form">&nbsp;&nbsp;<input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</form>
<script type="text/javascript">
updateLabel( document.preset_form );
</script>
</body>
</html>
