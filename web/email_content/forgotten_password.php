<p>
Hi <span class="UserName"><?php 
global $user;
global $link;
echo (new ZM\User($user))->Name() ?>,
</span></p>
<p>We have received a request to change your password.</p>
<p><a href="<?php echo $link->url() ?>">Click here to change your password.</a></p>
<p>The link will take you to a secure webpage where you can change your password.</p>
<p>The above link is valid for 5 minutes only.</p>
<p>Thank you,</p>
<p>CloudMule</p>

