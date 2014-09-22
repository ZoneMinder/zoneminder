var ZoneMinder = angular.module('ZoneMinder', [
	'ngRoute',
	'ZoneMinderControllers',
	'ui.bootstrap'
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
		getCategories: function() {
			return $http.get('/api/configs/categories.json');
		},
		getCategory: function(category) {
			return $http.get('/api/configs/categories/' + category + '.json')
		},
    setConfigModel: function() {
			return $http.get('/api/configs/keyValue.json')
    },
		updateOption: function(configId, newValue) {
			var putData = "Config[Value]=" + newValue;
			//var postData = {Config[Value]: configValue};


			return $http({
				method: 'POST',
				url: '/api/configs/' + configId + '.json',
				data: putData,
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			});


			//return $http.post ('/api/configs/' + configId + '.json', postData)
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



ZoneMinder.directive('angularHtmlBind', function($compile) {
    return function(scope, elm, attrs) {
        scope.$watch(attrs.angularHtmlBind, function(newValue, oldValue) {
            if (newValue && newValue !== oldValue) {
                elm.html(newValue);
                $compile(elm.contents())(scope);
            }
        });
    };
});
