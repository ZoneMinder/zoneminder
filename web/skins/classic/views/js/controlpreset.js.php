var labels = new Array();
<?php
foreach ( $labels as $index=>$label )
{
?>
labels[<?= $index ?>] = "<?= htmlentities(addslashes($label)) ?>";
<?php
}
?>
