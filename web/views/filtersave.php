<?php
//
// ZoneMinder web filter save view file, $Date$, $Revision$
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

if ( !canEdit( 'Events' ) )
{
    $view = "error";
    return;
}

$selectName = "filterName";
$newSelectName = "new".ucfirst($selectName);
foreach ( dbFetchAll( "select * from Filters order by Name" ) as $row )
{
    $filterNames[$row['Name']] = $row['Name'];
    if ( $_REQUEST['filterName'] == $row['Name'] )
    {
        $filterData = $row;
    }
}

$focusWindow = true;

$filter = $_REQUEST['filter'];

parseFilter( $filter );

xhtmlHeaders(__FILE__, $SLANG['SaveFilter'] );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?= $SLANG['SaveFilter'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="filter"/>
        <?= $filter['fields'] ?>
        <input type="hidden" name="sort_field" value="<?= requestVar( 'sort_field' ) ?>"/>
        <input type="hidden" name="sort_asc" value="<?= requestVar( 'sort_asc' ) ?>"/>
        <input type="hidden" name="limit" value="<?= requestVar( 'limit' ) ?>"/>
        <input type="hidden" name="autoArchive" value="<?= requestVar( 'autoArchive' ) ?>"/>
        <input type="hidden" name="autoVideo" value="<?= requestVar( 'autoVideo' ) ?>"/>
        <input type="hidden" name="autoUpload" value="<?= requestVar( 'autoUpload' ) ?>"/>
        <input type="hidden" name="autoEmail" value="<?= requestVar( 'autoEmail' ) ?>"/>
        <input type="hidden" name="autoMessage" value="<?= requestVar( 'autoMessage' ) ?>"/>
        <input type="hidden" name="autoExecute" value="<?= requestVar( 'autoExecute' ) ?>"/>
        <input type="hidden" name="autoExecuteCmd" value="<?= requestVar( 'autoExecuteCmd' ) ?>"/>
        <input type="hidden" name="autoDelete" value="<?= requestVar( 'autoDelete' ) ?>"/>
<?php if ( count($filterNames) ) { ?>
        <p>
          <label for="<?= $selectName ?>"><?= $SLANG['SaveAs'] ?></label><?= buildSelect( $selectName, $filterNames ); ?><label for="<?= $newSelectName ?>"><?= $SLANG['OrEnterNewName'] ?></label><input type="text" size="32" id="<?= $newSelectName ?>" name="<?= $newSelectName ?>" value="<?= requestVar('filterName') ?>"/>
        </p>
<?php } else { ?>
        <p>
          <label for="<?= $newSelectName ?>"><?= $SLANG['EnterNewFilterName'] ?></label><input type="text" size="32" id="<?= $newSelectName ?>" name="<?= $newSelectName ?>" value="">
        </p>
<?php } ?>
        <p>
          <label for="background"><?= $SLANG['BackgroundFilter'] ?></label><input type="checkbox" id="background" name="background" value="1"<?php if ( !empty($filterData['Background']) ) { ?> checked="checked"<?php } ?>/>
        </p>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Save'] ?>"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
