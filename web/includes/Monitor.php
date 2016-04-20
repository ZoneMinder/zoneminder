<?php
require_once( 'database.php' );
require_once( 'Server.php' );

class Monitor {
	public function __construct( $IdOrRow ) {
		$row = NULL;
        if ( $IdOrRow ) {
            if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
				$row = dbFetchOne( 'SELECT * FROM Monitors WHERE Id=?', NULL, array( $IdOrRow ) );
                if ( ! $row ) {
                    Error("Unable to load Server record for Id=" . $IdOrRow );
                }
            } elseif ( is_array( $IdOrRow ) ) {
                $row = $IdOrRow;
			} else {
				Error("Unknown argument passed to Monitor Constructor ($IdOrRow)");
				return;
            }
        } # end if isset($IdOrRow)

		if ( $row ) {
			foreach ($row as $k => $v) {
				$this->{$k} = $v;
			}
			if ( $this->{'Controllable'} ) {
				$s = dbFetchOne( 'SELECT * FROM Controls WHERE Id=?', NULL, array( $this->{'ControlId'} ) );
				foreach ($s as $k => $v) {
						if ( $k == 'Id' ) {
							continue;
						}
					$this->{$k} = $v;
				}
			}

		} else {
			Error("No row for Monitor " . $IdOrRow );
		}
	} // end function __construct
	public function Server() {
		return new Server( $this->{'ServerId'} );
	}
	public function __call( $fn, array $args){
        if(isset($this->{$fn})){
			return $this->{$fn};
            #array_unshift($args, $this);
            #call_user_func_array( $this->{$fn}, $args);
        }
    }
	public function getStreamSrc( $args, $querySep='&amp;' ) {
		if ( isset($this->{'ServerId'}) and $this->{'ServerId'} ) {
			$Server = new Server( $this->{'ServerId'} );
			$streamSrc = ZM_BASE_PROTOCOL.'://'.$Server->Hostname().ZM_PATH_ZMS;
		} else {
			$streamSrc = ZM_BASE_URL.ZM_PATH_ZMS;
		}

		$args[] = "monitor=".$this->{'Id'};

		if ( ZM_OPT_USE_AUTH ) {
			if ( ZM_AUTH_RELAY == "hashed" ) {
				$args[] = "auth=".generateAuthHash( ZM_AUTH_HASH_IPS );
			} elseif ( ZM_AUTH_RELAY == "plain" ) {
				$args[] = "user=".$_SESSION['username'];
				$args[] = "pass=".$_SESSION['password'];
			} elseif ( ZM_AUTH_RELAY == "none" ) {
				$args[] = "user=".$_SESSION['username'];
			}
		}
		if ( !in_array( "mode=single", $args ) && !empty($GLOBALS['connkey']) ) {
			$args[] = "connkey=".$GLOBALS['connkey'];
		}
		if ( ZM_RAND_STREAM ) {
			$args[] = "rand=".time();
		}

		if ( count($args) ) {
			$streamSrc .= "?".join( $querySep, $args );
		}

		return( $streamSrc );
	} // end function etStreamSrc
	public function Width() {
		if ( $this->Orientation() == '90' or $this->Orientation() == '270' ) {
			return $this->{'Height'};
		}
		return $this->{'Width'};
	}
	public function Height() {
		if ( $this->Orientation() == '90' or $this->Orientation() == '270' ) {
			return $this->{'Width'};
		}
		return $this->{'Height'};
	}
}
?>
