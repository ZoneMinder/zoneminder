	<div id="footer">
		<hr />
		<div class="container">
			<p>
				Configured for <?php echo $this->Html->link(Configure::read('zmBandwidth'), array('controller' => 'Bandwidth', 'action' => 'index')); ?> bandwidth.
				<span id="version">Version <?php echo $this->Html->link($zmVersion, array('controller' => 'Version'), array('escape' => false)); ?></span>
			</p>
		</div>
	</div> <!-- End Footer -->
	<div id="toggle-fullscreen"><span class="glyphicon glyphicon-fullscreen"></span></div>
<!-- <?php echo $this->element('sql_dump'); ?> -->
<?php echo $this->Js->writeBuffer(); ?>
