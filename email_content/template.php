<?php
global $skin;
global $css;
global $email_content;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title><?php echo ZM_WEB_TITLE; ?></title>
		<style type="text/css">
		<?php echo get_include_contents($_SERVER['DOCUMENT_ROOT'].'/skins/'.$skin.'/css/'.$css.'/email.css')?>
		</style>
		<base href="<?php echo ZM_URL; ?>"/>
	</head>
	<body>
		<table width="98%" border="0">
			<tr> 
      <td height="72" id="banner"><a href="<?php echo ZM_URL ?>" target="_blank">
          <img src="<?php echo ZM_URL ?>/skins/<?php echo $skin ?>/css/<?php echo $css ?>/graphics/email_header.png" height="40" border="0" alt="<?php echo ( ZM_WEB_TITLE ) ?>"/></a></td>
			</tr>
			<tr> 
				<td align="left" valign="top" style="padding: 5px;">
					<br/>
          <?php echo $email_content; ?> <br/> <br/>
				</td>
			</tr>
		</table>
	</body>
</html>
