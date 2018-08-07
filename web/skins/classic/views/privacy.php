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
        <h6><?php echo translate('PrivacyAbout') ?></h6>
        <p><?php echo translate('PrivacyAboutText') ?></p>
        <br>

        <h6><?php echo translate('PrivacyContact') ?></h6>
        <p><?php echo translate('PrivacyContactText') ?></p>
        <br>

        <h6><?php echo translate('PrivacyCookies') ?></h6>
        <p><?php echo translate('PrivacyCookiesText') ?></p>
        <br>

        <h6><?php echo translate('PrivacyTelemetry') ?></h6>
        <p><?php echo translate('PrivacyTelemetryText') ?></p>
        <br>

        <p><?php echo translate('PrivacyTelemetryList') ?></p>
        <p><?php echo translate('PrivacyMonitorList') ?></p>
        <p><?php echo translate('PrivacyConclusionText') ?></p>
        <p><?php echo buildSelect( "option", $options ); ?></p>

        <div id="contentButtons">
          <input type="submit" value="<?php echo translate('Apply') ?>" onclick="submitForm( this )">
        </div>
      </form>
    </div>
  </div>
</body>
</html>
