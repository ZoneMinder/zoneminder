<div ng-show="monitor.Type == 'cURL'">
	<div class="form-group">
		<label><?= "URL" ?></label>
		<input class="form-control" type="text" ng-model="monitor.Path" />
	</div>
	<div class="form-group">
		<label><?= "Username" ?></label>
		<input class="form-control" type="text" ng-model="monitor.User" />
	</div>
	<div class="form-group">
		<label><?= "Password" ?></label>
		<input class="form-control" type="text" ng-model="monitor.Pass" />
	</div>
</div>
