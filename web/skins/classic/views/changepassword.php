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

if ($action == 'changepassword' and !$error_message) {
?>
<p>You have successfully updated your password. We will redirect in a moment.</p>
<script nonce="<?php echo $cspNonce; ?>">
const redirectSuffix = '<?php echo (isset($user['Home']) ? $user['Home'] : '?view=console'); ?>';
// Redirect to the requested ZoneMinder url after the user logs in
function postLoginRedirect() {
  window.location.replace(redirectSuffix);
}

function initPage() {
  setTimeout(postLoginRedirect, 1000);
}

// Kick everything off
window.addEventListener('DOMContentLoaded', initPage);
</script>
<?php
} else {
  if ($error_message) {
    echo '<div id="error">'.$error_message.'</div>';
  }
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
<?php } ?>
		</form>
	</div>
<?php xhtmlFooter() ?>
