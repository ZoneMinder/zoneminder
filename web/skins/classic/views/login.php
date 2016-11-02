<?php
xhtmlHeaders(__FILE__, translate('Login') );
?>
<body>
	<div class="container">
		<form class="center-block" name="loginForm" id="loginForm" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
			<input type="hidden" name="action" value="login"/>
			<input type="hidden" name="view" value="postlogin"/>
			<input type="hidden" name="postLoginQuery" value="<?php echo $_SERVER['QUERY_STRING'] ?>">

			<h1><?php echo translate('Login') ?></h1>

			<label for="inputEmail" class="sr-only"><?php echo translate('Username') ?></label>
			<input type="text" name="username" class="form-control" placeholder="Username" required autofocus />

			<label for="inputPassword" class="sr-only"><?php echo translate('Password') ?></label>
			<input type="password" name="password" value="" size="12" class="form-control" placeholder="Password" required />

			<?php
			if (defined('ZM_OPT_USE_GOOG_RECAPTCHA') 
			&& defined('ZM_OPT_GOOG_RECAPTCHA_SITEKEY') 
			&& defined('ZM_OPT_GOOG_RECAPTCHA_SECRETKEY')
			&& ZM_OPT_USE_GOOG_RECAPTCHA && ZM_OPT_GOOG_RECAPTCHA_SITEKEY && ZM_OPT_GOOG_RECAPTCHA_SECRETKEY)
			{
			echo "<div class='g-recaptcha'  data-sitekey='".ZM_OPT_GOOG_RECAPTCHA_SITEKEY."'></div>";
			} ?>

			<input class="btn btn-lg btn-primary btn-block" type="submit" value="<?php echo translate('Login') ?>"/>
		</form>
	</div>
</body>
</html>
