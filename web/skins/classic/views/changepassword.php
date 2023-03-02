<?php
global $error_message;
global $user;
xhtmlHeaders(__FILE__, translate('Change Password'));
?>
<body>
<?php echo getNavBarHTML();?>
	<div class="container">
		<form class="center-block" name="loginForm" id="loginForm" method="post" action="?view=changepassword">
<?php 
if (!$user) {
  $error_message .= 'Not logged in. Unable to change password.';
?>
  <div id="loginform">
    <label for="inputUsername" class="sr-only"><?php echo translate('Username') ?></label>
    <input type="text" id="inputUsername" name="username" class="form-control" autocapitalize="none" placeholder="Username" required autofocus autocomplete="username"/>
    <button class="btn btn-lg btn-primary btn-block" type="submit" name="action" value="forgotpassword">
    <?php echo translate('Forgot Password') ?>
    </button>
  </div>

} else {
  if (empty($_SESSION['Username'])) {
    # Generate and use magic link
    require_once('includes/MagicLink.php');
    $link = new ZM\MagicLink();
    $link->UserId($user['Id']);
    if (!$link->GenerateToken()) {
      $error_message .= 'There was a system error generating the magic link. Please contact support.<br/>';
      return;
    } else if (!$link->save()) {
      $error_message .= 'There was a system error generating the magic link. Please contact support.<br/>';
    } else {
      echo '<input type="hidden" name="user_id" value="'.$user['Id'].'" />'.PHP_EOL;
      echo '<input type="hidden" name="magic" value="'.$link->Token().'" />'.PHP_EOL;
    }
  }
}
if ($error_message) {
  echo '<div id="error">'.$error_message.'</div>';
}
if ($user) {
?>
			<div>
        <h1><i class="material-icons md-36">account_circle</i> <?php echo validHtmlStr(ZM_WEB_TITLE) . ' ' . translate('Change Password') ?></h1>
        <p>
        Please enter a new password for your account
        </p>
	
				<label for="newPassword" class="sr-only"><?php echo translate('New Password') ?></label>
				<input type="password" id="newPassword" name="password" class="form-control" placeholder="New Password" required autofocus autocomplete="new-password"//>
	
				<label for="confirmPassword" class="sr-only"><?php echo translate('Confirm Password') ?></label>
				<input type="password" id="confirmPassword" name="confirmPassword" class="form-control" placeholder="Confirm Password" autocomplete="new-password"/>
        <button class="btn btn-lg btn-primary btn-block" type="submit" name="action" value="changepassword">
        <?php echo translate('Save') ?>
        </button>
			</div>
<?php 
}
?>
		</form>
	</div>
<?php
if ($action == 'changepassword' and !$error_message) {
?>
<script>
// Redirect to the requested ZoneMinder url after the user logs in
function postLoginRedirect() {
  console.log('Current location: ' + window.location);
  console.log('Redirecting to (' + redirectSuffix + ') from :' + thisUrl);
  window.location.replace(redirectSuffix);
}

function initPage() {
  setTimeout('<?php echo $user['Home'] ? $user['Home'] : '?view=console' ?>', 500);
}

// Kick everything off
$j(document).ready(initPage);
</script>
<?php
}
?>
<?php xhtmlFooter() ?>
