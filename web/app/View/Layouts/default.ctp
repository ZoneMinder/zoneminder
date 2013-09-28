<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$cakeDescription = __d('cake_dev', 'CakePHP: the rapid development php framework');
?>
<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo $cakeDescription ?>:
		<?php echo $title_for_layout; ?>
	</title>
	<?php
		echo $this->Html->meta('icon');

		echo $this->Html->css('jquery-ui.min');
		echo $this->Html->css('colorbox');
		echo $this->Html->css('bootstrap-theme.min');
		echo $this->Html->css('bootstrap.min');
		echo $this->Html->css('main');

		echo $this->fetch('meta');
		echo $this->fetch('css');
    echo $this->fetch('script');
    echo $this->Html->script('jquery-2.0.1.min');
    echo $this->Html->script('jquery-ui.min');
    echo $this->Html->script('jquery.expander.min');
    echo $this->Html->script('jquery.colorbox-min');
    echo $this->Html->script('bootstrap.min');
    echo $this->Html->script('main');
	?>
</head>
<body>
	<div id="header" class="navbar navbar-default" role="navigation">
		<p class="navbar-text navbar-right"><?php echo $daemonStatusHtml; ?></p>
		<p class="navbar-text navbar-right">Used Event Storage: <?php echo $diskSpace; ?>%</p>
		<p class="navbar-text navbar-right">CPU Load: <?php echo $systemLoad; ?></p>
		<div class="container">
			<div id="loadingDiv"><img src="/img/loading.gif" alt="Loading..." /></div>
			<div class="navbar-header">
				<a class="navbar-brand" href="#">ZoneMinder</a>
			</div>
			<div class="navbar-collapse collapse">
				<ul class="nav navbar-nav">
					<li><?php echo $this->Html->link('Dashboard', array('controller' => 'Monitors', 'action' => 'index')); ?></li>
					<li><?php echo $this->Html->link('Events', '/Events/'); ?></li>
					<li><?php echo $this->Html->link('Options', array('controller' => 'Config', 'action' => 'index')); ?></li>
					<li><?php echo $this->Html->link('Logs', array('controller' => 'Logs', 'action' => 'index')); ?></li>
				</ul>
			</div>
		</div>
	</div>
	<div id="main-content" class="container">
    <div class="row">
      <div class="col-sm-2 col-md-2 col-lg-2 sidebar-offcanvas" id="sidebar">
        <div class="sidebar-nav">
          <?php echo $this->fetch('sidebar'); ?>
        </div>
      </div>
      <div class="col-sm-10 col-md-10 col-lg-10">
        <?php echo $this->Session->flash(); ?>
    
        <?php echo $this->fetch('content'); ?>
      </div>
    </div>
  </div>
	<div id="footer">
		<hr />
		<div class="container">
			<p>
				Configured for <?php echo $this->Html->link($zmBandwidth, array('controller' => 'Bandwidth', 'action' => 'index')); ?> bandwidth.
				<span id="version">Version <?php echo $this->Html->link($zmVersion, array('controller' => 'Version'), array('escape' => false)); ?></span>
			</p>
		</div>
	</div>
  <div id="toggle-fullscreen"><span class="glyphicon glyphicon-fullscreen"></span></div>
<!-- <?php echo $this->element('sql_dump'); ?> -->
<?php echo $this->Js->writeBuffer(); ?>
</body>
</html>
