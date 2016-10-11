<?php
//
// ZoneMinder web login view file, $Date$, $Revision$
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

xhtmlHeaders(__FILE__, translate('Login') );
?>
<!-- PP: Add recaptcha script if enabled -->
<?php 
	if (defined('ZM_OPT_USE_GOOG_RECAPTCHA') && ZM_OPT_USE_GOOG_RECAPTCHA)
	{
		echo "<head> <script src='https://www.google.com/recaptcha/api.js'></script> </head>";
	}
?>
<body>
  <div id="page">
    <div id="header">
      <h1>ZoneMinder <?php echo translate('Login') ?></h1>
    </div>
    <div id="content">
      <form name="loginForm" id="loginForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="action" value="login"/>
        <input type="hidden" name="view" value="postlogin"/>
        <input type="hidden" name="postLoginQuery" value="<?php echo $_SERVER['QUERY_STRING'] ?>">
        <table id="loginTable" class="minor" cellspacing="0">
          <tbody>
            <tr>
              <td class="colLeft"><?php echo translate('Username') ?></td>
              <td class="colRight"><input type="text" name="username" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?php echo isset($_REQUEST['username'])?validHtmlStr($_REQUEST['username']):"" ?>" size="12"/></td>
            </tr>
            <tr>
              <td class="colLeft"><?php echo translate('Password') ?></td>
              <td class="colRight"><input type="password" name="password" value="" size="12"/></td>
            </tr>
          </tbody>
        </table>
        <input type="submit" value="<?php echo translate('Login') ?>"/>
	<!-- PP: Added recaptcha widget if enabled -->
	<?php
	if (defined('ZM_OPT_USE_GOOG_RECAPTCHA') 
	    && defined('ZM_OPT_GOOG_RECAPTCHA_SITEKEY') 
	    && defined('ZM_OPT_GOOG_RECAPTCHA_SECRETKEY')
	    && ZM_OPT_USE_GOOG_RECAPTCHA && ZM_OPT_GOOG_RECAPTCHA_SITEKEY && ZM_OPT_GOOG_RECAPTCHA_SECRETKEY)
	{
		echo "<br/><br/><center> <div class='g-recaptcha'  data-sitekey='".ZM_OPT_GOOG_RECAPTCHA_SITEKEY."'></div> </center>";
	}
	?>
      </form>
    </div>
  </div>
</body>
</html>
