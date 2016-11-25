<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('TimestampLabelFormat') ?></label><div class="col-sm-3"><input class="form-control" type="text" name="newMonitor[LabelFormat]" value="<?php echo validHtmlStr($newMonitor['LabelFormat']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('TimestampLabelX') ?></label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[LabelX]" value="<?php echo validHtmlStr($newMonitor['LabelX']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('TimestampLabelY') ?></label><div class="col-sm-3"><input class="form-control" type="number" name="newMonitor[LabelY]" value="<?php echo validHtmlStr($newMonitor['LabelY']) ?>"></div>
</div>
<div class="form-group">
<label class="col-sm-3 control-label"><?php echo translate('TimestampLabelSize') ?></label><div class="col-sm-3"><select class="form-control" name="newMonitor[LabelSize]"><?php foreach ( $label_size as $name => $value ) { ?><option value="<?php echo $value ?>"<?php if ( $value == $newMonitor['LabelSize'] ) { ?> selected="selected"<?php } ?>><?php echo $name ?></option><?php } ?></select></div>
</div>
