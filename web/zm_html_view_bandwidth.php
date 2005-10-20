<?php
//
// ZoneMinder web bandwidth view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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

$new_bandwidth = $bandwidth;

if ( $user && !empty($user['MaxBandwidth']) )
{
	if ( $user['MaxBandwidth'] == "low" )
	{
		unset( $bw_array['high'] );
		unset( $bw_array['medium'] );
	}
	elseif ( $user['MaxBandwidth'] == "medium" )
	{
		unset( $bw_array['high'] );
	}
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangBandwidth ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function closeWindow()
{
	window.close();
}
window.focus();
</script>
</head>
<body>
<table align="center" border="0" cellspacing="4" cellpadding="2" width="96%">
<form name="logout_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="action" value="bandwidth">
<input type="hidden" name="view" value="">
<tr><td colspan="2" class="smallhead" align="center"><?= $zmSlangSetNewBandwidth ?></td></tr>
<tr><td colspan="2" class="text" align="center"><?= buildSelect( "new_bandwidth", $bw_array ) ?></td></tr>
<tr><td align="center"><input type="submit" value="<?= $zmSlangSave ?>" class="form"></td>
<td align="center"><input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td></tr>
</form>
</table>
</body>
</html>
