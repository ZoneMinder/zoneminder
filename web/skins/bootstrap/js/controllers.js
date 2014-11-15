var ZoneMinder = angular.module('ZoneMinderControllers', [])

.controller('HeaderController', function($scope, Header) {
	Header.getLogState(function(results) {
		console.log(results);
	});
});
