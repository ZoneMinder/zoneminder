<?php $this->assign('title', 'Zones'); ?>
<table class="table">
<?
  echo $this->Html->tableHeaders(array('Monitor', 'Zone Name'));
  foreach ($zones as $zone) {
    echo $this->Html->tableCells(array(
      $zone['Monitor']['Name'],
      $this->Html->link($zone['Zone']['Name'], array(
        'controller' => 'zones',
        'action' => 'edit',
        $zone['Zone']['Id'],
      ))
    ));
  }
?>
</table>
