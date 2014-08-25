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

function ConfigController($scope, $http, $sce, Config) {

	Config.getCategories(function(results) {
		// List of category names for the tabs
		$scope.categories = results['categories'];

		// For each category, add all config options belonging to it to the categories array
		for (var key in results['categories']) {
			var category = results['categories'][key]['Config']['Category'];
			buildCats(category);
		}
	});

	// Get config options belonging to a given category and push them into categories array
	function buildCats(cat) {
		Config.getCategory(cat, function(results) {
			$scope[cat] = $sce.trustAsHtml(results['config']);
		});
	}
	


	$http.get('/api/configs.json').success(function(data, status, headers, config) {
		$scope.config = data['configs'];
	});
}
