var ZoneMinder = angular.module('ZoneMinder', [
	'ngRoute',
	'ZoneMinderControllers'
]);

ZoneMinder.config(['$routeProvider',
	function($routeProvider) {
		$routeProvider.
			when('/monitors', {
				templateUrl: 'partials/monitors.html',
			}).
			otherwise({
				redirectTo: '/monitors'
			});

}]);

