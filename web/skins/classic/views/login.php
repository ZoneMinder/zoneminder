<?php
xhtmlHeaders(__FILE__, translate('Login') );
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
