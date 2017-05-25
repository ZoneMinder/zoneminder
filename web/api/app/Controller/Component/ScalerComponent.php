<?php
App::uses('Component', 'Controller');

class ScalerComponent extends Component {
	public function reScale( $dimension, $dummy ) {
		for ( $i = 1; $i < func_num_args(); $i++ )
		{
		    $scale = func_get_arg( $i );
		    if ( !empty($scale) && $scale != 100 )
		        $dimension = (int)(($dimension*$scale)/100);
		}
		return( $dimension );
		
	}

	public function deScale( $dimension, $dummy )
	{
	    for ( $i = 1; $i < func_num_args(); $i++ )
	    {
	        $scale = func_get_arg( $i );
	        if ( !empty($scale) && $scale != 100 )
	            $dimension = (int)(($dimension*100)/$scale);
	    }
	    return( $dimension );
	}
}
?>
