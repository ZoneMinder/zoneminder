var ZoneMinder = angular.module('ZoneMinderControllers', []);

ZoneMinder.controller('StateController', function($scope, State, Header) {
	State.get(function(results) {
		$scope.states = results.states;
	});

	$scope.changeState = function(newState) {
		State.change(newState)
		// Redirect to the dashboard on success
	        .success(function(data) { window.location = "/index.php?view=console"; });
	};

	Header.getDaemonStatus(function(results) {
		if (results.result == 1) {
			$scope.isRunning = true;
		} 
	});
});

ZoneMinder.controller('HeaderController', function($scope, Header, State) {
	Header.getLogState(function(results) {
	});

	Header.getDaemonStatus(function(results) {
		if (results.result == 1) {
			$scope.isRunning = true;
		} 
	});
});

ZoneMinder.controller('FooterController', function($scope, Footer) {
	Footer.getVersion(function(version) {
		$scope.version = version.version;
	});
});

ZoneMinder.controller('LogController', function($scope, Log) {
	getLogsPage(1);

	$scope.pageChanged = function(newPage) {
		getLogsPage(newPage);
	}

	function getLogsPage(pageNumber) {
		Log.get(pageNumber).then(function(results) {
			$scope.logs = results.data.logs;
			$scope.totalLogs = results.data.pagination.count;
			$scope.logsPerPage = results.data.pagination.limit;
		});
	}
});

ZoneMinder.controller('EventsController', function($scope, Events, Console, $modal) {
	// First thing, get page 1 of the events.
	$scope.page = 1;
	getEvents(null, $scope.page);

	// Get the monitors which popular the sidebar select
	Console.getMonitors().then(function(results) {
		$scope.monitors = results.data.monitors;
	});

	$scope.filter_url = '';

	var now = new Date();
	var startdate = new Date(now);
	startdate.setMonth(now.getMonth() - 1);
	$scope.filter = {
		'StartTime' : startdate,
		'EndTime' : new Date()
	};

	// If the page is changed, get the new page of events
	$scope.pageChanged = function(newPage) {
		$scope.page = newPage;
		getEvents($scope.filter_url, newPage);
	};

	// Call Events.get and pass it the page number
	// Set the appropriate scope values with the results.
	// The events.php file takes over and iterates over events
	// and the painator uses totalEvents and eventPerPage
	function getEvents(filter, pageNumber) {
		Events.get(filter, pageNumber).then(function(results) {
			$scope.events = results.data.events;
			$scope.totalEvents = results.data.pagination.count;
			$scope.eventsPerPage = results.data.pagination.limit;
		});
	}

	$scope.filterEvents = function() {
		var filters = new Array();
		var url = '';

		// Push all of the MonitorId's into the filters array
		angular.forEach($scope.filter.MonitorId, function(value, key) {
			filters.push('MonitorId:'+value);
		});

		var StartTime = $scope.filter.StartTime.toISOString().slice(0, 19).replace('T', ' ');
		filters.push('StartTime >=:'+StartTime);
		var EndTime = $scope.filter.EndTime.toISOString().slice(0, 19).replace('T', ' ');
		filters.push('EndTime <=:'+EndTime);

		console.log(filters);

		angular.forEach(filters, function(value, key) {
			url = url + value + '/';
		});

		length = url.length;
		$scope.filter_url = url.substring(0, length - 1);
		$scope.page = 1;
		getEvents($scope.filter_url, 1);
	}

	// This is called when a user clicks on an event.
	// It fires up a modal and passes it the EventId of the clicked event
	// EventController takes over from there.
	$scope.displayEvent = function (index) {
		var event = $scope.events[index];

		var modalInstance = $modal.open({
			templateUrl: '/?view=event&skin=bootstrap',
			controller: 'EventController',
			resolve: {
				eventId: function () { return event.Event.Id; },
				index: function () { return index; }
			 }
		});

		modalInstance.result.then(function (index) {
				$scope.events.splice(index, 1);
				$scope.totalEvents = $scope.totalEvents - 1;
			}, function () {
				console.log('Modal dismissed at: ' + new Date());
			}
		);
	};
});

ZoneMinder.controller('EventController', function($scope, Event, $modalInstance, eventId, index) {

	Event.get(eventId).then(function(results) {
		$scope.eventId			= eventId;
		$scope.name 				= results.data.event.Event.Name;
		$scope.cause 				= results.data.event.Event.Cause;
		$scope.startTime 		= results.data.event.Event.StartTime;
		$scope.endTime 			= results.data.event.Event.EndTime;
		$scope.width 				= results.data.event.Event.Width;
		$scope.length 			= results.data.event.Event.Length;
		$scope.frames				= results.data.event.Event.Frames;
		$scope.alarmFrames	= results.data.event.Event.AlarmFrames;
		$scope.totScore				= results.data.event.Event.TotScore;
		$scope.avgScore				= results.data.event.Event.AvgScore;
		$scope.maxScore				= results.data.event.Event.MaxScore;
		$scope.notes				= results.data.event.Event.Notes;
	});

	$scope.cancel = function () {
		$modalInstance.dismiss('cancel');
	};

	$scope.delete= function() {
		Event.delete(eventId).then(function(results) {
			$modalInstance.close(index);
			console.log(results);
		});
	};
});

ZoneMinder.controller('MonitorController', function($scope, $http, $location, Monitor) {
	// If mid is set, we're editing a monitor.  Else, we're adding one.
	var mid = $location.search().mid;
	if (mid) {
		Monitor.getMonitor(mid).then(function(results) {
			$scope.monitor = results.data.monitor.Monitor;
		});
	} else {
		// Assign each monitor a random color, as opposed to them all being 'red'
		var color =  '#' + (Math.random() * 0xFFFFFF << 0).toString(16);

		// This is where the monitor's 'form data' is saved
		// It is sent to saveMonitor when the save button is clicked
		$scope.monitor = {
			// Set default values for a monitor
			'Type' : 'Remote',
			'RefBlendPerc' : 6,
			'AlarmRefBlendPerc' : 6,
			'Function' : 'Monitor',
			'ImageBufferCount' : 100,
			'WarmupCount' : 25,
			'PreEventCount' : 50,
			'PostEventCount' : 50,
			'StreamReplayBuffer' : 1000,
			'AlarmFrameCount' : 1,
			'LabelFormat' : '%N - %d/%m/%y %H:%M:%S',
			'LabelX' : 0,
			'LabelY' : 0,
			'Colours' : 3,
			// Here be a bug!
			// When retrieving 'Orientation', it comes back and is set a a number
			// But it mustbe saved as a string (due to mysql enum)
			'Orientation' : '0',
			'Enabled' : true,
			'Protocol' : 'http',
			'SectionLength' : 600,
			'EventPrefix' : 'Event-',
			'FrameSkip' : 0,
			'MotionFrameSkip' : 0,
			'FPSReportInterval' : 1000,
			'DefaultView' : 'Events',
			'DefaultRate' : 100,
			'DefaultScale' : 100,
			'SignalCheckColour' : '#0000c0',
			'Method' : 'simple',
			'WebColour' : color
		};
	}


	// Call Monitor.saveMonitor when the save button is clicked.  Pass along $scope.monitor
	$scope.submitMonitor = function() {
		Monitor.saveMonitor($scope.monitor)
		// Redirect to the dashboard on success
	        .success(function(data) { window.location = "/index.php?view=console"; });
	};
});

ZoneMinder.controller('ConsoleController', function($scope, Console) {
	// Ask the API for events that have happened in the last week
	Console.getConsoleEvents('1 week').then(function(results) {
		// For each result, assign it to $scope[Counts$monitorId]
		for (var key in results['data']['results']) {
			var mid = key;
			var count = results['data']['results'][key];
			$scope['Counts' + mid] = count;
		}
	});

	$scope.delete = function (index) {
		var monitor = $scope.monitors[index];
		var id = monitor.Id;

		Console.delete(id).then(function(results) {
			$scope.monitors.splice(index, 1);
			console.log(results);
		});
	};

	Console.getMonitors().then(function(results) {
		var monitors = new Array();
		var daemons = ['zmc', 'zma']; // Daemons to check for each monitor

		// For each monitor
		angular.forEach(results['data']['monitors'], function(value, key) {
			var id = value.Monitor.Id;
			var alerts = value.Monitor.alerts = new Array();

			// Check if the above daemons are running for it
			angular.forEach(daemons, function(daemon) {
				// Ask the API for the daemonStatus of the id 
				Console.daemonStatus(id, daemon).then(function(results) {
					value.Monitor.alerts[daemon] = results.data.status;

					// If there is a failed daemon, set a generic error
					if (daemon) {
						value.Monitor.alert = 'zma or zmc is not running';
					}
				});
			});

			monitors.push(value.Monitor);
		});

		$scope.monitors = monitors;
	});
});


ZoneMinder.controller('ConfigController', function($scope, $http, Config) {

  Config.setConfigModel().then(function(results) {
    $scope.myModel = {configData: results.data.keyValues};
  }); 

	Config.getCategories().then(function(results) {
		// List of category names for the tabs
		$scope.categories = results.data.categories;

		// For each category, add all config options belonging to it to the categories array
		angular.forEach(results['data']['categories'], function(value, key) {
			var cat = results.data.categories[key].Config.Category;
			catman(cat);
		});

	});

	function catman(category) {
			Config.getCategory(category).then(function(results) {
				$scope[category] = results.data.config;
			});
	}

	$scope.updateConfig = function(configId, configName) {
		var newValue = $scope.myModel.configData[configName];
		var i = document.getElementById(configName).parentNode.parentNode;
		var s = i.getElementsByTagName("span");
		s = s[0];

		Config.updateOption(configId, newValue).then(function(results) {
			if (results.statusText == 'OK') {
				i.className = i.className + " has-success has-feedback";
				s.className = s.className + " glyphicon glyphicon-ok";
			} else {
				i.className = i.className + " has-failure has-feedback";
				s.className = s.className + " glyphicon glyphicon-ok";
			}
		});
	}

});

ZoneMinder.controller('HostController', function($scope, Host) {
  Host.getDiskPercent(function(diskPercent) {
		var array = [];
		angular.forEach(diskPercent.usage, function(value, key) {
			var a = {
				'value' : Math.floor(value),
				'label' : key,
  	    'color' : '#F7464A',
  	    'highlight'  : '#FFC870',
			};
			array.push(a);
		});
		$scope.ddata = array;
  });

	Host.getLoad(function(load) {
		$scope.loadData = {
			labels: ['1 min', '5 min', '15 min'],
			datasets: [{
				label: 'CPU Load',
				fillColor: 'rgba(220,220,220,0.2)',
				strokeColor: 'rgba(220,220,220,1)',
				pointColor: 'rgba(220,220,220,1)',
				pointStrokeColor: '#fff',
				pointHighlightFill: '#fff',
				pointHighlightStroke: 'rgba(220,220,220,1)',
				data: [ load.load[0], load.load[1], load.load[2] ]
			}]
		};
	});

    $scope.doptions =  {
      responsive: false,
      segmentShowStroke : true,
      segmentStrokeColor : '#fff',
      segmentStrokeWidth : 2,
      percentageInnerCutout : 50, // This is 0 for Pie charts
      animationSteps : 1,
      animationEasing : 'easeOutBounce',
      animateRotate : false,
      animateScale : false,
      legendTemplate : '<ul class="tc-chart-js-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>'
	};
});
