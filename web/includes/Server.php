<?php
require_once( 'database.php' );
class Server {
    public function __construct( $id ) {
		if ( $id ) {
		$s = dbFetchOne( 'SELECT * FROM Servers WHERE Id=?', NULL, array( $id ) );
		if ( $s ) {
			foreach ($s as $k => $v) {
				$this->{$k} = $v;
			}
		} else {
			Error("Unable to load Server record for Id=" . $id );
		}
		} else {
			$this->{'Name'} = '';
			$this->{'Hostname'} = '';
		}
    }

	public function Name() {
		return $this->{'Name'};
	}
	public function Hostname() {
		if ( ( ! isset( $this->{'Hostname'} ) and $this->{'Hostname'} != '' ) ) {
			return $this->{'Name'};
		}
		return $this->{'Hostname'};
	}
}
?>
