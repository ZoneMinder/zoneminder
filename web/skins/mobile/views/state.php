<?php
//
// ZoneMinder web run state view file, $Date$, $Revision$
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

if ( !canEdit( 'System' ) )
{
    $view = "error";
    return;
}
$running = daemonCheck();

$states = dbFetchAll( "select * from States" );

xhtmlHeaders( __FILE__, $SLANG['State'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['RunState'] ?></h2>
    </div>
    <div id="content">
      <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <div class="hidden">
          <fieldset>
            <input type="hidden" name="view" value="console"/>
            <input type="hidden" name="action" value="state"/>
          </fieldset>
        </div>
        <select name="runState">
<?php
if ( $running )
{
?>
          <option value="stop" selected="selected"><?= $SLANG['Stop'] ?></option>
          <option value="restart"><?= $SLANG['Restart'] ?></option>
<?php
}
else
{
?>
          <option value="start" selected="selected"><?= $SLANG['Start'] ?></option>
<?php
}
?>
<?php
foreach ( $states as $state )
{
?>
          <option value="<?= $state['Name'] ?>"><?= $state['Name'] ?></option>
<?php
}
?>
        </select>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Apply'] ?>"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
