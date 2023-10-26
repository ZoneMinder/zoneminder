<p>
Hi <span class="UserName"><?php 
global $link;
$user = $link->User();
echo ($user ? $user->Name() : ''); ?>
</span>,</p>
<p>We have received a request to change your password.</p>
<p><a href="<?php echo $link->url() ?>" target="_blank">Click here to change your password.</a></p>
<p>or cut and paste the following into your browser:<br/>
<?php echo htmlspecialchars($link->url()) ?>
</p>
<p>The link will take you to a secure webpage where you can change your password.</p>
<p>The above link is valid for 5 minutes only.</p>
<p>Thank you,</p>
<p><?php echo ZM_WEB_TITLE ?></p>
