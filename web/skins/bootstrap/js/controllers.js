var ZoneMinder = angular.module('ZoneMinderControllers', []);

ZoneMinder.controller('HeaderController', function($scope, Header) {
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
	Log.getLogs(function(results) {
		$scope.logs = results.logs;
	});
});

ZoneMinder.controller('EventsController', function($scope, Events) {
	getEventsPage(1);

	$scope.pageChanged = function(newPage) {
		getEventsPage(newPage);
	};

	function getEventsPage(pageNumber) {
		Events.get(pageNumber).then(function(results) {
			$scope.events = results.data.events;
			$scope.totalEvents = results.data.pagination.count;
			$scope.eventsPerPage = results.data.pagination.limit;
		});
	}
});

ZoneMinder.controller('EventController', function($scope, $location, Event) {

	var eventId = $location.search().eid;

	Event.getEvent(eventId).then(function(results) {
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
