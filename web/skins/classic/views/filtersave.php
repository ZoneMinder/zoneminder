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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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

xhtmlHeaders(__FILE__, translate('SaveFilter') );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('SaveFilter') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="filter"/>
        <?php echo $filter['fields'] ?>
        <input type="hidden" name="sort_field" value="<?php echo requestVar( 'sort_field' ) ?>"/>
        <input type="hidden" name="sort_asc" value="<?php echo requestVar( 'sort_asc' ) ?>"/>
        <input type="hidden" name="limit" value="<?php echo requestVar( 'limit' ) ?>"/>
        <input type="hidden" name="AutoArchive" value="<?php echo requestVar( 'AutoArchive' ) ?>"/>
        <input type="hidden" name="AutoVideo" value="<?php echo requestVar( 'AutoVideo' ) ?>"/>
        <input type="hidden" name="AutoUpload" value="<?php echo requestVar( 'AutoUpload' ) ?>"/>
        <input type="hidden" name="AutoEmail" value="<?php echo requestVar( 'AutoEmail' ) ?>"/>
        <input type="hidden" name="AutoMessage" value="<?php echo requestVar( 'AutoMessage' ) ?>"/>
        <input type="hidden" name="AutoExecute" value="<?php echo requestVar( 'AutoExecute' ) ?>"/>
        <input type="hidden" name="AutoExecuteCmd" value="<?php echo requestVar( 'AutoExecuteCmd' ) ?>"/>
        <input type="hidden" name="AutoDelete" value="<?php echo requestVar( 'AutoDelete' ) ?>"/>
<?php if ( count($filterNames) ) { ?>
        <p>
          <label for="<?php echo $selectName ?>"><?php echo translate('SaveAs') ?></label><?php echo buildSelect( $selectName, $filterNames ); ?><label for="<?php echo $newSelectName ?>"><?php echo translate('OrEnterNewName') ?></label><input type="text" size="32" id="<?php echo $newSelectName ?>" name="<?php echo $newSelectName ?>" value="<?php echo requestVar('filterName') ?>"/>
        </p>
<?php } else { ?>
        <p>
          <label for="<?php echo $newSelectName ?>"><?php echo translate('EnterNewFilterName') ?></label><input type="text" size="32" id="<?php echo $newSelectName ?>" name="<?php echo $newSelectName ?>" value="">
        </p>
<?php } ?>
        <p>
          <label for="background"><?php echo translate('BackgroundFilter') ?></label><input type="checkbox" id="background" name="background" value="1"<?php if ( !empty($filterData['Background']) ) { ?> checked="checked"<?php } ?>/>
        </p>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Save') ?>"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/><input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow();"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
