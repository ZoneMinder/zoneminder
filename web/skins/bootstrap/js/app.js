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

