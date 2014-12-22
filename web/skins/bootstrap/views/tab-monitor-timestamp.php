<div role="tabpanel" class="tab-pane" id="timestamp">
	<div class="form-group">
		<label for="labelFormat"><?= $SLANG['TimestampLabelFormat'] ?></label>
		<input type="text" id="labelFormat" class="form-control" ng-model="monitor.LabelFormat" required />
	</div>
	<div class="form-group">
		<label for="labelX"><?= $SLANG['TimestampLabelX'] ?></label>
		<input type="text" id="labelX" class="form-control" ng-model="monitor.LabelX" required />
	</div>
	<div class="form-group">
		<label for="labelY"><?= $SLANG['TimestampLabelY'] ?></label>
		<input type="text" id="labelY" class="form-control" ng-model="monitor.LabelY" required />
	</div>
</div>
