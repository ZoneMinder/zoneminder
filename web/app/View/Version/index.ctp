<?php
  if ($zmDynLastVersion > $zmDynDBVersion) {
?>
  <div class="alert alert-info">A new upgrade is available!</div>
<?php
  } else {
?>
  <div class="alert alert-success">An upgrade is not available.  Carry on.</div>
<?php
  }
?>
