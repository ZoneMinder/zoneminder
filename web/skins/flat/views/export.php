<?php
//
// ZoneMinder web export view file, $Date: 2008-09-26 10:47:20 +0100 (Fri, 26 Sep 2008) $, $Revision: 2632 $
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
    $view = "error";
    return;
}

if ( isset($_SESSION['export']) )
{
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

xhtmlHeaders(__FILE__, $SLANG['Export'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow()"><?= $SLANG['Close'] ?></a>
      </div>
      <h2><?= $SLANG['ExportOptions'] ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
<?php
if ( !empty($_REQUEST['eid']) )
{
?>
        <input type="hidden" name="id" value="<?= validInt($_REQUEST['eid']) ?>"/>
<?php
}
elseif ( !empty($_REQUEST['eids']) )
{
    foreach ( $_REQUEST['eids'] as $eid )
    {
?>
        <input type="hidden" name="eids[]" value="<?= validInt($eid) ?>"/>
<?php
    }
    unset( $eid );
}
?>
        <table id="contentTable" class="minor" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?= $SLANG['ExportDetails'] ?></th>
              <td><input type="checkbox" name="exportDetail" value="1"<?php if ( !empty($_REQUEST['exportDetail']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['ExportFrames'] ?></th>
              <td><input type="checkbox" name="exportFrames" value="1"<?php if ( !empty($_REQUEST['exportFrames']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['ExportImageFiles'] ?></th>
              <td><input type="checkbox" name="exportImages" value="1"<?php if ( !empty($_REQUEST['exportImages']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['ExportVideoFiles'] ?></th>
              <td><input type="checkbox" name="exportVideo" value="1"<?php if ( !empty($_REQUEST['exportVideo']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['ExportMiscFiles'] ?></th>
              <td><input type="checkbox" name="exportMisc" value="1"<?php if ( !empty($_REQUEST['exportMisc']) ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['ExportFormat'] ?></th>
              <td>
                <input type="radio" id="exportFormatTar" name="exportFormat" value="tar"<?php if ( isset($_REQUEST['exportFormat']) && $_REQUEST['exportFormat'] == "tar" ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/><label for="exportFormatTar"><?= $SLANG['ExportFormatTar'] ?></label>
                <input type="radio" id="exportFormatZip" name="exportFormat" value="zip"<?php if ( isset($_REQUEST['exportFormat']) && $_REQUEST['exportFormat'] == "zip" ) { ?> checked="checked"<?php } ?> onclick="configureExportButton( this )"/><label for="exportFormatZip"><?= $SLANG['ExportFormatZip'] ?></label>
              </td>
            </tr>
          </tbody>
        </table>
        <input type="button" id="exportButton" name="exportButton" value="<?= $SLANG['Export'] ?>" onclick="exportEvent( this.form );" disabled="disabled"/>
      </form>
    </div>
<?php
    if ( isset($_REQUEST['generated']) )
    {
?>
      <h2 id="exportProgress" class="<?= $_REQUEST['generated']?'infoText':'errorText' ?>"><span id="exportProgressText"><?= $_REQUEST['generated']?$SLANG['ExportSucceeded']:$SLANG['ExportFailed'] ?></span><span id="exportProgressTicker"></span></h2>
<?php
    }
    else
    {
?>
      <h2 id="exportProgress" class="hidden warnText"><span id="exportProgressText"><?= $SLANG['Exporting'] ?></span><span id="exportProgressTicker"></span></h2>
<?php
    }
    if ( !empty($_REQUEST['generated']) )
    {
?>
      <h3 id="downloadLink"><a href="<?= validHtmlStr($_REQUEST['exportFile']) ?>"><?= $SLANG['Download'] ?></a></h3>
<?php
    }
?>
  </div>
</body>
</html>
