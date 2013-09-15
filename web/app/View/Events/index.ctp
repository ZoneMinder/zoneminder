<?php $this->start('sidebar'); ?>
<div id="sidebar">
<?php
echo $this->Form->create('Events', array('default' => false, 'inputDefaults' => array(
	'legend' => false,
	'label' => false,
	'div' => false,
	'fieldset' => false
)));
?>
<div id="events_monitors">
<fieldset>
<legend>Monitors</legend>
<ol id="selectable">
<?php foreach ($monitors as $monitor): ?>
<li id="Monitor_<?php echo $monitor['Monitor']['Id']; ?>"><?php echo $this->Form->input($monitor['Monitor']['Name'], array('type' => 'checkbox', 'label' => $monitor['Monitor']['Name'])); ?></li>
<?php
endforeach;
unset($monitor);
?>
</ol>
</fieldset>
</div>

<div id="events_date_time">
<fieldset>
<legend>Date Range</legend>
<fieldset>
<?php
$prepend = array('00','01','02','03','04','05','06','07','08','09');
$hours     = array_merge($prepend,range(10, 23));
$minutes     = array_merge($prepend,range(10, 59));
$seconds     = $minutes; 
echo $this->Form->input('Start Date', array('id' => 'EventStartDate', 'required' => true));
echo $this->Form->inputs(array(
	'legend' => false,
	'fieldset' => false,
	'Hour' => array('type' => 'select', 'id' => 'EventStartHour', 'options' => $hours),
	'Minute' => array('type' => 'select', 'id' => 'EventStartMinute', 'options' => $minutes)
));
?>
</fieldset>
<fieldset>
<?php
echo $this->Form->input('End Date', array('id' => 'EventEndDate', 'required' => true));
echo $this->Form->inputs(array(
	'legend' => false,
	'fieldset' => false,
	'Hour' => array('type' => 'select', 'id' => 'EventEndHour', 'options' => $hours),
	'Minute' => array('type' => 'select', 'id' => 'EventEndMinute', 'options' => $minutes)
));
?>
</fieldset>
</div>
<?php echo $this->Form->end(array('label' => 'Search', 'id' => 'EventsButtonSearch')); ?>
</div>
<?php $this->end(); ?>

<ul class="pagination">
  <?php echo $this->Paginator->numbers(array('tag' => 'li', 'separator' => false, 'currentClass' => 'active', 'currentTag' => 'span')); ?>
</ul>

<div id="Events">
<table class="table table-condensed table-striped">
<?php
  echo $this->Html->tableHeaders(array( 'Thumbnail', 'Id', 'Name', 'Monitor', 'Cause', 'Time', 'Duration', 'Alarm Frames', 'Total Score', 'Avg. Score', 'Max Score', '' ));

foreach ($events as $key => $value) {
	echo $this->Html->tableCells(array(
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
		$value['Event']['StartTime'],
		$value[0]['Duration'],
		$value['Event']['AlarmFrames'],
		$value['Event']['TotScore'],
		$value['Event']['AvgScore'],
		$value['Event']['MaxScore'],
  		$this->Form->postLink(
			'Delete',
			array('action' => 'delete', $value['Event']['Id']),
			array('confirm' => 'Are you sure?'))
	));
}
?>
</table>
</div>

<ul class="pagination">
  <?php echo $this->Paginator->numbers(array('tag' => 'li', 'separator' => false, 'currentClass' => 'active', 'currentTag' => 'span')); ?>
</ul>
