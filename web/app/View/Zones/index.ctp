<?php
	$this->assign('title', 'Zones');
	$this->start('sidebar');
	echo $this->Form->select("Monitor", $monitors, array('empty' => 'Filter Zones'));
	$this->end();
?>
<table class="table">
<?
  echo $this->Html->tableHeaders(array('Monitor Name', 'Zone Name', 'Zone Type'));
  foreach ($zones as $zone) {
    echo $this->Html->tableCells(array(
      $zone['Monitor']['Name'],
      $this->Html->link($zone['Zone']['Name'], array(
        'controller' => 'zones',
        'action' => 'edit',
        $zone['Zone']['Id'],
      )),
	$zone['Zone']['Type']
    ));
  }
?>
</table>
