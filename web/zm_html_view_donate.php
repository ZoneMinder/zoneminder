<?php
//
// ZoneMinder web donate view file, $Date$, $Revision$
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

if ( !canEdit( 'System' ) )
{
	$view = "error";
	return;
}

$options = array( 
	"go"      => $zmSlangDonateYes,
	"hour"    => $zmSlangDonateRemindHour,
	"day"     => $zmSlangDonateRemindDay,
	"week"    => $zmSlangDonateRemindWeek,
	"month"   => $zmSlangDonateRemindMonth,
	"never"   => $zmSlangDonateRemindNever,
	"already" => $zmSlangDonateAlready,
);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangDonate ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
window.focus();
function zmWindow()
{
	var winName = window.open( 'http://www.zoneminder.com/donate.html', 'ZoneMinder' );
	winName.focus();
}
function closeWindow()
{
	window.close();
}
function submitForm()
{
	with( document.donate_form )
	{
		if ( option.selectedIndex == 0 )
		{
			view.value = '<?= $view ?>';
		}
		else
		{
			view.value = 'none';
		}
	}
}
<?php
if ( $action == "donate" && $option == "go" )
{
?>
zmWindow();
<?php
}
?>
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="2" width="100%">
<tr>
<td align="center" class="head">ZoneMinder - <?= $zmSlangDonate ?></td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="2" width="100%">
<form name="donate_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="donate">
<table border="0" cellspacing="0" cellpadding="6" width="100%">
<tr>
<td align="center" class="text" "align=justify"><?= $zmSlangDonateEnticement ?></td>
</tr>
<tr>
<td align="center" class="text"><?= buildSelect( "option", $options ); ?></td>
</tr>
</table>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td width="70%" align="center">&nbsp;</td>
<td width="15%" align="center"><input type="submit" value="<?= $zmSlangApply ?>" class="form" onClick="submitForm()"></td>
<td width="15%" align="center"><input type="button" value="<?= $zmSlangClose ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</form>
</body>
</html>
