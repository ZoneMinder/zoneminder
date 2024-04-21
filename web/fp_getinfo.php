<?php 

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


// need to create and print array with results

$conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
//$stmt = "SELECT mid, json FROM floorplan";
$stmt = "SELECT json FROM floorplan";
$result = $conn->query($stmt);

//$rows = $result->fetch_array(MYSQLI_NUM);

$rows = array();
while ($row = $result->fetch_row()) {
//    printf("%s (%s)\n", $row[0], $row[1]);
//    printf("%s (%s)\n", $row[0], $row[1]);
    $rows[] = $row;
}
print json_encode($rows);

//print_r ($rows); 

//$stmt->close();
$conn->close();


/*


//$db = new PDO('mysql:host=localhost;dbname=poi', 'root', ''); 
$connstr = 'mysql:host='.$dbhost.';dbname='.$dbname.';charset=UTF8, '.$dbuser.', '.$dbpass.'';
print ($connstr);
//$db = new PDO('mysql:host='.$dbhost.';dbname='.$dbname.', '.$dbuser.', '.$dbpass.''); 
$db = new PDO($connstr); 

//print ($db);

$sql = "SELECT * FROM floorplan"; 

$rs = $db->query($sql); 
if (!$rs) { 
    echo "An SQL error occured.\n"; 
    exit; 
} 

$rows = array(); 
while($r = $rs->fetch(PDO::FETCH_ASSOC)) { 
    $mid[] = $r['mid']; 
    $name[] = $r['name'];
    $user_date[] = $r['user_date'];
    $user_time[] = $r['user_time'];
    $address[] = $r['address'];
    $icon_name[] = $r['icon_name'];
    $json[] = $r['json'];

} 
//print json_encode($rows); 
print_r ($rows); 

$db = NULL; */
?> 
