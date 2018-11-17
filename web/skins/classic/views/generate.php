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

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Generate') );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow()"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo translate('Generate') ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
<?php
if ( !empty($_REQUEST['eids']) ) {
    foreach ( $_REQUEST['eids'] as $eid ) {
?>
        <input type="hidden" name="eids[]" value="<?php echo validInt($eid) ?>"/>
<?php
    }
    echo "Generating " . count($_REQUEST['eids']) . ' event(s).';
} else {
    echo '<div class="warning">There are no events found.</div>';
}
?>
        <table id="contentTable" class="minor" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row">Encoder </th>
              <td>
                <input type="radio" id="generateEncoderNone" name="generateEncoder" value="none"/>
                <label for="generateEncoderNone">Uncompressed</label>
              </td>
              <td>
                <input type="radio" id="generateEncoderx264" name="generateEncoder" value="x264" checked="checked"/>
                <label for="generateEncoderx264">x264</label>
              </td>
              <td>
                <input type="radio" id="generateEncodermpeg2" name="generateEncoder" value="mpeg2"/>
                <label for="generateEncodermpeg2">MPEG-2</label>
              </td>
            </tr>
            <tr>
              <th scope="row">Framerate</th>
              <td>
                 <select name="generateFramerate">
                   <option value="1">1/4x</option>
                   <option value="2">1/2x</option>
                   <option value="4" selected>Real</option>
                   <option value="8">2x</option>
                   <option value="16">4x</option>
                   <option value="32">8x</option>
                 </select>
              </td>
            </tr>
            <tr>
              <th scope="row">Size</th>
              <td>
                 <select name="generateSize">
                   <option value="1" selected>Actual</option>
                   <option value="0.75">3/4</option>
                   <option value="0.5">1/2</option>
                   <option value="0.25">1/4</option>
                   <option value="0.125">1/8</option>
                 </select>
              </td>
            </tr>
          </tbody>
        </table>
        <input type="button" id="generateButton" name="generateButton" value="<?php echo translate('GenerateVideo') ?>" />
      </form>
      <br>
      <label for="result">Output</label><br>
      <textarea rows="8" cols="100" id="result"></textarea>
    </div>
  </div>
</body>
</html>
