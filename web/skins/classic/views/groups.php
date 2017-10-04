<?php
//
// ZoneMinder web monitor groups file, $Date$, $Revision$
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

if ( !canView( 'Groups' ) ) {
  $view = 'error';
  return;
}

  # This will end up with the group_id of the deepest selection
$group_id = 0;
$max_depth = 0;


$Groups = array();
foreach ( Group::find_all( ) as $Group ) {
  $Groups[$Group->Id()] = $Group;
}

# This  array is indexed by parent_id
$children = array();
foreach ( $Groups as $id=>$Group ) {
  if ( ! isset( $children[$Group->ParentId()] ) )
    $children[$Group->ParentId()] = array();
  $children[$Group->ParentId()][] = $Group;
  if ( $max_depth < $Group->depth() )
    $max_depth = $Group->depth();
}
Warning("Max depth $max_depth");
xhtmlHeaders(__FILE__, translate('Groups') );
?>
<body>
  <div id="page">
    <?php echo $navbar = getNavBarHTML(); ?>
    <div id="content">
      <form name="groupsForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="setgroup"/>
        <table id="contentTable" class="major">
          <thead>
            <tr>
              <th class="colName" colspan="<?php echo $max_depth ?>"><?php echo translate('Name') ?></th>
              <th class="colIds"><?php echo translate('Monitors') ?></th>
              <th class="colSelect"><?php echo translate('Mark') ?></th>
            </tr>
          </thead>
          <tbody>
<?php
function group_line( $Group ) {
  global $children;
  global $max_depth;
  $html = '<tr>';
  for ( $i = 1; $i<$Group->depth(); $i+=1 )
    $html .= '<td class="colName">&nbsp;</td>';
  $html .= '<td class="colName" colspan="'.($max_depth-($Group->depth()-1)).'">';
  if ( canEdit('Groups') ) {
    $html .= '<a href="#" onclick="editGroup('.$Group->Id().');">'. validHtmlStr($Group->Name()).'</a>';
  } else {
    $html .= validHtmlStr($Group->Name());
  }
  $html .= '</td><td class="colIds">'. monitorIdsToNames( $Group->MonitorIds(), 30 ).'</td>
                <td class="colSelect"><input type="checkbox" name="gid" value="'. $Group->Id() .'" onclick="configureButtons(this);"/></td>
              </tr>
  ';
  if ( isset( $children[$Group->Id()] ) ) {
    foreach ( $children[$Group->Id()] as $G ) {
      $html .= group_line( $G );
    }
  }
  return $html;
}
foreach ( $children[null] as $Group )
  echo group_line( $Group );
?>
          </tbody>
        </table>
        <div id="contentButtons">
          <input type="button" value="<?php echo translate('New') ?>" onclick="newGroup();"<?php echo canEdit('Groups')?'':' disabled="disabled"' ?>/>
          <input type="button" name="deleteBtn" value="<?php echo translate('Delete') ?>" onclick="deleteGroup(this);" disabled="disabled"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
