<html>
<head>
<script language="JavaScript">
<?php
	if ( !empty($refresh_parent) )
	{
?>
//self.onerror = function() { return( true ); }
opener.location.reload(true);
<?php
	}
?>
window.close();
</script>
</head>
</html>
