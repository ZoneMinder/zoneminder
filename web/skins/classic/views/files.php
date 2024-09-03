<?php
//
// ZoneMinder web files view
// Copyright (C) 2022 ZoneMinder Inc.
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

if (!canView('Events')) {
  $view = 'error';
  return;
}

$path = (!empty($_REQUEST['path'])) ? $_REQUEST['path'] : ZM_DIR_EVENTS;
$is_ok_path = false;
foreach (ZM\Storage::find() as $storage) {
  $rc = strstr($path, $storage->Path(), true);
  ZM\Debug("rc from strstr ($rc) $path ".$storage->Path());
  if ((false !== $rc) and ($rc == '')) {
    # Must be at the beginning
    $is_ok_path = true;
  }
}
$path_parts = pathinfo($path);

if (@is_file($path)) {
  if (output_file($path))
    return;
  $path = $path_parts['dirname'];
}
$show_hidden = isset($_REQUEST['show_hidden']) ? $_REQUEST['show_hidden'] : 0;

function guess_material_icon($file) {
  $path_parts = pathinfo($file);
  if (!isset($path_parts['extension'])) {
    return 'note';
  } else if ( $path_parts['extension'] == 'mp4' or $path_parts['extension'] == 'webm') {
    return 'video_file';
  } else if ($path_parts['extension'] == 'jpg') {
    return 'image';
  } else if ($path_parts['extension'] == 'zip') {
    return 'folder_zip';
  }
}

xhtmlHeaders(__FILE__, translate('Files'));
?>
<body>
  <div id="page">
    <?php echo $navbar = getNavBarHTML(); ?>
    <div id="content">
      <form id="filesForm" name="filesForm" method="post" action="?view=files&path=<?php echo urlencode($path); ?>">
<?php
if (!$is_ok_path) {
  echo '<div class="error">Path is not valid. Path must be below a designated Storage area.<br/></div>';
} else {
$exploded = explode('/', $path);
ZM\Debug(print_r($exploded, true));
$array = array();
$parent = '';
for ($i = 0; $i < count($exploded); $i++) {
  if ($exploded[$i])
  $parent = $parent . '/' . $exploded[$i];
  $parent_enc = urlencode($parent);
  $array[] = "<a href='?view=files&amp;path={$parent_enc}'>" . validHtmlStr($exploded[$i]) . '</a>';
}
array_pop($exploded);

$parent = implode('/', $exploded);
$sep = '<i class="bread-crumb"> / </i>';
echo implode($sep, $array).'<br/>';
?>
        <table id="contentTable" class="major">
          <thead class="thead-highlight">
            <tr>
              <th class="colSelect"><input type="checkbox" name="toggleCheck" value="1" data-checkbox-name="files[]" data-on-click-this="updateFormCheckboxesByName"></th>
              <th class="colName"><?php echo translate('Filename') ?></th>
            </tr>
          </thead>
          <tbody>
<?php
$files = array();
$folders = array();
$entries = is_readable($path) ? scandir($path) : array();
foreach ($entries as $file) {
  if ($file == '.' || $file == '..') {
    continue;
  }
  if (!$show_hidden && substr($file, 0, 1) === '.') {
    continue;
  }
  $full_path = $path.'/'.$file;
  if (@is_file($full_path)) {
    $files[] = $file;
  } else if (@is_dir($full_path)) {
    $folders[] = $file;
  }
}
natcasesort($files);
natcasesort($folders);
if ($parent != '') {
  echo '
<tr>
  <td class="colSelect"></td>
  <td><span class="material-icons md-18">folder</span><a href="?view=files&amp;path='.urlencode($parent).'">..</a></td>
</tr>';
}
foreach ($folders as $folder) {
  $url = urlencode($path.'/'.$folder);
  echo '
<tr>
  <td class="colSelect"><input type="checkbox" name="files[]" value="'.validHtmlStr($folder).'"/></td>
  <td><span class="material-icons md-18">folder</span><a href="?view=files&amp;path='.$url.'">'.validHtmlStr($folder).'</a></td>
</tr>';
}
foreach ($files as $file) {
  $url = urlencode($path.'/'.$file);
  echo '<tr><td class="colSelect"><input type="checkbox" name="files[]" value="'.validHtmlStr($file).'"/></td>
     <td><span class="material-icons md-18">'.guess_material_icon($file).'</span><a href="?view=files&amp;path='.$url.'">'.validHtmlStr($file).'</a></td>
     </tr>';
}

?>
          </tbody>
        </table>
<?php 
} # end if is_ok_path
?>
        <div id="contentButtons">
          <button type="submit" name="action" value="delete" disabled="disabled">
          <?php echo translate('Delete') ?>
          </button>
        </div>
      </form>
    </div>
  </div>
<?php xhtmlFooter() ?>
