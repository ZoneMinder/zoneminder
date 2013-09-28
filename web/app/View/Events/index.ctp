<?php $this->assign('title', 'Events'); ?>
<?php $this->start('sidebar'); ?>
<div class="panel-body">
<div id="sidebar">
<?php
echo $this->Form->create('Events', array('default' => false, 'inputDefaults' => array(
	'legend' => false,
	'label' => false,
	'div' => false,
	'fieldset' => false
)));
?>

<div class="panel panel-default">
  <div class="panel-heading">Filter by Monitor</div>
  <div class="panel-body">
<div id="events_monitors">
<ul class="list-group">
<?php foreach ($monitors as $monitor): ?>
<li class="list-group-item" id="Monitor_<?php echo $monitor['Monitor']['Id']; ?>">
  <?php echo $this->Form->input($monitor['Monitor']['Name'], array('type' => 'checkbox', 'label' => $monitor['Monitor']['Name'])); ?>
</li>
<?php
endforeach;
unset($monitor);
?>
</ul>
</div>
</div>
</div>

<div class="panel panel-default">
  <div class="panel-heading">Filter by Date & Time</div>
  <div class="panel-body">
    Start Date
    <div class="form-group">
      <div class="input-group date datetime">
        <input type="text" class="form-control" id="EventStartDate">
        <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
      </div>
    </div>
    End Date
    <div class="form-group">
      <div class="input-group date datetime">
        <input type="text" class="form-control" id="EventEndDate">
        <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
      </div>
    </div>  
  </div>
</div>

<?php echo $this->Form->end(array('label' => 'Search', 'id' => 'EventsButtonSearch', 'class' => 'btn btn-default')); ?>
<?php echo $this->Html->link('Delete Selected','#',array('class' => 'btn btn-default', 'onClick' => '$("#EventsDeleteSelectedForm").submit();')); ?>

</div>
</div>

<?php $this->end(); ?>

<ul class="pagination">
  <?php echo $this->Paginator->numbers(array('tag' => 'li', 'separator' => false, 'currentClass' => 'active', 'currentTag' => 'span')); ?>
</ul>

<?
  echo $this->Form->create('Events', array('action' => 'deleteSelected'));
?>

<table class="table table-condensed table-striped" id="Events">
<?php
  echo $this->Html->tableHeaders(array($this->Form->checkbox('', array('hiddenField' => false, 'class' => 'selectAll')), 'Thumbnail', 'Id', 'Name', 'Monitor', 'Cause', 'Date/Time', 'Duration', 'Alarm Frames', 'Total Score', 'Avg. Score', 'Max Score'));

foreach ($events as $key => $value) {
	echo $this->Html->tableCells(array(
		$this->Form->checkbox('delete.', array(
      'value' => $value['Event']['Id'],
      'hiddenField' => false
    )),
    $this->Html->link($this->Html->image('/events/'.$thumbData[$key]['Path'], array(
			'alt' => $thumbData[$key]['Frame']['FrameId'].'/'.$thumbData[$key]['Event']['MaxScore'],
			'width' => $thumbData[$key]['Width'],
			'height' => $thumbData[$key]['Height']
		)),
		array('controller' => 'events', 'action' => 'view', $value['Event']['Id']), array('escape' => false)),
		$value['Event']['Id'],
		$value['Event']['Name'],
		$value['Monitor']['Name'],
		$value['Event']['Cause'],
		//$value['Event']['StartTime'],
		$this->Time->format('n/j/y @ g:i:s A', $value['Event']['StartTime'], null, 'EST'),
		$value[0]['Duration'],
		$value['Event']['AlarmFrames'],
		$value['Event']['TotScore'],
		$value['Event']['AvgScore'],
		$value['Event']['MaxScore']
	));
}
?>
</table>

<?
  echo $this->Form->end();
?>

<ul class="pagination">
  <?php echo $this->Paginator->numbers(array('tag' => 'li', 'separator' => false, 'currentClass' => 'active', 'currentTag' => 'span')); ?>
</ul>
