<div class="modal-header">
	<span class="pull-right modal-title">Event {{eventId}}</span>
	<span ng-bind="startTime"></span>
</div>

<div class="modal-body">

	<div id="eventStream" ng-show="stream">
		<img class="img-responsive" ng-src="/cgi-bin/nph-zms?source=event&mode=jpeg&event={{eventId}}&frame=1&scale=100&rate=100&maxfps=30&replay=single&connkey=736818&rand=1419877749" />

		<div>
			<span class="pull-right">{{ startTime | DateDiff:endTime:'pretty' }}</span>
			<div id="controls" class="text-center">
				Start || Pause
			</div>
		</div>
	</div>
</div>

	<?php include("event-frames-stills.html"); ?>

<div class="modal-footer">
	<span class="pull-right glyphicon glyphicon-chevron-right"><span class="sr-only">Next</span></span>
	<button type="button" class="btn btn-default" ng-click="eventView()">{{ eventView_text }}</button>
	<button type="button" class="btn btn-default" ng-click="archive()">{{ archive_text }}</button>
	<button type="button" class="btn btn-danger" ng-click="delete()">Delete</button>
	<button type="button" class="btn btn-warning" ng-click="cancel()">Cancel</button>
	<span class="pull-left glyphicon glyphicon-chevron-left"><span class="sr-only">Previous</span></span>
</div>
