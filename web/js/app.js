var ZoneMinder = angular.module('ZoneMinder', [
	'ngRoute',
	'ZoneMinderControllers'
]);

ZoneMinder.config(['$routeProvider',
	function($routeProvider) {
		$routeProvider.
			when('/monitor', {
				templateUrl: 'partials/monitor.html',
			}).
			when('/monitors', {
				templateUrl: 'partials/monitors.html',
			}).
			when('/config', {
				templateUrl: 'partials/config.html',
			}).
			otherwise({
				redirectTo: '/monitors'
			});

}]);



ZoneMinder.factory('Monitors', function ($http) {
	return {
		getMonitors: function(callback) {
			$http.get('/api/monitors.json').success(callback);
		},
		getSourceTypes: function(callback) {
			$http.get('/api/monitors/sourceTypes.json').success(callback);
		}
	};
});

ZoneMinder.factory('Config', function($http) {
	return {
		getCategories: function(callback) {
			$http.get('/api/configs/categories.json').success(callback);
		},
		getCategory: function(category, callback) {
			$http.get('/api/configs/categories/' + category + '.json').success(callback);
		}
	};
});

ZoneMinder.filter('range', function() {
// Thanks to https://stackoverflow.com/questions/11160513/angularjs-ng-options-create-range/11161353#11161353
	return function(input, min, max) {
		min = parseInt(min); //Make string input int
		max = parseInt(max);
		for (var i=min; i<max; i++)
			input.push(i);
		return input;
	};
});
