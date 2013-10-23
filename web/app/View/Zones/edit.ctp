<?php $this->assign('title', 'Edit Zone'); ?>
<?php $this->start('sidebar'); ?>
  <div>
    <button class="btn btn-default" id="done">Done</button>
    <button class="btn btn-default" id="reset">Reset</button>
  </div>
<? $this->end(); ?>

<div class="row">

<div class="col-md-3">
<?php echo $this->element('zones-inputs'); ?>
</div>

<div class="col-md-9">
<?php echo $this->element('zones-canvas'); ?>
</div>

</div>
<script type="text/javascript" src="/js/zone.js"></script>
