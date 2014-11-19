var ZoneMinder = angular.module('ZoneMinderControllers', []);

ZoneMinder.controller('HeaderController', function($scope, Header) {
	Header.getLogState(function(results) {
	});

	Header.getDaemonStatus(function(results) {
		if (results.result == 1) {
			$scope.isRunning = true;
		} 
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

ZoneMinder.controller('ConsoleController', function($scope, Console) {
	// Ask the API for events that have happened in the last week
	Console.getConsoleEvents('1 week').then(function(results) {
		// For each result, assign it to $scope[Counts$monitorId]
		for (var key in results['data']['results']) {
			var mid = key;
			var count = results['data']['results'][key];
			$scope['Counts' + mid] = count;
		}
	});
});
