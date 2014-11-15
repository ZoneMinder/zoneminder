var ZoneMinder = angular.module('ZoneMinderControllers', []);

function MonitorsController($scope, Monitors) {
	Monitors.getMonitors(function(results) {
		$scope.monitors = results.monitors;
	});
}

function MonitorController($scope, Monitors) {
	Monitors.getSourceTypes(function(results) {
		$scope.sourceTypes = results.sourceTypes;
		$scope.monitorSourceType = $scope.sourceTypes[0];
		$scope.monitorDeviceChannel = 0;
	});
}

function HeaderController($scope) {

}

function ConfigController($scope, $http, Config) {

  Config.setConfigModel().then(function(results) {
    $scope.myModel = {configData: results.data.keyValues};
  }); 

	Config.getCategories().then(function(results) {
		// List of category names for the tabs
		$scope.categories = results.data.categories;

		// For each category, add all config options belonging to it to the categories array
		for (var key in results.data.categories) {
			var category = results.data.categories[key].Config.Category;

			catman(category);
		}

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

}

function EventsController($scope, $http, Events) {
	Events.getEvents().then(function(results) {
		console.log(results.data.events);
		$scope.events = results.data.events;
	})
};
