var labels = new Array();
<?php
foreach ( $labels as $index=>$label )
{
?>
labels[<?= validInt($index) ?>] = "<?= validJsStr($label) ?>";
<?php
}
?>
