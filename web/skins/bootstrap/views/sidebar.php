<?php

?>

<ul class="nav nav-pills nav-stacked" role="tablist">
	<li><input class="btn btn-default btn-block" type="button" value="<?= $SLANG['Refresh'] ?>" onclick="location.reload(true);"/></li>
	<li><input class="btn btn-default btn-block" type="button" value="Add New Monitor" onclick="createPopup( '?view=monitor', 'zmMonitor0', 'monitor' ); return( false );"/></li>
	<li><input class="btn btn-default btn-block" type="button" value="Filters" onclick="createPopup( '?view=filter&amp;filter[terms][0][attr]=DateTime&amp;filter[terms][0][op]=%3c&amp;filter[terms][0][val]=now', 'zmFilter', 'filter' ); return( false );"/></li>
	<li><input class="btn btn-default btn-block" type="button" name="editBtn" value="<?= $SLANG['Edit'] ?>" onclick="editMonitor( this )" disabled="disabled"/></li>
	<li><input class="btn btn-default btn-block" type="button" name="deleteBtn" value="<?= $SLANG['Delete'] ?>" onclick="deleteMonitor( this )" disabled="disabled"/></li>
</ul>

