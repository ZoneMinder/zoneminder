<h2>Events</h2>
<?php $count = 0; ?>
<ol id="selectable">
<?php foreach ($monitors as $monitor): ?>
  <li id="Monitor_<?php echo $monitor['Monitor']['Id']; ?>">
<?php echo $monitor['Monitor']['Name']; ?>
	<?php echo count($monitor['Event']); ?>
	<?php echo $eventsLastHour[$count][0]['count']; ?>
	<?php echo $eventsLastDay[$count][0]['count']; ?>
	<?php echo $eventsLastWeek[$count][0]['count']; ?>
	<?php echo $eventsLastMonth[$count][0]['count']; ?>
  <?php echo $eventsArchived[$count][0]['count']; ?>
</li>
    <?php $count++; ?>
    <?php endforeach; ?>
    <?php unset($monitor); ?>
</ol>
<div id="Events">
<div style="clear:both;"><?php echo $this->Paginator->numbers(); ?></div>
<table>
    <tr>
        <th>Event Name</th>
        <th>Monitor Name</th>
        <th>Length</th>
    </tr>

    <?php foreach ($events as $key => $value): ?>
    <tr>
        <td>
        <td><?php echo $event['Monitor']['Name']; ?></td>
        <td><?php echo $event['Event']['Length']; ?></td>
<?php 
echo $this->Html->link($this->Html->image('/events/'.$thumbData[$key]['Path'], array(
    'alt' => $thumbData[$key]['Frame']['FrameId'].'/'.$thumbData[$key]['Event']['MaxScore'],
    'width' => $thumbData[$key]['Width'],
    'height' => $thumbData[$key]['Height']
)), array('controller' => 'events', 'action' => 'view', $value['Event']['Id']),
array('escape' => false));
?>

</td>
    </tr>
    <?php endforeach; ?>
    <?php unset($key); ?>
</table>
<div><?php echo $this->Paginator->numbers(); ?></div>
</div>
