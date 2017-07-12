<?php
//
// ZoneMinder web null view file, $Date$, $Revision$
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

$skinJsPhpFile = getSkinFile( 'js/skin.js.php' );
$skinJsFile = getSkinFile( 'js/skin.js' );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php echo ZM_WEB_TITLE_PREFIX ?></title>
  <script type="text/javascript">
<?php
require_once( $skinJsPhpFile );
?>
  </script>

  <script type="text/javascript" src="<?php echo $skinJsFile ?>"></script>
  <script type="text/javascript">
<?php
if ( !$debug ) {
?>
    closeWindow();
<?php
}
?>
  </script>
</head>
<body>
</body>
</html>
