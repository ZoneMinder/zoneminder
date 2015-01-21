var ZoneMinder = angular.module('ZoneMinder', [
	'ZoneMinderControllers',
	'tc.chartjs',
	'ui.bootstrap',
	'angularUtils.directives.dirPagination',
	'ui.bootstrap.datetimepicker'
]);

ZoneMinder.config(['$locationProvider', function($locationProvider){
    $locationProvider.html5Mode(true);    
}]);
ZoneMinder.config(function(paginationTemplateProvider) {
    paginationTemplateProvider.setPath('skins/bootstrap/js/dirPagination.tpl.html');
});

ZoneMinder.factory('Monitor', function($http) {
	return {
		getMonitor: function(mid) {
			return $http.get('/api/monitors/'+mid+'.json');
		},
		saveMonitor: function(monitor) {
			return $http({
				method: 'POST',
				url: '/api/monitors.json',
				data: $.param(monitor),
				headers: {'Content-Type': 'application/x-www-form-urlencoded'}
			});
		}
	};
});

ZoneMinder.factory('State', function($http) {
	return {
		get: function(callback) {
			$http.get('/api/states.json').success(callback);
		},
		change: function(state) {
			return $http.post('/api/states/change/'+state+'.json');
		}
	};
});

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
		get: function(page) {
			return $http.get('/api/logs.json?page='+page);
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

ZoneMinder.factory('Events', function($http) {
	return {
		get: function(filter, page) {
			if (filter) {
				return $http.get('/api/events/index/'+filter+'.json?page='+page);
			} else {
				return $http.get('/api/events.json?page='+page);
			}
		}
	};
});

ZoneMinder.factory('Event', function($http) {
	return {
		get: function(eventId) {
			return $http.get('/api/events/'+ eventId +'.json');
		},
		delete: function(eventId) {
			return $http.delete('/api/events/'+ eventId + '.json');
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
		},
		delete: function(id) {
			return $http.delete('/api/monitors/'+id+'.json');
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

ZoneMinder.filter('DateDiff', function() {
	return function(StartTime, EndTime, format) {
	var d1 = new Date(StartTime.replace(/-/g,'/'));
	var d2 = new Date(EndTime.replace(/-/g,'/'));
	var miliseconds = d2-d1;
	var seconds = miliseconds/1000;
	var minutes = seconds/60;
	var hours = minutes/60;
	var days = hours/24;

	switch (format) {
		case "seconds":
			return seconds;
		case "hours":
			return hours;
		case "minutes":
			return minutes;
		case "pretty":
			return Math.floor(minutes)+'m ' + seconds+'s';
	}
	};
});
