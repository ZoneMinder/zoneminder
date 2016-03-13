<?php
xhtmlHeaders(__FILE__, translate('Login') );
?>
<body>
<style>
body {
	background-color: #f8f8f8;
}

input[type="text"] {
	margin-bottom: -1px;
	border-bottom-right-radius: 0;
	border-bottom-left-radius: 0;
}

input[type="password"] {
	margin-bottom: 10px;
	border-top-left-radius: 0;
	border-top-right-radius: 0;
}

input[type="submit"] {
	margin-top: 20px;
}

form {
	max-width: 450px;
	padding: 40px 60px;
	margin: 15px auto;
	border: 1px solid #e7e7e7;
	background-color: #fff;
	box-shadow: 0 0 6px 0 rgba(0,0,0,0.08);
}

.form-control {
	height: 54px;
}

h1 {
	font-size: 250%;
	margin-top: 0;
	margin-bottom: 15px;
}

</style>
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
