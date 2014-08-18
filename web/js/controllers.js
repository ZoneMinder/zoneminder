var ZoneMinder = angular.module('ZoneMinderControllers', []);

function MonitorController($scope, $http) {
	$http.get('/api/monitors.json').success(function(data, status, headers, config) {
		$scope.monitors = data['monitors'];
	});
}

function NavigationController($scope) {

}
