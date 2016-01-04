<?php
require_once( 'database.php' );
class Server {

    public function __construct( $IdOrRow = NULL ) {
		$row = NULL;
		if ( $IdOrRow ) {
			if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
				$row = dbFetchOne( 'SELECT * FROM Servers WHERE Id=?', NULL, array( $IdOrRow ) );
				if ( ! $row ) {
					Error("Unable to load Server record for Id=" . $IdOrRow );
				}
			} elseif ( is_array( $IdOrRow ) ) {
				$row = $IdOrRow;
			}
		} # end if isset($IdOrRow)
		if ( $row ) {
			foreach ($row as $k => $v) {
				$this->{$k} = $v;
			}
		} else {
			$this->{'Name'} = '';
			$this->{'Hostname'} = '';
		}
    }
	public static function find_all() {
		$servers = array();
		$result = dbQuery( 'SELECT * FROM Servers ORDER BY Name');
		$results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Server' );
		foreach ( $results as $row => $server_obj ) {
			$servers[] = $server_obj;
		}
		return $servers;
	}

	public function Url() {
		if ( $this->Id() ) {
			return ZM_BASE_PROTOCOL . '://'. $this->Hostname();
		} else {
			return '';
		}
	}
	public function Hostname() {
		if ( isset( $this->{'Hostname'} ) and ( $this->{'Hostname'} != '' ) ) {
			return $this->{'Hostname'};
		}
		return $this->{'Name'};
	}
	public function __call( $fn, array $args= NULL){
        if(isset($this->{$fn})){
            return $this->{$fn};
            #array_unshift($args, $this);
            #call_user_func_array( $this->{$fn}, $args);
        }
    }
}
?>
