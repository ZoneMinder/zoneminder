var ZoneMinder = angular.module('ZoneMinderControllers', []);

ZoneMinder.controller('HeaderController', function($scope, Header) {
	Header.getLogState(function(results) {
		console.log(results);
	});
});

ZoneMinder.controller('EventController', function($scope, $location, Event) {

	var eventId = $location.search().eid;

	Event.getEvent(eventId).then(function(results) {
		$scope.eventId			= eventId;
		$scope.name 				= results.data.event.Event.Name;
		$scope.cause 				= results.data.event.Event.Cause;
		$scope.startTime 		= results.data.event.Event.StartTime;
		$scope.endTime 			= results.data.event.Event.EndTime;
		$scope.width 				= results.data.event.Event.Width;
		$scope.length 			= results.data.event.Event.Length;
		$scope.frames				= results.data.event.Event.Frames;
		$scope.alarmFrames	= results.data.event.Event.AlarmFrames;
		$scope.totScore				= results.data.event.Event.TotScore;
		$scope.avgScore				= results.data.event.Event.AvgScore;
		$scope.maxScore				= results.data.event.Event.MaxScore;
		$scope.notes				= results.data.event.Event.Notes;


	});
});
