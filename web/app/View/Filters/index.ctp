<ul>
<?php foreach ($filters as $filter => $value): ?>
<li><?php echo $value['Filter']['Name']; ?></li>
<?php endforeach; ?>
<?php unset($filter); ?>
</ul>
