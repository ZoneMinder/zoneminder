var ZoneMinder = angular.module('ZoneMinder', [
	'ZoneMinderControllers',
	'tc.chartjs',
	'ui.bootstrap'
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

ZoneMinder.factory('Log', function($http) {
	return {
		getLogs: function(callback) {
			$http.get('/api/logs.json').success(callback);
		}
	};
});

ZoneMinder.factory('Host', function($http) {
	return {
		getDiskPercent: function(callback) {
			$http.get('/api/host/getDiskPercent.json').success(callback);
		},
		getLoad: function(callback) {
			$http.get('/api/host/getLoad.json').success(callback);
		}
	};
});

ZoneMinder.factory('Footer', function($http) {
	return {
		getVersion: function(callback) {
			$http.get('/api/host/getVersion.json').success(callback);
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
		},
		getMonitors: function() {
			return $http.get('/api/monitors.json');
		},
		daemonStatus: function(id, daemon) {
			return $http.get('/api/monitors/daemonStatus/id:'+id+'/daemon:'+daemon+'.json');
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
