<?php
require_once( 'database.php' );
class Storage {
    public function __construct( $IdOrRow ) {
		$row = NULL;
		if ( isset($IdOrRow) ) {
			if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
				$row = dbFetchOne( 'SELECT * FROM Storage WHERE Id=?', NULL, array( $IdOrRow ) );
				if ( ! $row ) {
					Error("Unable to load Storage record for Id=" . $IdOrRow );
				}
			} elseif ( is_array( $IdOrRow ) ) {
				$row = $IdOrRow;
			}
		}
		if ( $row ) {
			foreach ($row as $k => $v) {
				$this->{$k} = $v;
			}
		} else {
			$this->{'Name'} = '';
			$this->{'Path'} = '';
		}
    }

	public function Path() {
		if ( isset( $this->{'Path'} ) and ( $this->{'Path'} != '' ) ) {
			return $this->{'Path'};
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
