<?php
//
// ZoneMinder web function view file, $Date$, $Revision$
// Copyright (C) 2017 ZoneMinder LLC
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

if ( !canEdit('Monitors') ) {
  $view = 'error';
  return;
}

$focusWindow = true;
$navbar = getNavBarHTML();

xhtmlHeaders(__FILE__, translate('AddMonitors'));
?>
<body>
  <div id="page">
    <?php echo $navbar ?>
    <div id="content">

      <form name="contentForm" id="contentForm" method="post" action="?">
        <div style="position:relative;">
        <div id="results" style="position: absolute; top:0; right: 0; width: 50%; height: 100%;">
          <fieldset><legend>Results</legend>
            <div id="url_results">
          
            </div>
          </fieldset>
        </div>
        <div style="width:50%;position: absolute; top:0; left: 0;height: 100%;">
        <fieldset><legend>Enter by IP or URL</legend>
          <p>
          Simply enter the ip address or full url to the stream.
          It will be probed for available streams, or checked to see if it has already been entered.
          If streams are found, they will be listed in the results column. Click Add to add them.
          </p>
          <!--<input type="text" name="newMonitor[Name]" />-->
          <input type="text" name="newMonitor[Url]" oninput="probe(this);"/>
        </fieldset>
        <fieldset><legend>Import CSV Spreadsheet</legend>
            Spreadsheet should have the following format:<br/>
            <table class="major">
              <tr>
                <th>Name</th>
                <th>URL</th>
                <th>Group</th>
              </tr>
              <tr title="Example Data">
                <td>Example Name Driveway</td>
                <td>http://192.168.1.0/?action=stream</td>
                <td>MN1</td>
              </tr>
            </table>
<p>
            Defaults to apply to each monitor:<br/>
</p>
            <table><tr><th>Setting</th><th>Value</th></tr>
              <tr><td><?php echo translate('Function') ?></td><td>
<?php 
              $options = array();
              foreach ( getEnumValues('Monitors', 'Function') as $opt ) {
                $options[$opt] = translate('Fn'.$opt);
              }
              echo htmlSelect( 'newMonitor[Function]', $options, 'Mocord' );
?>
              </td></tr>
<?php
              $servers = ZM\Server::find();
              $ServersById = array();
              foreach ( $servers as $S ) {
                $ServersById[$S->Id()] = $S;
              }

              if ( count($ServersById) > 0 ) { ?>
              <tr class="Server"><td><?php echo translate('Server')?></td><td>
              <?php echo htmlSelect( 'newMonitor[ServerId]', array(''=>'Auto')+$ServersById, '' ); ?>
              </td></tr>
<?php
              }
              $storage_areas = ZM\Storage::find();
              $StorageById = array();
              foreach ( $storage_areas as $S ) {
                $StorageById[$S->Id()] = $S;
              }
              if ( count($StorageById) > 0 ) {
?>
<tr class="Storage"><td><?php echo translate('Storage')?></td><td>
<?php echo htmlSelect( 'newMonitor[StorageId]', array(''=>'All')+$StorageById, 1 ); ?>
</tr>
<?php
              }
?>
              </td></tr>
            </table>
<br/>
            <input type="file" name="import_file" id="import_file"/>
            <input type="button" value="Import" onclick="import_csv(this.form);"/>
          </div>
          </div>
        </fieldset>
      </form>
    </div>
  </div>
<?php xhtmlFooter() ?>
