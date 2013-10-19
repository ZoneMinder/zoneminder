<?php $this->assign('title', 'Edit Zone'); ?>
<?php $this->start('sidebar'); ?>
  <div>
    <button class="btn btn-default" id="done">Done</button>
    <button class="btn btn-default" id="reset">Reset</button>
  </div>
<? $this->end(); ?>

<div>
  <canvas id="c1">
    <?php echo $this->Html->image($zoneImage, array('alt' => 'Your browser does not support the HTML5 canvas element.  Upgrade your shit!', 'id' => 'imgZone')); ?>
  </canvas>
</div>
  <div id="zones"></div>

<script type="text/javascript" src="/js/zone.js"></script>
