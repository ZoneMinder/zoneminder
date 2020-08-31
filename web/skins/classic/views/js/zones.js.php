<?php
  global $monitors;
?>
var monitors = new Array();
<?php
  foreach ( $monitors as $monitor ) {
    echo 'monitors['.$monitor->Id().'] = '.json_encode($monitor).';'.PHP_EOL;
  }
?>
var CMD_QUIT = <?php echo CMD_QUIT ?>;
