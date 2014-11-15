var ZoneMinder = angular.module('ZoneMinder', [
	'ZoneMinderControllers'
]);

ZoneMinder.factory('Header', function($http) {
	return {
		getLogState: function(callback) {
			$http.get('/api/monitors.json').success(callback);
		}
	};
});
