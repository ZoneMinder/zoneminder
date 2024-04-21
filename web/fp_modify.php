<?php

$mid = $_GET["mid"];
/*if ($_GET["ismon"] = "true")
{ $ismon = true}else{$ismon = false};*/
//$ismon = $_GET["ismon"];
$json = $_GET["json"];


$file_handle = fopen('/etc/zm.conf', 'r');
    while (!feof($file_handle)) {
      $line = fgets($file_handle);
      if (substr($line,0,1)!="#"){
          if (($pos = strpos($line, "=")) !== FALSE) { 
               $key = substr($line, 0, $pos);
               $value = substr($line,$pos+1);
               $value = str_replace(PHP_EOL, '', $value);
               // switchcase to get values intended
                switch($key) {
                         case 'ZM_DB_HOST':
                              $dbhost = $value;
                              break;
                         case 'ZM_DB_NAME':
                              $dbname = $value;
                              break;
                         case 'ZM_DB_USER':
                              $dbuser = $value;
                              break;
                         case 'ZM_DB_PASS':
                              $dbpass = $value;
                              break;
                     } // end switch
             }
         } //end verification comment
    } //end while


/*$conf_array = file('/etc/zm.conf', FILE_SKIP_EMPTY_LINES);
$dbhost = array_search('ZM_DB_HOST', $conf_array);
$dbname = array_search('ZM_DB_NAME', $conf_array);
$dbuser = array_search('ZM_DB_USER', $conf_array);
$dbpass = array_search('ZM_DB_PASS', $conf_array);
print_r($dbhost);
print_r($dbname);
print_r($dbuser);
print_r($dbpass);
//print_r($conf_array);*/


//$conn = new mysqli("localhost", "root", "", "dronfikamap");
$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);


// prepare and bind
$stmt = $conn->prepare("UPDATE floorplan SET json = ? WHERE mid = ?");
$stmt->bind_param("ss", $json, $mid);
$stmt->execute();
$stmt->close();
$conn->close();

?>
