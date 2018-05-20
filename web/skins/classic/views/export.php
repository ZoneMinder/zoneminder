<?php
//
// ZoneMinder web export view file, $Date$, $Revision$
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

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

if ( isset($_SESSION['export']) ) {
  if ( isset($_SESSION['export']['detail']) )
    $_REQUEST['exportDetail'] = $_SESSION['export']['detail'];
  if ( isset($_SESSION['export']['frames']) )
    $_REQUEST['exportFrames'] = $_SESSION['export']['frames'];
  if ( isset($_SESSION['export']['images']) )
    $_REQUEST['exportImages'] = $_SESSION['export']['images'];
  if ( isset($_SESSION['export']['video']) )
    $_REQUEST['exportVideo'] = $_SESSION['export']['video'];
  if ( isset($_SESSION['export']['misc']) )
    $_REQUEST['exportMisc'] = $_SESSION['export']['misc'];
  if ( isset($_SESSION['export']['format']) )
    $_REQUEST['exportFormat'] = $_SESSION['export']['format'];
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Export') );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow()"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('ExportOptions') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
<?php
if ( !empty($_REQUEST['eid']) ) {
?>
        <input type="hidden" name="id" value="<?php echo validInt($_REQUEST['eid']) ?>"/>
<?php
} elseif ( !empty($_REQUEST['eids']) ) {
    foreach ( $_REQUEST['eids'] as $eid ) {
?>
        <input type="hidden" name="eids[]" value="<?php echo validInt($eid) ?>"/>
<?php
    }
    unset( $eid );
}
?>
        <table id="contentTable" class="minor">
          <tbody>
            <tr>
              <th scope="row"><?php echo translate('ExportDetails') ?></th>
              <td><input type="checkbox" name="exportDetail" value="1"<?php if ( !empty($_REQUEST['exportDetail']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ExportFrames') ?></th>
              <td><input type="checkbox" name="exportFrames" value="1"<?php if ( !empty($_REQUEST['exportFrames']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ExportImageFiles') ?></th>
              <td><input type="checkbox" name="exportImages" value="1"<?php if ( !empty($_REQUEST['exportImages']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ExportVideoFiles') ?></th>
              <td><input type="checkbox" name="exportVideo" value="1"<?php if ( !empty($_REQUEST['exportVideo']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ExportMiscFiles') ?></th>
              <td><input type="checkbox" name="exportMisc" value="1"<?php if ( !empty($_REQUEST['exportMisc']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ExportFormat') ?></th>
              <td>
                <input type="radio" id="exportFormatTar" name="exportFormat" value="tar"<?php if ( isset($_REQUEST['exportFormat']) && $_REQUEST['exportFormat'] == "tar" ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/><label for="exportFormatTar"><?php echo translate('ExportFormatTar') ?></label>
                <input type="radio" id="exportFormatZip" name="exportFormat" value="zip"<?php if ( isset($_REQUEST['exportFormat']) && $_REQUEST['exportFormat'] == "zip" ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/><label for="exportFormatZip"><?php echo translate('ExportFormatZip') ?></label>
              </td>
            </tr>
          </tbody>
        </table>
        <button type="button" id="exportButton" name="exportButton" value="Export" onclick="exportEvent(this.form);" disabled="disabled"><?php echo translate('Export') ?></button>
      </form>
    </div>
<?php
    if ( isset($_REQUEST['generated']) ) {
?>
      <h2 id="exportProgress" class="<?php echo $_REQUEST['generated']?'infoText':'errorText' ?>"><span id="exportProgressText"><?php echo $_REQUEST['generated']?translate('ExportSucceeded'):translate('ExportFailed') ?></span><span id="exportProgressTicker"></span></h2>
<?php
    } else {
?>
      <h2 id="exportProgress" class="hidden warnText"><span id="exportProgressText"><?php echo translate('Exporting') ?></span><span id="exportProgressTicker"></span></h2>
<?php
    }
    if ( !empty($_REQUEST['generated']) ) {
?>
      <h3 id="downloadLink"><a href="<?php echo validHtmlStr($_REQUEST['exportFile']) ?>"><?php echo translate('Download') ?></a></h3>
<?php
    }
?>
  </div>
</body>
</html>
