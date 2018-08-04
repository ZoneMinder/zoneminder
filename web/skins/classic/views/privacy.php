<?php
//
// ZoneMinder privacy view file, $Date$, $Revision$
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

if ( !canEdit( 'System' ) )
{
    $view = "error";
    return;
}

$options = array( 
    "accept"      => translate('Accept'),
    "decline"    => translate('Decline'),
);

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Privacy') );
?>
<body>
  <div id="page">
    <div id="header">
      <h2><?php echo translate('Privacy') ?></h2>
      <h1>ZoneMinder - <?php echo translate('Privacy') ?></h1>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="privacy"/>
        <p>
          <p><u>About</u></p>
          <p>Since 2002, ZoneMinder has been the premier free and open-source Video Management System (VMS) solution for Linux platforms. ZoneMinder is supported by the community and is managed by those who choose to volunteer their spare time to the project. The best way to improve ZoneMinder is to get involved.</p>
          <p><br/>

          </p>
          <p><u>Contact</u></p>
          <p>There are three primary ways to engage with the community:</p>
          <p>    • The user forum at forums.zoneminder.com</p>
          <p>    • The ZoneMinder Slack channel</p>
          <p>    • A github forum exists for bug reporting only</p>
          <p><br/>

          </p>
          <p><u>Cookies</u></p>
          <p>Whether you use a web browser or a mobile app to communicate with the ZoneMinder server, a ZMSESSID cookie is created on the client to uniquely identify a session with the ZoneMinder server. ZmCSS and zmSkin cookies are created to remember your style and skin choices. 
          </p>
          <p><br/>

          </p>
          <p><u>Telemetry</u></p>
          <p>Because ZoneMinder is open-source, anyone can install it without registering. This makes it difficult to  answer questions such as: how many systems are out there, what is the largest system out there, what kind of systems are our there, or where are these systems located? Knowing the answers to these questions, helps new users who ask these questions, and it helps us set priorities based on the majority user base.  
          </p>
          <p><br/>

          </p>
          <p>The ZoneMinder Telemetry daemon collects the following data about your system:</p>
          <p>    • A unique identifier</p>
          <p>    • City based location including city, region, country, latitude, and longitude</p>
          <p>    • Current time</p>
          <p>    • Total number of monitors</p>
          <p>    • Total number of events</p>
          <p>    • System architecture</p>
          <p>    • Operating system kernel, distro, and distro version</p>
          <p>    • Version of ZoneMinder</p>
          <p>    • Total amount of memory</p>
          <p>    • Number of cpu cores</p>
          <p>    • The following configuration parameters from each monitor are collected:</p>
          <p>        ◦ Id</p>
          <p>        ◦ Name</p>
          <p>        ◦ Type</p>
          <p>        ◦ Function</p>
          <p>        ◦ Width</p>
          <p>        ◦ Height</p>
          <p>        ◦ Colours</p>
          <p>        ◦ MaxFPS</p>
          <p>        ◦ AlarmMaxFPS</p>
          <p><br/>

          </p>
          <p>We are <u>NOT</u> collecting any image specific data from your cameras. We don’t know what your cameras are watching. This data will not be sold or used for any purpose not stated herein.</p>
          <p><br/>

          </p>
          <p>By clicking accept, you agree to send us this data to help make ZoneMinder a better product. By clicking decline, you can still freely use ZoneMinder and all its features.</p>
        </p>
        <p>
          <?php echo buildSelect( "option", $options ); ?>
        </p>
        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Apply') ?>" onclick="submitForm( this )">
          <input type="button" value="<?php echo translate('Cancel') ?>" onclick="closeWindow()">
        </div>
      </form>
    </div>
  </div>
</body>
</html>
