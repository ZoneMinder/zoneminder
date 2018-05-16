var labels = new Array();
<?php
foreach ( $labels as $index=>$label ) {
?>
labels[<?php echo validInt($index) ?>] = '<?php echo validJsStr($label) ?>';
<?php
}
?>
