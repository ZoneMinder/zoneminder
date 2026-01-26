<?php
App::uses('AppModel', 'Model');
/**
 * Event_Summary Model
 *
 */
class Event_Summary extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Event_Summaries';

/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'MonitorId';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'MonitorId';

	public $recursive = -1;

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'MonitorId' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);

	public function beforeFind($queryData) {
		$db = $this->getDataSource();
		$result = $db->fetchAll(
			"SELECT last_updated FROM Event_Summaries_Metadata WHERE table_name='Event_Summaries'"
		);
		if ($result) {
			$row = $result[0];
			$last_updated = isset($row['Event_Summaries_Metadata'])
				? $row['Event_Summaries_Metadata']['last_updated']
				: (isset($row[0]) ? $row[0]['last_updated'] : null);
			if ($last_updated && (time() - strtotime($last_updated)) >= 60) {
				$db->rawQuery("CALL Refresh_Summaries_SWR()");
			}
		}
		return $queryData;
	}
}
