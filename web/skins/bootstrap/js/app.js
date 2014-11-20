var ZoneMinder = angular.module('ZoneMinder', [
	'ZoneMinderControllers'
]);

ZoneMinder.config(['$locationProvider', function($locationProvider){
    $locationProvider.html5Mode(true);    
}]);

ZoneMinder.factory('Header', function($http) {
	return {
		getLogState: function(callback) {
			$http.get('/api/monitors.json').success(callback);
		},
		getDaemonStatus: function(callback) {
			$http.get('/api/host/daemonCheck.json').success(callback);
		}
	};
});

ZoneMinder.factory('Event', function($http) {
	return {
		getEvent: function(eventId) {
			return $http.get('/api/events/'+ eventId +'.json');
		}
	};
});

ZoneMinder.factory('Console', function($http) {
	return {
		getConsoleEvents: function(interval) {
			return $http.get('/api/events/consoleEvents/'+interval+'.json');
		}
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
