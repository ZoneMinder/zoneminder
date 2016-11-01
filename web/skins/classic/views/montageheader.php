<div class="navbar navbar-default">
	<div class="container">
<?php if ($view == 'montage' ) { ?>
		<div class="navbar-form navbar-left">
			<div class="form-group">
        			<label for="scale"><?php echo translate('Scale') ?>:</label> <?php echo buildSelect( 'scale', $scales, 'changeScale(this);', 'form-control' ); ?>
			</div>
			<div class="form-group">
				<label for="layout"><?php echo translate('Layout') ?>:</label><?php echo buildSelect( 'layout', $layouts, 'selectLayout(this);', 'form-control' )?>
			</div>
		</div>
<?php } else if ($view == 'cycle') { ?>
<?php if ( $mode == "stream" ) { ?>
        <a class="btn btn-default navbar-btn" href="?view=<?php echo $view ?>&amp;mode=still&amp;group=<?php echo $group ?>&amp;mid=<?php echo $monitor['Id'] ?>"><?php echo translate('Stills') ?></a>
<?php } else { ?>
        <a class="btn btn-default navbar-btn" href="?view=<?php echo $view ?>&amp;mode=stream&amp;group=<?php echo $group ?>&amp;mid=<?php echo $monitor['Id'] ?>"><?php echo translate('Stream') ?></a>
<?php } ?>
<?php } ?>

		<ul class="nav navbar-nav navbar-right">
			<li><a href="?view=cycle&amp;group=<?php echo $cycleGroup ?>"><?php echo translate('Cycle') ?></a></li>
			<li><a href="?view=montage&amp;group=<?php echo $cycleGroup ?>"><?php echo translate('Montage') ?></a></li>
			<li><a href="?view=montagereview&amp;group=<?php echo $cycleGroup ?>"><?php echo translate('Montage Review') ?></a></li>
<?php if ( $showControl ) { ?>
			<li><a href="#" onclick="createPopup( '?view=control', 'zmControl', 'control' )"><?php echo translate('Control') ?></a></li>
<?php } ?>
		</ul>
	</div>
</div>
