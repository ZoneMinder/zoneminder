<?php
//
// ZoneMinder web filter view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
    $_REQUEST['view'] = "error";
    return;
}

$filterNames = array();
$sql = "select * from Filters order by Name";
foreach( dbFetchAll( $sql ) as $row )
{
    $filterNames[$row['Name']] = $row['Name'];
}

xhtmlHeaders( __FILE__, $SLANG['EventFilter'] );
?>
<body>
  <div id="page">
    <div id="header">
    </div>
    <div id="content">
      <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <div class="hidden">
          <fieldset>
            <input type="hidden" name="view" value="events"/>
            <input type="hidden" name="page" value="1"/>
          </fieldset>
        </div>
<?php
if ( count($filterNames) > 0 )
{
?>
        <div>
          <label for="filterName"><?= $SLANG['UseFilter'] ?></label><?= buildSelect( "filterName", $filterNames ); ?>
        </div>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Submit'] ?>"/>
        </div>
<?php
}
else
{
?>
        <p><?= $SLANG['NoSavedFilters'] ?></p>
<?php
}
?>
      </form>
    </div>
  </div>
</body>
</html>
