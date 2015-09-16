<?php
require_once( 'database.php' );
class Server {
	public function __construct( array $params = array() ) {
		if ( isset( $params['Id']) and $params['Id'] ) {
			$s = dbFetchOne( 'SELECT * FROM Servers WHERE Id=?', NULL, array( $params['Id'] ) );
			if ( $s ) {
				foreach ($s as $k => $v) {
					$this->{$k} = $v;
				}
			} else {
				Error("Unable to load Server record for Id=" . $params['Id'] );
			}
		}
	}
	public function Name() {
	return $this->{'Name'};
	}
}
?>
