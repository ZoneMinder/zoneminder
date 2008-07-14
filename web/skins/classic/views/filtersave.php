<?php
//
// ZoneMinder web filter save view file, $Date$, $Revision$
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

if ( !canEdit( 'Events' ) )
{
    $_REQUEST['view'] = "error";
    return;
}

$selectName = "filterName";
foreach ( dbFetchAll( "select * from Filters order by Name" ) as $row )
{
    $filterNames[$row['Name']] = $row['Name'];
    if ( $filterName == $row['Name'] )
    {
        $filter_data = $row;
    }
}

$focusWindow = true;

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
        <input type="hidden" name="sort_field" value="<?= $sort_field ?>"/>
        <input type="hidden" name="sort_asc" value="<?= $sort_asc ?>"/>
        <input type="hidden" name="limit" value="<?= $limit ?>"/>
        <input type="hidden" name="auto_archive" value="<?= $auto_archive ?>"/>
        <input type="hidden" name="auto_video" value="<?= $auto_video ?>"/>
        <input type="hidden" name="auto_upload" value="<?= $auto_upload ?>"/>
        <input type="hidden" name="auto_email" value="<?= $auto_email ?>"/>
        <input type="hidden" name="auto_message" value="<?= $auto_message ?>"/>
        <input type="hidden" name="auto_execute" value="<?= $auto_execute ?>"/>
        <input type="hidden" name="auto_execute_cmd" value="<?= $auto_execute_cmd ?>"/>
        <input type="hidden" name="auto_delete" value="<?= $auto_delete ?>"/>
<?php if ( count($filterNames) ) { ?>
        <p>
          <label for="<?= $selectName ?>"><?= $SLANG['SaveAs'] ?></label><?= buildSelect( $selectName, $filterNames ); ?><label for="new_<?= $selectName ?>"><?= $SLANG['OrEnterNewName'] ?></label><input type="text" size="32" name="new_<?= $selectName ?>" value="<?= $filterName ?>">
        </p>
<?php } else { ?>
        <p>
          <label for="new_<?= $selectName ?>"><?= $SLANG['EnterNewFilterName'] ?></label><input type="text" size="32" name="new_<?= $selectName ?>" value="">
        </p>
<?php } ?>
        <p>
          <label for="background"><?= $SLANG['BackgroundFilter'] ?></label><input type="checkbox" name="background" value="1"<?php if ( $filter_data['Background'] ) { ?> checked="checked"<?php } ?>/>
        </p>
        <div id="contentButtons">
          <input type="submit" value="<?= $SLANG['Save'] ?>"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow();"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
