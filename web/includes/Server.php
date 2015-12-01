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

	public function Url() {
		return ZM_BASE_PROTOCOL . '://'. $this->Hostname();
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
