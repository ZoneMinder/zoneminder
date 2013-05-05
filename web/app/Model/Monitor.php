<?php
  class Monitor extends AppModel {
    public $useTable = 'Monitors';
    public $hasMany = array(
	'Event' => array(
		'className' => 'Event',
		'foreignKey' => 'MonitorId',
		'fields' => 'Event.Id'
	),
	'Zone' => array(
		'className' => 'Zone',
		'foreignKey' => 'MonitorId',
		'fields' => 'Zone.Id'
	)
    );

	public function getEventsLastHour() {
		$conditions = array('Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
		$group = array('Event.MonitorId');
		$fields = array('count(Event.Id) AS count');
		return $this->Event->find('all', compact('conditions', 'group', 'fields'));
	}

	public function getEventsLastDay() {
		$conditions = array('Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 DAY)');
		$group = array('Event.MonitorId');
		$fields = array('count(Event.Id) AS count');
		return $this->Event->find('all', compact('conditions', 'group', 'fields'));
	}

	public function getEventsLastWeek() {
		$conditions = array('Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 WEEK)');
		$group = array('Event.MonitorId');
		$fields = array('count(Event.Id) AS count');
		return $this->Event->find('all', compact('conditions', 'group', 'fields'));
	}

	public function getEventsLastMonth() {
		$conditions = array('Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 MONTH)');
		$group = array('Event.MonitorId');
		$fields = array('count(Event.Id) AS count');
		return $this->Event->find('all', compact('conditions', 'group', 'fields'));
	}

	public function getEventsArchived() {
		$conditions = array('Event.Archived = 1');
		$group = array('Event.MonitorId');
		$fields = array('count(Event.Id) AS count');
		return $this->Event->find('all', compact('conditions', 'group', 'fields'));
	}
  }
?>
