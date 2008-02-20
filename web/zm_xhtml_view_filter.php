<?php
//
// ZoneMinder web filter view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}
$filter_names = array();
$sql = "select * from Filters order by Name";
foreach( dbFetchAll( $sql ) as $row )
{
	$filter_names[$row['Name']] = $row['Name'];
}

noCacheHeaders();
header("Content-type: application/xhtml+xml" );
echo( '<?xml version="1.0" encoding="iso-8859-1"?>'."\n" );
?>
<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangEventFilter ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css"/>
</head>
<body>
<form method="post" action="<?= $PHP_SELF ?>">
<div style="visibility: hidden">
<fieldset>
<input type="hidden" name="view" value="events"/>
</fieldset>
</div>
<table>
<?php
if ( count($filter_names) > 0 )
{
?>
<tr>
<td align="center"><?= $zmSlangUseFilter ?>:&nbsp;<?= buildSelect( "filter_name", $filter_names ); ?></td>
</tr>
<tr>
<td align="center"><input type="submit" value="<?= $zmSlangSubmit ?>"/></td>
</tr>
<?php
}
else
{
?>
<tr>
<td align="center"><?= $zmSlangNoSavedFilters ?></td>
</tr>
<?php
}
?>
</table>
</form>
</body>
</html>
