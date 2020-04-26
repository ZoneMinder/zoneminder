<?php
xhtmlHeaders(__FILE__, translate('Login'));
?>
<body>
<?php echo getNavBarHTML(); ?>
	<div class="container">
		<form class="center-block" name="loginForm" id="loginForm" method="post" action="?">
			<input type="hidden" name="action" value="login"/>
			<input type="hidden" name="view" value="login"/>

			<div id="loginError" class="hidden alarm" role="alert">
				<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
				Invalid username or password.
			</div>

			<div id="loginform">

        <h1><i class="material-icons md-36">account_circle</i> <?php echo validHtmlStr(ZM_WEB_TITLE) . ' ' . translate('Login') ?></h1>
	
				<label for="inputUsername" class="sr-only"><?php echo translate('Username') ?></label>
				<input type="text" id="inputUsername" name="username" class="form-control" autocapitalize="none" placeholder="Username" required autofocus autocomplete="username"/>
	
				<label for="inputPassword" class="sr-only"><?php echo translate('Password') ?></label>
				<input type="password" id="inputPassword" name="password" class="form-control" placeholder="Password" required autocomplete="current-password"/>
<?php
if (
  defined('ZM_OPT_USE_GOOG_RECAPTCHA') 
  && defined('ZM_OPT_GOOG_RECAPTCHA_SITEKEY') 
  && defined('ZM_OPT_GOOG_RECAPTCHA_SECRETKEY')
  && ZM_OPT_USE_GOOG_RECAPTCHA && ZM_OPT_GOOG_RECAPTCHA_SITEKEY && ZM_OPT_GOOG_RECAPTCHA_SECRETKEY
) {
  echo '<div class="g-recaptcha" data-sitekey="'.ZM_OPT_GOOG_RECAPTCHA_SITEKEY.'"></div>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>';
} ?>
				<button class="btn btn-lg btn-primary btn-block" type="submit"><?php echo translate('Login') ?></button>
			</div>
		</form>
	</div>
</body>
</html>
