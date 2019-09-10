<?php
namespace ZM;
require_once('database.php');
require_once('Event.php');

class Frame extends ZM_Object {
  protected static $table = 'Frames';
  protected $defaults = array(
      'Id'            =>  null,
      'EventId'       =>  '',
      'FrameId'       =>  null,
      'Type'          => 'Normal',
      'TimeStamp'     =>  null,
      'Delta'         =>  0,
      'Score'         =>  0,
      'Data_json'     =>  '',
      );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function Storage() {
    return $this->Event()->Storage();
  }

  public function Event() {
    return Event::find_one(array('Id'=>$this->{'EventId'}));
  }

  public function getImageSrc( $show='capture' ) {
    return '?view=image&fid='.$this->{'FrameId'}.'&eid='.$this->{'EventId'}.'&show='.$show;
    #return '?view=image&fid='.$this->{'Id'}.'&show='.$show.'&filename='.$this->Event()->MonitorId().'_'.$this->{'EventId'}.'_'.$this->{'FrameId'}.'.jpg';
  } // end function getImageSrc

  public function Path($show='capture') {
    return sprintf(
      '%s/%0'.ZM_EVENT_IMAGE_DIGITS.'d-%s.jpg',
      $this->Event()->Path(), $this->FrameId(), $show
    );
  }

  public function Data_json() {
    if ( func_num_args( ) ) {
      $this->{'Data_json'} = func_get_arg(0);;
      $this->{'Data'} = jsonDecode($this->{'Data_json'});
    }
    return $this->{'Data_json'};
  }

  public function Data() {
    if ( func_num_args( ) ) {
      $this->{'Data'} = func_get_arg(0);;
      $this->{'Data_json'} = jsonEncode($this->{'Data'});
    }
    if ( !array_key_exists('Data', $this) ) {
      if ( array_key_exists('Data_json', $this) and $this->{'Data_json'} ) {
        $this->{'Data'} = jsonDecode($this->{'Data_json'});
      } else {
        $this->{'Data'} = array();
      }
    } else {
      if ( !is_array($this->{'Data'}) ) {
        # Handle existence of both Data_json and Data in the row
        $this->{'Data'} = jsonDecode($this->{'Data_json'});
      }
    }
    return $this->{'Data'};
  }

} # end class
?>
