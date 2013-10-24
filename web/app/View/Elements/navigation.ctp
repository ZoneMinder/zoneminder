<ul class="nav nav-pills">
<?php
$views = array(
	'Monitors',
	'Events',
	'Options',
	'Logs',
	'Zones'
);
$title = $this->fetch('title');
foreach ($views as $view) {
	if ($view == $title) {
		echo '<li class="active">' . $this->Html->link($view, array('controller' => $view, 'action' => 'index')) . '</li>';
	} else {
		echo '<li>' . $this->Html->link($view, array('controller' => $view, 'action' => 'index')) . '</li>';
	}
}
?>
</ul>
