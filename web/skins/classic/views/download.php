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

if (isset($_SESSION['montageReviewFilter'])) { //Handles montageReview filter
  $eventsSql = 'SELECT E.Id FROM Events as E WHERE 1';
  $eventsSql .= $_SESSION['montageReviewFilter']['sql'];
  $results = dbQuery($eventsSql);
  $eids = [];
  while ( $event_row = dbFetchNext( $results ) ) {
    array_push($eids, 'eids[]='.$event_row['Id']);
  }
  $_REQUEST['eids'] = $eids;
  session_start();
  unset($_SESSION['montageReviewFilter']);
  session_write_close();
}

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Download') );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow()"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Download') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
<?php
if ( !empty($_REQUEST['eid']) ) {
?>
        <input type="hidden" name="id" value="<?php echo validInt($_REQUEST['eid']) ?>"/>
<?php
} else if ( !empty($_REQUEST['eids']) ) {
    foreach ( $_REQUEST['eids'] as $eid ) {
?>
        <input type="hidden" name="eids[]" value="<?php echo validInt($eid) ?>"/>
<?php
    }
    unset( $eid );
}
?>
        <table id="contentTable" class="minor" cellspacing="0">
          <tbody>
            <tr>
              <td><input type="hidden" name="exportVideo" value="1"/></td>
            </tr>
            <tr>
              <th scope="row"><?php echo translate('ExportFormat') ?></th>
              <td>
                <input type="radio" id="exportFormatTar" name="exportFormat" value="tar" onclick="configureExportButton(this)"/>
                <label for="exportFormatTar"><?php echo translate('ExportFormatTar') ?></label>
                <input type="radio" id="exportFormatZip" name="exportFormat" value="zip" checked="checked" onclick="configureExportButton(this);"/>
                <label for="exportFormatZip"><?php echo translate('ExportFormatZip') ?></label>
              </td>
            </tr>
          </tbody>
        </table>
        <input type="button" id="exportButton" name="exportButton" value="<?php echo translate('GenerateDownload') ?>" onclick="exportEvent(this.form);" />
      </form>
    </div>
<?php
    if ( isset($_REQUEST['generated']) ) {
?>
      <h2 id="exportProgress" class="<?php echo $_REQUEST['generated']?'infoText':'errorText' ?>">
        <span id="exportProgressText"><?php echo $_REQUEST['generated']?translate('ExportSucceeded'):translate('ExportFailed') ?></span>
        <span id="exportProgressTicker"></span>
      </h2>
<?php
    } else {
?>
      <h2 id="exportProgress" class="hidden warnText">
        <span id="exportProgressText"><?php echo translate('Exporting') ?></span>
        <span id="exportProgressTicker"></span>
      </h2>
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
