var ZoneMinder = angular.module('ZoneMinder', [
	'ZoneMinderControllers',
	'tc.chartjs',
	'ui.bootstrap',
	'angularUtils.directives.dirPagination',
	'ui.bootstrap.datetimepicker',
	'ui.router'
]);

ZoneMinder.config(['$locationProvider', function($locationProvider) {
	$locationProvider.html5Mode(false);
}]);

ZoneMinder.config(function($stateProvider, $urlRouterProvider) {
	$urlRouterProvider
		.when ('/', '/monitor')
		.otherwise('/');

	$stateProvider

		///////////////////////////////////////////////////
		// Monitor Grid and List View (main monitor page //
		///////////////////////////////////////////////////

		// This page lets you view the monitors as either a grid, or a list.
		// The grid or list is chosen by clicking on the button on the top-right of the page
		.state('monitor', {
			// State can not be explicitly activated - only implicitly by one of its children
			abstract: true,
			// This abstract will prepend '/monitor' onto the urls of all its children
			url: '/monitor',
			// As a top level state, this template will be loaded into index.html's ui-view
			templateUrl: '/views/monitor.html'
		})

		.state('monitor.list', {
			url: '',
			templateUrl: '/views/monitor.list.html'
		})

		//////////////////////
		// Monitor > Detail //
		//////////////////////

		// 'detail' is a child of 'monitor' and as such will be loaded into monitor.html's ui-view
		// The 'detail' state will be the first 'tab' in the 'detail' view, which is 'General'
		.state('monitor.detail', {
			// monitor.detail can not be loaded directly
			abstract: true,
			// This state is a child of 'monitor'.  The URL will end up being like:
			// '/monitor/{mid:[0-9]{1,4}}'.  When the URL becomes something like '/monitor/7',
			// this state will become active.
			url: '/detail/{mid:[0-9]{1,4}}',
			templateUrl: '/views/monitor.detail.html',
			controller: 'MonitorController'
		})

		////////////////////////////
		// Monitor > Detail > Tab //
		////////////////////////////

		// Each 'tab' gets its own state.  As these are all children of 'detail', they are lodaed
		// into detail's ui-view

		.state('monitor.detail.general', {
			url: '',
			templateUrl: '/views/monitor.detail.general.html'
		})
		.state('monitor.detail.source', {
			url: '',
			templateUrl: '/views/monitor.detail.source.html'
		})
		.state('monitor.detail.timestamps', {
			url: '',
			templateUrl: '/views/monitor.detail.timestamps.html'
		})
		.state('monitor.detail.buffers', {
			url: '',
			templateUrl: '/views/monitor.detail.buffers.html'
		})
		.state('monitor.detail.misc', {
			url: '',
			templateUrl: '/views/monitor.detail.misc.html'
		})

		.state('host', {
			url: '/host',
			templateUrl: '/views/host.html'
		})

		.state('log', {
			url: '/log',
			templateUrl: '/views/log.html'
		})

		///////////////////////////
		// Zones - Edit and List //
		///////////////////////////
		.state('zones', {
			url: '/zones/{mid:[0-9]{1,4}}',
			templateUrl: '/views/zones.html',
			resolve: {
				mid: function($stateParams) {
					return {  value: $stateParams.mid };
				},
				zones: function(Zones, $stateParams) {
					return Zones.getZones($stateParams.mid);
				}
			},
			controller: function($scope, mid, zones) {
				$scope.mid = mid.value;
				$scope.zones = zones.data.zones;
			}
		})
		.state('zones.edit', {
			url: '/edit/{zid:[0-9]{1,4}}',
			templateUrl: '/views/zones.edit.html',
			resolve: {
				zone: function(Zones, $stateParams) {
					return Zones.getZone($stateParams.zid);
				}
			},
			controller: function($scope, zone) {
				$scope.zone = zone.data.zone.Zone;
			}
		})


		.state('events', {
			url: '/events',
			templateUrl: '/views/events.html'
		})

		.state('options', {
			abstract: true,
			url: '/options',
			templateUrl: '/views/options/options.html'
		})
		.state('options.images', {
		        url: '/images',
		        templateUrl: '/views/options/options.images.html'
		})
		.state('options.system', {
		        url: '/system',
		        templateUrl: '/views/options/options.system.html'
		})
		.state('options.config', {
		        url: '/config',
		        templateUrl: '/views/options/options.config.html'
		})
		.state('options.paths', {
		        url: '/paths',
		        templateUrl: '/views/options/options.paths.html'
		})
		.state('options.logging', {
		        url: '/logging',
		        templateUrl: '/views/options/options.logging.html'
		})
		.state('options.dynamic', {
		        url: '/dynamic',
		        templateUrl: '/views/options/options.dynamic.html'
		})
		.state('options.mail', {
		        url: '/mail',
		        templateUrl: '/views/options/options.mail.html'
		})
		.state('options.eyezm', {
		        url: '/eyezm',
		        templateUrl: '/views/options/options.eyezm.html'
		})
		.state('options.network', {
		        url: '/network',
		        templateUrl: '/views/options/options.network.html'
		})
		.state('options.upload', {
		        url: '/upload',
		        templateUrl: '/views/options/options.upload.html'
		})
		.state('options.x10', {
		        url: '/x10',
		        templateUrl: '/views/options/options.x10.html'
		})
		.state('options.web', {
		        url: '/web',
		        templateUrl: '/views/options/options.web.html'
		})
		.state('options.highband', {
		        url: '/highband',
		        templateUrl: '/views/options/options.highband.html'
		})
		.state('options.lowband', {
		        url: '/lowband',
		        templateUrl: '/views/options/options.lowband.html'
		})
		.state('options.medband', {
		        url: '/medband',
		        templateUrl: '/views/options/options.medband.html'
		})
		.state('options.phoneband', {
		        url: '/phoneband',
		        templateUrl: '/views/options/options.phoneband.html'
		});

});

ZoneMinder.config(function(paginationTemplateProvider) {
    paginationTemplateProvider.setPath('/js/dirPagination.tpl.html');
});

ZoneMinder.factory('Monitor', function($http) {
	return {
		getMonitors: function() {
			return $http.get('/api/monitors.json');
		},
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
		},
		archive: function(eventId) {
			return $http.post('/api/events/archive/'+ eventId + '.json');
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
		},
		findByName: function(name) {
			return $http.get('/api/configs/viewByName/'+name+'.json')
		}
	};
});

ZoneMinder.factory('Zones', function($http) {
	return {
		getZones: function(mid) {
			return $http.get('/api/zones/forMonitor/'+mid+'.json')
		},
		getZone: function(zid) {
			return $http.get('/api/zones/'+zid+'.json')
		},
		createZoneImage: function(mid) {
			return $http.post('/api/zones/createZoneImage/'+mid+'.json');
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

ZoneMinder.filter('zpad', function() {
	return function(input, n) {
		if(input === undefined)
			input = ""
		if(input.length >= n)
			return input
		var zeros = "0".repeat(n);
		return (zeros + input).slice(-1 * n)
	}; 
});
