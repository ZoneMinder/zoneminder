<h2><?= __d('crud', 'Config'); ?></h2>
<?php
if (empty($crudDebugKitData['action'])) {
	$crudDebugKitData['action'] = __d('crud', 'Current action is not handled by Crud');
}

$config = array(
	__d('crud', 'Action') => $crudDebugKitData['action'],
	__d('crud', 'Component') => $crudDebugKitData['component'],
	__d('crud', 'Listeners') => $crudDebugKitData['listeners']
);

echo $this->Toolbar->makeNeatArray($config);
?>
<h2><?= __d('crud', 'Events triggered'); ?></h2>
<?php
echo $this->Toolbar->makeNeatArray($crudDebugKitData['events']);
