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

		echo $this->Html->css('cake.generic');
		echo $this->Html->css('jquery-ui.min');

		echo $this->fetch('meta');
		echo $this->fetch('css');
    echo $this->fetch('script');
    echo $this->Html->script('jquery-2.0.1.min');
    echo $this->Html->script('jquery-ui.min');
    echo $this->Html->script('masonry.pkgd.min');
    echo $this->Html->script('main');
	?>
</head>
<body>
	<div id="container">
		<div id="header">
			<div class="menu">
				<ol>
				<li><?php echo $this->Html->link('Dashboard', array('controller' => 'Monitors', 'action' => 'index')); ?></li>
				<li><?php echo $this->Html->link('Events', '/Events/'); ?></li>
				<li><?php echo $this->Html->link('Options', array('controller' => 'Config', 'action' => 'index')); ?></li>
				</ol>
			</div>
		</div>
		<div id="content">
			<?php echo $this->Session->flash(); ?>

			<?php echo $this->fetch('content'); ?>
		</div>
		<div id="footer">
				<p>Configured for <?php echo $this->Html->link($zmBandwidth, array('controller' => 'Bandwidth', 'action' => 'index')); ?> bandwidth</p>
				<p><?php echo $daemonStatus; ?></p>
		</div>
	</div>
	<?php echo $this->element('sql_dump'); ?>
<?php echo $this->Js->writeBuffer(); ?>
</body>
</html>
