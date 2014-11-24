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

ZoneMinder.controller('FooterController', function($scope, Footer) {
	Footer.getLoad(function(load) {
		$scope.load = load.load;
	});

	Footer.getDiskPercent(function(diskPercent) {
		$scope.diskPercent = diskPercent.space;
	});

	Footer.getVersion(function(version) {
		$scope.version = version.version;
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


ZoneMinder.controller('ConfigController', function($scope, $http, Config) {

  Config.setConfigModel().then(function(results) {
    $scope.myModel = {configData: results.data.keyValues};
  }); 

	Config.getCategories().then(function(results) {
		// List of category names for the tabs
		$scope.categories = results.data.categories;

		// For each category, add all config options belonging to it to the categories array
		angular.forEach(results['data']['categories'], function(value, key) {
			var cat = results.data.categories[key].Config.Category;
			catman(cat);
		});

	});

	function catman(category) {
			Config.getCategory(category).then(function(results) {
				$scope[category] = results.data.config;
			});
	}

	$scope.updateConfig = function(configId, configName) {
		var newValue = $scope.myModel.configData[configName];
		var i = document.getElementById(configName).parentNode.parentNode;
		var s = i.getElementsByTagName("span");
		s = s[0];

		Config.updateOption(configId, newValue).then(function(results) {
			if (results.statusText == 'OK') {
				i.className = i.className + " has-success has-feedback";
				s.className = s.className + " glyphicon glyphicon-ok";
			} else {
				i.className = i.className + " has-failure has-feedback";
				s.className = s.className + " glyphicon glyphicon-ok";
			}
		});
	}

});
