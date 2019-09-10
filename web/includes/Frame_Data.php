<?php
namespace ZM;
class Frame_Data {

  public function __construct($data = NULL) {
    $this->{'data'} = $data;
  } # end function __construct

  /* Will output a useful representation of the data. */
  public function to_string() {
    $data = $this->{'data'};

    if ( isset($data['type']) ) {
      if ( $data['type'] == 'PLATE_RECOGNIZER' ) {
        return 'plate: '.$data['data']['results'][0]['plate'].' score: '.$data['data']['results'][0]['score'];
      } else {
        return print_r($data,true);
      }
    } else {
      return print_r($data,true);
    }
  }  // end public to_string
}
?>
