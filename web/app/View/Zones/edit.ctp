<?php $this->assign('title', 'Edit Zone'); ?>
<?php $this->start('sidebar'); ?>
<ul class="list-group">
    <li class="list-group-item"><button class="btn btn-success btn-block" id="done">Done</button></li>
    <li class="list-group-item"><button class="btn btn-warning btn-block" id="reset">Reset</button></li>
</ul>
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
